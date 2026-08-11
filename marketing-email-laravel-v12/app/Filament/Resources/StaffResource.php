<?php

namespace App\Filament\Resources;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\Crm\PositionAuthority;
use App\Enums\Crm\StaffEmploymentStatus;
use App\Filament\Pages\OrganizationAccessPage;
use App\Filament\Resources\StaffResource\Pages;
use App\Filament\Resources\StaffResource\RelationManagers\AvailabilitiesRelationManager;
use App\Filament\Resources\StaffResource\RelationManagers\InteractionsRelationManager;
use App\Filament\Resources\StaffResource\RelationManagers\ScheduleRelationManager;
use App\Filament\Resources\StaffResource\RelationManagers\WorkScheduleRelationManager;
use App\Models\Crm\Department;
use App\Models\Crm\Position;
use App\Models\Crm\Staff;
use App\Models\User;
use App\Support\Ui\BadgePalette;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 23;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('crm.manage-staff') ?? false;
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.configuration');
    }

    public static function getNavigationParentItem(): ?string
    {
        return OrganizationAccessPage::getNavigationLabel();
    }

    public static function getNavigationLabel(): string
    {
        return __('configuration.navigation.staff');
    }

    public static function getModelLabel(): string
    {
        return __('configuration.staff.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('configuration.staff.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('crm.manage-staff') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('crm.manage-staff') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('crm.manage-staff') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('crm.manage-staff') ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'department',
            'position',
            'user',
            'businessFunctions',
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('configuration.staff.identity_section'))
                ->columns(['default' => 1, 'md' => 2, 'xl' => 3])
                ->schema([
                    TextInput::make('full_name')
                        ->label(__('configuration.staff.full_name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(['default' => 1, 'xl' => 2]),
                    TextInput::make('phone')
                        ->label(__('configuration.staff.phone'))
                        ->tel()
                        ->maxLength(30),
                    TextInput::make('employee_code')
                        ->label(__('configuration.staff.employee_code'))
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder(__('configuration.staff.employee_code_auto')),
                ]),

            Section::make(__('configuration.staff.organization_section'))
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    Select::make('department_id')
                        ->label(__('configuration.staff.department'))
                        ->options(Department::options())
                        ->default(fn (): ?int => request()->integer('department_id') ?: null)
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('position_id')
                        ->label(__('configuration.staff.position'))
                        ->options(fn (): array => Position::groupedOptions())
                        ->searchable()
                        ->preload()
                        ->placeholder(__('configuration.staff.position_optional')),
                ]),

            Section::make(__('configuration.staff.business_section'))
                ->schema([
                    Repeater::make('businessFunctions')
                        ->relationship('businessFunctions')
                        ->label(__('configuration.business_functions.title'))
                        ->addActionLabel(__('configuration.business_functions.add'))
                        ->defaultItems(0)
                        ->collapsed()
                        ->itemLabel(function (array $state): string {
                            $function = DepartmentFunction::tryFrom((string) ($state['function_key'] ?? ''));
                            $authority = PositionAuthority::tryFrom((string) ($state['authority_level'] ?? ''));

                            return collect([
                                $function?->label(),
                                $authority?->label(),
                            ])->filter()->implode(' · ') ?: __('configuration.business_functions.new_item');
                        })
                        ->schema([
                            Select::make('function_key')
                                ->label(__('configuration.business_functions.function'))
                                ->options(collect(DepartmentFunction::options())->except(['admin', 'other'])->all())
                                ->native(false)
                                ->searchable()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->required(),
                            Select::make('authority_level')
                                ->label(__('configuration.business_functions.authority'))
                                ->options(PositionAuthority::options())
                                ->default(PositionAuthority::Member->value)
                                ->native(false)
                                ->required(),
                            Toggle::make('is_primary')
                                ->label(__('configuration.business_functions.primary'))
                                ->fixIndistinctState()
                                ->default(false),
                            Toggle::make('is_active')
                                ->label(__('configuration.business_functions.active'))
                                ->default(true),
                        ])
                        ->columns(['default' => 1, 'md' => 2, 'xl' => 4])
                        ->columnSpanFull(),
                ]),

            Section::make(__('configuration.staff.capacity_section'))
                ->collapsible()
                ->collapsed()
                ->columns(['default' => 1, 'md' => 2, 'xl' => 3])
                ->schema([
                    Select::make('employment_status')
                        ->label(__('configuration.staff.employment_status'))
                        ->options(StaffEmploymentStatus::options())
                        ->default(StaffEmploymentStatus::Active->value)
                        ->native(false)
                        ->required(),
                    Toggle::make('can_receive_customers')
                        ->label(__('configuration.staff.can_receive_customers'))
                        ->default(false),
                    TextInput::make('customer_capacity')
                        ->label(__('configuration.staff.customer_capacity'))
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('distribution_weight')
                        ->label(__('configuration.staff.distribution_weight'))
                        ->numeric()
                        ->minValue(0)
                        ->default(1),
                    DatePicker::make('started_at')
                        ->label(__('configuration.staff.started_at'))
                        ->native(false)
                        ->displayFormat('d/m/Y'),
                    DatePicker::make('ended_at')
                        ->label(__('configuration.staff.ended_at'))
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->minDate(fn (Get $get) => $get('started_at')),
                ]),

            Section::make(__('configuration.staff.account_section'))
                ->collapsed()
                ->schema([
                    Select::make('user_id')
                        ->label(__('configuration.staff.account'))
                        ->options(function (?Staff $record): array {
                            return User::query()
                                ->where(function ($query) use ($record): void {
                                    $query->whereDoesntHave('staff');

                                    if ($record?->user_id) {
                                        $query->orWhereKey($record->user_id);
                                    }
                                })
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (User $user): array => [
                                    $user->id => $user->name.' - '.$user->email,
                                ])
                                ->all();
                        })
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                            if (! $state || filled($get('full_name'))) {
                                return;
                            }

                            $name = User::query()->whereKey((int) $state)->value('name');
                            if ($name) {
                                $set('full_name', $name);
                            }
                        }),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee_code')
                    ->label(__('configuration.staff.employee_code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('full_name')
                    ->label(__('configuration.staff.full_name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department.name')
                    ->label(__('configuration.staff.department'))
                    ->badge()
                    ->color(fn (Staff $record): string => $record->department?->color ?? 'gray'),
                TextColumn::make('position.title')
                    ->label(__('configuration.staff.position'))
                    ->placeholder(__('common.not_available')),
                TextColumn::make('primary_business_function')
                    ->label(__('configuration.staff.primary_business_function'))
                    ->getStateUsing(fn (Staff $record): ?string => $record->primaryBusinessFunction()?->value)
                    ->formatStateUsing(fn (?string $state): string => DepartmentFunction::tryFrom((string) $state)?->label() ?? __('common.not_available'))
                    ->badge()
                    ->color(fn (?string $state): string => DepartmentFunction::tryFrom((string) $state)?->color() ?? 'gray'),
                TextColumn::make('user.email')
                    ->label(__('configuration.staff.account'))
                    ->placeholder(__('common.not_available'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('employment_status')
                    ->label(__('configuration.staff.employment_status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state instanceof StaffEmploymentStatus
                        ? $state
                        : StaffEmploymentStatus::tryFrom((string) $state))?->label() ?? __('common.not_available'))
                    ->color(fn ($state): string => BadgePalette::status($state instanceof StaffEmploymentStatus ? $state->value : (string) $state)),
                IconColumn::make('can_receive_customers')
                    ->label(__('configuration.staff.can_receive_customers'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('provision_account')
                        ->label(__('configuration.staff.provision_account'))
                        ->icon('heroicon-o-user-plus')
                        ->url(fn (Staff $record): string => UserResource::getUrl('create', [
                            'staff_id' => $record->id,
                        ]))
                        ->visible(fn (Staff $record): bool => $record->user_id === null),
                    EditAction::make()->label(__('configuration.staff.edit')),
                    DeleteAction::make()->label(__('configuration.staff.delete')),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('configuration.staff.delete')),
                ]),
            ])
            ->filters([
                SelectFilter::make('department_id')
                    ->label(__('configuration.staff.department'))
                    ->relationship('department', 'name'),
                SelectFilter::make('position_id')
                    ->label(__('configuration.staff.position'))
                    ->relationship('position', 'title'),
                SelectFilter::make('employment_status')
                    ->label(__('configuration.staff.employment_status'))
                    ->options(StaffEmploymentStatus::options()),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AvailabilitiesRelationManager::class,
            InteractionsRelationManager::class,
            ScheduleRelationManager::class,
            WorkScheduleRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'edit' => Pages\EditStaff::route('/{record}/edit'),
        ];
    }
}
