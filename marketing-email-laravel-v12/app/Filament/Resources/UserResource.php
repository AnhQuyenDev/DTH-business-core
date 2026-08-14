<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages;
use App\Models\Crm\Staff;
use App\Models\User;
use App\Services\Organization\RoleDepartmentService;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?int $navigationSort = 30;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('configuration.navigation.accounts');
    }

    public static function getModelLabel(): string
    {
        return __('configuration.account.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('configuration.account.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('system.manage-users') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('system.manage-users') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $actor = auth()->user();

        if (! ($actor?->can('system.manage-users') ?? false)) {
            return false;
        }

        return ! ($record instanceof User && $record->isSuperAdmin() && ! $actor->isSuperAdmin());
    }

    public static function canDelete(Model $record): bool
    {
        $actor = auth()->user();

        if (! ($actor?->isSuperAdmin() ?? false)) {
            return false;
        }

        if (! $actor->can('system.manage-users')) {
            return false;
        }

        return $record instanceof User && $record->id !== $actor->id;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('configuration.account.section_link'))
                ->icon('heroicon-o-user-plus')
                ->iconColor('primary')
                ->compact()
                ->extraAttributes(['class' => 'dth-config-form'])
                ->columns(12)
                ->schema([
                    Select::make('staff_id')
                        ->label(__('configuration.account.staff'))
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
                        ->required(fn (Get $get): bool => app(RoleDepartmentService::class)->requiresStaff($get('role')))
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.account.staff_hint'))
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            if (! $state) {
                                return;
                            }

                            $staff = Staff::query()->find((int) $state);
                            if ($staff) {
                                $set('name', $staff->full_name);
                            }
                        })
                        ->columnSpan(['default' => 12, 'md' => 6]),

                    Placeholder::make('staff_department_preview')
                        ->label(__('configuration.account.department'))
                        ->content(function (Get $get): string {
                            $staff = filled($get('staff_id'))
                                ? Staff::query()->with('department')->find((int) $get('staff_id'))
                                : null;

                            return $staff?->department?->name ?? __('common.not_available');
                        })
                        ->columnSpan(['default' => 6, 'md' => 3]),

                    Placeholder::make('staff_position_preview')
                        ->label(__('configuration.account.position'))
                        ->content(function (Get $get): string {
                            $staff = filled($get('staff_id'))
                                ? Staff::query()->with('position')->find((int) $get('staff_id'))
                                : null;

                            return $staff?->position?->title ?? __('common.not_available');
                        })
                        ->columnSpan(['default' => 6, 'md' => 3]),
                ]),

            Section::make(__('configuration.account.section_access'))
                ->icon('heroicon-o-lock-closed')
                ->iconColor('primary')
                ->compact()
                ->extraAttributes(['class' => 'dth-config-form'])
                ->columns(12)
                ->schema([
                    TextInput::make('name')
                        ->label(__('configuration.account.name'))
                        ->prefixIcon('heroicon-o-user')
                        ->default(function (): ?string {
                            $staffId = request()->integer('staff_id');

                            return $staffId > 0
                                ? Staff::query()->whereKey($staffId)->value('full_name')
                                : null;
                        })
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(['default' => 12, 'md' => 6]),

                    TextInput::make('email')
                        ->label(__('configuration.account.email'))
                        ->prefixIcon('heroicon-o-envelope')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->columnSpan(['default' => 12, 'md' => 6]),

                    Select::make('role')
                        ->label(__('configuration.account.system_role'))
                        ->options(function (): array {
                            $options = UserRole::options();
                            if (! (auth()->user()?->isSuperAdmin() ?? false)) {
                                unset($options[UserRole::SuperAdmin->value]);
                            }

                            return $options;
                        })
                        ->required()
                        ->searchable()
                        ->native(false)
                        ->live()
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.account.system_role_hint'))
                        ->columnSpan(['default' => 12, 'md' => 5]),

                    Select::make('roles')
                        ->label(__('configuration.account.additional_roles'))
                        ->relationship('roles', 'label')
                        ->getOptionLabelFromRecordUsing(fn ($record): string => $record->label ?: $record->name)
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->hintIcon('heroicon-m-question-mark-circle', __('v1.rbac.additional_roles_helper'))
                        ->visible(fn (): bool => auth()->user()?->can('system.manage-rbac') ?? false)
                        ->columnSpan(['default' => 12, 'md' => 5]),

                    Toggle::make('is_active')
                        ->label(__('configuration.account.active'))
                        ->inline()
                        ->default(true)
                        ->extraFieldWrapperAttributes(['class' => 'dth-config-toggle-wrap'])
                        ->columnSpan(['default' => 12, 'md' => 2]),

                    TextInput::make('password')
                        ->label(__('configuration.account.password'))
                        ->prefixIcon('heroicon-o-lock-closed')
                        ->password()
                        ->revealable()
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $operation) => $operation === 'create')
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.account.password_hint'))
                        ->columnSpan(['default' => 12, 'md' => 6]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('configuration.account.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('configuration.account.email'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('staff.full_name')
                    ->label(__('configuration.account.staff'))
                    ->placeholder(__('common.not_available'))
                    ->searchable(),
                TextColumn::make('role')
                    ->label(__('configuration.account.system_role'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state
                        ? UserRole::tryFrom($state)?->label() ?? __('common.not_available')
                        : __('common.not_available'))
                    ->color(fn (?string $state): string => UserRole::tryFrom((string) $state)?->color() ?? 'gray'),
                IconColumn::make('is_active')
                    ->label(__('configuration.account.active'))
                    ->boolean(),
                TextColumn::make('staff.department.name')
                    ->label(__('configuration.account.department'))
                    ->badge()
                    ->placeholder(__('common.not_available'))
                    ->color(fn (User $record): string => $record->staff?->department?->color ?? 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('staff.position.title')
                    ->label(__('configuration.account.position'))
                    ->placeholder(__('common.not_available'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('configuration.account.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label(__('configuration.account.active')),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()->label(__('configuration.account.edit'))->icon('heroicon-o-pencil-square'),
                    DeleteAction::make()
                        ->label(__('configuration.account.delete'))
                        ->icon('heroicon-o-trash')
                        ->modalHeading(__('configuration.account.delete_confirm_title'))
                        ->modalDescription(__('configuration.account.delete_confirm_description'))
                        ->visible(fn (User $record): bool => $record->id !== auth()->id()),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label(__('configuration.common.activate_selected'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => User::query()->whereKey($records->modelKeys())->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->label(__('configuration.common.deactivate_selected'))
                        ->icon('heroicon-o-no-symbol')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $actorId = auth()->id();
                            $ids = $records->reject(fn (User $user): bool => $user->isSuperAdmin() || $user->id === $actorId)->modelKeys();

                            User::query()->whereKey($ids)->update(['is_active' => false]);
                        })
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('delete_accounts')
                        ->label(__('configuration.common.delete_selected'))
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(__('configuration.account.bulk_delete_confirm_title'))
                        ->modalDescription(__('configuration.account.bulk_delete_confirm_description'))
                        ->visible(fn (): bool => auth()->user()?->isSuperAdmin() ?? false)
                        ->action(function (Collection $records): void {
                            $actorId = auth()->id();
                            $deleteable = $records->reject(fn (User $user): bool => $user->isSuperAdmin() || $user->id === $actorId);
                            $blocked = $records->count() - $deleteable->count();

                            $deleteable->each(fn (User $user): bool => (bool) $user->delete());

                            Notification::make()
                                ->color($blocked > 0 ? 'warning' : 'success')
                                ->title(__('configuration.account.bulk_delete_result', [
                                    'deleted' => $deleteable->count(),
                                    'blocked' => $blocked,
                                ]))
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
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
