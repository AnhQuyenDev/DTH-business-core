<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Crm\Staff;
use App\Models\User;
use App\Services\Organization\RoleDepartmentService;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?int $navigationSort = 50;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.user.singular');
    }

    public static function getModelLabel(): string
    {
        return __('resource.user.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.user.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('staff_id')
                ->label(__('field.staff'))
                ->options(function (?User $record): array {
                    return Staff::query()
                        ->with('department')
                        ->where(function ($query) use ($record): void {
                            $query->whereNull('user_id');

                            if ($record) {
                                $query->orWhere('user_id', $record->id);
                            }
                        })
                        ->orderBy('full_name')
                        ->get()
                        ->mapWithKeys(fn (Staff $staff): array => [
                            $staff->id => sprintf(
                                '%s - %s%s',
                                $staff->employee_code,
                                $staff->full_name,
                                $staff->department?->name
                                    ? ' ('.$staff->department->name.')'
                                    : ''
                            ),
                        ])
                        ->all();
                })
                ->searchable()
                ->preload()
                ->default(fn (): ?int => request()->integer('staff_id') ?: null)
                ->live()
                ->required(
                    fn (Get $get): bool => app(
                        RoleDepartmentService::class
                    )->requiresStaff($get('role'))
                )
                ->afterStateUpdated(function (Set $set, ?string $state): void {
                    if (! $state) {
                        return;
                    }

                    $staff = Staff::query()->find((int) $state);

                    if ($staff) {
                        $set('name', $staff->full_name);
                    }
                })
                ->helperText(__('helper.user_staff_link')),

            Placeholder::make('staff_department_preview')
                ->label(__('field.department'))
                ->content(function (Get $get): string {
                    $staff = filled($get('staff_id'))
                        ? Staff::query()->with('department')->find((int) $get('staff_id'))
                        : null;

                    return $staff?->department?->name
                        ?? __('common.not_available');
                }),

            Placeholder::make('staff_position_preview')
                ->label(__('field.position'))
                ->content(function (Get $get): string {
                    $staff = filled($get('staff_id'))
                        ? Staff::query()->with('position')->find((int) $get('staff_id'))
                        : null;

                    return $staff?->position?->title
                        ?? __('common.not_available');
                }),

            TextInput::make('name')
                ->label(__('field.name'))
                ->default(function (): ?string {
                    $staffId = request()->integer('staff_id');

                    return $staffId > 0
                        ? Staff::query()->whereKey($staffId)->value('full_name')
                        : null;
                })
                ->required()
                ->maxLength(255)
                ->helperText(__('helper.user_name_from_staff')),

            TextInput::make('email')
                ->label(__('field.email'))
                ->email()
                ->required()
                ->unique(ignoreRecord: true),

            Select::make('role')
                ->label(__('field.role'))
                ->options(UserRole::options())
                ->required()
                ->searchable()
                ->live()
                ->helperText(__('helper.role_department_function')),

            Toggle::make('is_active')
                ->label(__('field.account_active'))
                ->default(true)
                ->helperText(__('helper.account_active')),

            TextInput::make('password')
                ->label(__('field.password'))
                ->password()
                ->revealable()
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $operation) => $operation === 'create'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')
                ->label(__('field.name'))
                ->searchable()
                ->sortable(),
            TextColumn::make('email')
                ->label(__('field.email'))
                ->searchable()
                ->sortable(),
            TextColumn::make('staff.department.name')
                ->label(__('field.department'))
                ->badge()
                ->placeholder(__('common.not_available'))
                ->color(
                    fn (User $record): string => $record->staff?->department?->color
                        ?? 'gray'
                ),
            TextColumn::make('staff.position.title')
                ->label(__('field.position'))
                ->placeholder(__('common.not_available'))
                ->toggleable(),
            TextColumn::make('role')
                ->label(__('field.role'))
                ->badge()
                ->formatStateUsing(
                    fn (?string $state): string => $state
                        ? UserRole::tryFrom($state)?->label()
                            ?? __('common.not_available')
                        : __('common.not_available')
                )
                ->color(
                    fn (?string $state): string => UserRole::tryFrom(
                        (string) $state
                    )?->color() ?? 'gray'
                ),
            IconColumn::make('is_active')
                ->label(__('field.account_active'))
                ->boolean(),
            TextColumn::make('created_at')
                ->label(__('field.created_at'))
                ->dateTime('d/m/Y H:i')
                ->sortable(),
        ])
            ->actions([ActionGroup::make([
                EditAction::make(),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton()])
;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
