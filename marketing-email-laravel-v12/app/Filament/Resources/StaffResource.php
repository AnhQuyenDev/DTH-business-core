<?php

namespace App\Filament\Resources;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\Crm\PositionAuthority;
use App\Enums\Crm\StaffEmploymentStatus;
use App\Filament\Resources\StaffResource\Pages;
use App\Filament\Resources\StaffResource\RelationManagers\AvailabilitiesRelationManager;
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
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 22;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('crm.manage-staff') ?? false;
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.configuration');
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
                ->icon('heroicon-o-user-circle')
                ->iconColor('primary')
                ->compact()
                ->extraAttributes(['class' => 'dth-config-form'])
                ->columns(12)
                ->schema([
                    TextInput::make('full_name')
                        ->label(__('configuration.staff.full_name'))
                        ->prefixIcon('heroicon-o-user')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(['default' => 12, 'md' => 7]),

                    TextInput::make('phone')
                        ->label(__('configuration.staff.phone'))
                        ->prefixIcon('heroicon-o-phone')
                        ->tel()
                        ->maxLength(30)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state) => $set('phone', Staff::normalizePhone($state)))
                        ->dehydrateStateUsing(fn (?string $state): ?string => Staff::normalizePhone($state))
                        ->unique(ignoreRecord: true)
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.staff.phone_hint'))
                        ->columnSpan(['default' => 12, 'md' => 5]),

                    TextInput::make('employee_code')
                        ->label(__('configuration.staff.employee_code'))
                        ->prefixIcon('heroicon-o-identification')
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (?Staff $record): bool => $record !== null)
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.staff.employee_code_hint'))
                        ->columnSpan(['default' => 12, 'md' => 4]),
                ]),

            Section::make(__('configuration.staff.organization_section'))
                ->icon('heroicon-o-building-office-2')
                ->iconColor('primary')
                ->compact()
                ->extraAttributes(['class' => 'dth-config-form'])
                ->columns(12)
                ->schema([
                    Select::make('department_id')
                        ->label(__('configuration.staff.department'))
                        ->options(Department::options())
                        ->default(fn (): ?int => request()->integer('department_id') ?: null)
                        ->searchable()
                        ->preload()
                        ->required()
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.staff.department_hint'))
                        ->columnSpan(['default' => 12, 'md' => 6]),

                    Select::make('position_id')
                        ->label(__('configuration.staff.position'))
                        ->options(fn (): array => Position::groupedOptions())
                        ->searchable()
                        ->preload()
                        ->placeholder(__('configuration.staff.position_optional'))
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.staff.position_hint'))
                        ->columnSpan(['default' => 12, 'md' => 6]),
                ]),

            Section::make(__('configuration.staff.business_section'))
                ->icon('heroicon-o-squares-plus')
                ->iconColor('primary')
                ->compact()
                ->extraAttributes(['class' => 'dth-config-form'])
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
                                ->required()
                                ->hintIcon('heroicon-m-question-mark-circle', __('configuration.business_functions.function_hint')),
                            Select::make('authority_level')
                                ->label(__('configuration.business_functions.authority'))
                                ->options(PositionAuthority::options())
                                ->default(PositionAuthority::Member->value)
                                ->native(false)
                                ->required()
                                ->hintIcon('heroicon-m-question-mark-circle', __('configuration.business_functions.authority_hint')),
                            Toggle::make('is_primary')
                                ->label(__('configuration.business_functions.primary'))
                                ->inline()
                                ->fixIndistinctState()
                                ->default(false)
                                ->extraFieldWrapperAttributes(['class' => 'dth-config-toggle-wrap']),
                            Toggle::make('is_active')
                                ->label(__('configuration.business_functions.active'))
                                ->inline()
                                ->default(true)
                                ->extraFieldWrapperAttributes(['class' => 'dth-config-toggle-wrap']),
                        ])
                        ->columns(['default' => 1, 'md' => 2, 'xl' => 4])
                        ->columnSpanFull(),
                ]),

            Section::make(__('configuration.staff.capacity_section'))
                ->icon('heroicon-o-chart-bar-square')
                ->iconColor('primary')
                ->compact()
                ->collapsible()
                ->extraAttributes(['class' => 'dth-config-form'])
                ->columns(12)
                ->schema([
                    Select::make('employment_status')
                        ->label(__('configuration.staff.employment_status'))
                        ->options(StaffEmploymentStatus::options())
                        ->default(StaffEmploymentStatus::Active->value)
                        ->native(false)
                        ->required()
                        ->columnSpan(['default' => 12, 'md' => 4]),

                    Toggle::make('can_receive_customers')
                        ->label(__('configuration.staff.can_receive_customers'))
                        ->inline()
                        ->live()
                        ->default(false)
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.staff.can_receive_customers_hint'))
                        ->extraFieldWrapperAttributes(['class' => 'dth-config-toggle-wrap'])
                        ->columnSpan(['default' => 12, 'md' => 4]),

                    TextInput::make('customer_capacity')
                        ->label(__('configuration.staff.customer_capacity'))
                        ->numeric()
                        ->minValue(1)
                        ->required(fn (Get $get): bool => (bool) $get('can_receive_customers'))
                        ->disabled(fn (Get $get): bool => ! (bool) $get('can_receive_customers'))
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.staff.customer_capacity_hint'))
                        ->columnSpan(['default' => 12, 'md' => 4]),

                    TextInput::make('distribution_weight')
                        ->label(__('configuration.staff.distribution_weight'))
                        ->numeric()
                        ->minValue(0)
                        ->default(1)
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.staff.distribution_weight_hint'))
                        ->columnSpan(['default' => 12, 'md' => 4]),

                    DatePicker::make('started_at')
                        ->label(__('configuration.staff.started_at'))
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->columnSpan(['default' => 12, 'md' => 4]),

                    DatePicker::make('ended_at')
                        ->label(__('configuration.staff.ended_at'))
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->minDate(fn (Get $get) => $get('started_at'))
                        ->rule('after_or_equal:started_at')
                        ->columnSpan(['default' => 12, 'md' => 4]),
                ]),

            Section::make(__('configuration.staff.account_section'))
                ->icon('heroicon-o-lock-closed')
                ->iconColor('primary')
                ->compact()
                ->collapsible()
                ->collapsed()
                ->extraAttributes(['class' => 'dth-config-form'])
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
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.staff.account_hint'))
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
                TextColumn::make('employment_status')
                    ->label(__('configuration.staff.employment_status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state instanceof StaffEmploymentStatus
                        ? $state
                        : StaffEmploymentStatus::tryFrom((string) $state))?->label() ?? __('common.not_available'))
                    ->color(fn ($state): string => BadgePalette::status($state instanceof StaffEmploymentStatus ? $state->value : (string) $state)),
                IconColumn::make('can_receive_customers')
                    ->label(__('configuration.staff.can_receive_customers'))
                    ->boolean(),
                TextColumn::make('user.email')
                    ->label(__('configuration.staff.account'))
                    ->placeholder(__('common.not_available'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('primary_business_function')
                    ->label(__('configuration.staff.primary_business_function'))
                    ->getStateUsing(fn (Staff $record): ?string => $record->primaryBusinessFunction()?->value)
                    ->formatStateUsing(fn (?string $state): string => DepartmentFunction::tryFrom((string) $state)?->label() ?? __('common.not_available'))
                    ->badge()
                    ->color(fn (?string $state): string => DepartmentFunction::tryFrom((string) $state)?->color() ?? 'gray')
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
                    EditAction::make()->label(__('configuration.staff.edit'))->icon('heroicon-o-pencil-square'),
                    DeleteAction::make()->label(__('configuration.staff.delete')),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('enable_customer_intake')
                        ->label(__('configuration.staff.bulk_enable_intake'))
                        ->icon('heroicon-o-user-plus')
                        ->color('success')
                        ->form([
                            TextInput::make('customer_capacity')
                                ->label(__('configuration.staff.customer_capacity'))
                                ->numeric()
                                ->minValue(1)
                                ->required()
                                ->hintIcon('heroicon-m-question-mark-circle', __('configuration.staff.customer_capacity_hint')),
                        ])
                        ->action(fn (Collection $records, array $data) => Staff::query()
                            ->whereKey($records->modelKeys())
                            ->update([
                                'can_receive_customers' => true,
                                'customer_capacity' => (int) $data['customer_capacity'],
                            ]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('disable_customer_intake')
                        ->label(__('configuration.staff.bulk_disable_intake'))
                        ->icon('heroicon-o-user-minus')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => Staff::query()->whereKey($records->modelKeys())->update(['can_receive_customers' => false]))
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make()->label(__('configuration.common.delete_selected')),
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
                TernaryFilter::make('has_account')
                    ->label(__('configuration.staff.account_filter'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('user_id'),
                        false: fn (Builder $query): Builder => $query->whereNull('user_id'),
                    ),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AvailabilitiesRelationManager::class,
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
