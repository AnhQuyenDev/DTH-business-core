<?php

namespace Dth\HumanResource\Filament\Resources;

use Dth\HumanResource\Enums\EmploymentStatus;
use Dth\HumanResource\Filament\Navigation\HumanResourceNavigationGroup;
use Dth\HumanResource\Filament\Resources\EmployeeResource\Pages;
use Dth\HumanResource\Filament\Resources\EmployeeResource\RelationManagers\AvailabilitiesRelationManager;
use Dth\HumanResource\Filament\Resources\EmployeeResource\RelationManagers\BusinessFunctionsRelationManager;
use Dth\HumanResource\Models\Department;
use Dth\HumanResource\Models\Employee;
use Dth\HumanResource\Models\Position;
use Dth\HumanResource\Support\StatusColor;
use Dth\HumanResource\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static string|\UnitEnum|null $navigationGroup = HumanResourceNavigationGroup::HumanResource;
    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.employees', 'Employees', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.employee', 'Employee', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.employee_plural', 'Employees', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('sections.identity', 'Employee identity'))
                ->icon('heroicon-o-user-circle')
                ->schema([
                    TextInput::make('employee_code')
                        ->label(UiText::get('fields.employee_code', 'Employee code'))
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (?Employee $record): bool => $record !== null),
                    TextInput::make('full_name')
                        ->label(UiText::get('fields.full_name', 'Full name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label(UiText::get('common.fields.email', 'Email'))
                        ->email()
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label(UiText::get('fields.phone', 'Phone'))
                        ->tel()
                        ->maxLength(30),
                    Select::make('user_id')
                        ->label(UiText::get('fields.login_account', 'Login account'))
                        ->options(fn (): array => self::userOptions())
                        ->searchable()
                        ->preload()
                        ->placeholder(UiText::get('fields.no_login_account', 'No login account'))
                        ->unique(ignoreRecord: true),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(UiText::get('sections.organization', 'Organization'))
                ->icon('heroicon-o-building-office-2')
                ->schema([
                    Select::make('department_id')
                        ->label(UiText::get('fields.department', 'Department'))
                        ->options(fn (): array => Department::options())
                        ->searchable()
                        ->preload(),
                    Select::make('position_id')
                        ->label(UiText::get('fields.position', 'Job title'))
                        ->options(fn (): array => Position::groupedOptions())
                        ->searchable()
                        ->preload(),
                    Select::make('employment_status')
                        ->label(UiText::get('fields.employment_status', 'Employment status'))
                        ->options(EmploymentStatus::options())
                        ->default(EmploymentStatus::Active->value)
                        ->required()
                        ->native(false),
                    DatePicker::make('started_at')
                        ->label(UiText::get('fields.started_at', 'Start date')),
                    DatePicker::make('ended_at')
                        ->label(UiText::get('fields.ended_at', 'End date')),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee_code')
                    ->label(UiText::get('fields.employee_code', 'Employee code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('full_name')
                    ->label(UiText::get('fields.full_name', 'Full name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('department.name')
                    ->label(UiText::get('fields.department', 'Department'))
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('position.title')
                    ->label(UiText::get('fields.position', 'Job title'))
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('employment_status')
                    ->label(UiText::get('fields.employment_status', 'Employment status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state instanceof EmploymentStatus ? $state : EmploymentStatus::tryFrom((string) $state))?->label() ?? (string) $state)
                    ->color(fn ($state): string => StatusColor::employment($state))
                    ->sortable(),
                TextColumn::make('email')
                    ->label(UiText::get('common.fields.email', 'Email'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('phone')
                    ->label(UiText::get('fields.phone', 'Phone'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('employment_status')
                    ->label(UiText::get('fields.employment_status', 'Employment status'))
                    ->options(EmploymentStatus::options()),
                SelectFilter::make('department_id')
                    ->label(UiText::get('fields.department', 'Department'))
                    ->options(fn (): array => Department::options()),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make()->label(UiText::get('common.actions.view', 'View')),
                    Actions\EditAction::make()->label(UiText::get('common.actions.edit', 'Edit')),
                    Actions\DeleteAction::make()->label(UiText::get('common.actions.delete', 'Delete')),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete'))
                        ->authorizeIndividualRecords(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        $relations = [];

        if (config('dth-human-resource.features.availability', true)) {
            $relations[] = AvailabilitiesRelationManager::class;
        }
        if (config('dth-human-resource.features.business_functions', true)) {
            $relations[] = BusinessFunctionsRelationManager::class;
        }

        return $relations;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }

    private static function userOptions(): array
    {
        $model = (string) config('auth.providers.users.model', \App\Models\User::class);
        if (! class_exists($model)) {
            return [];
        }

        return $model::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->mapWithKeys(fn ($user): array => [
                $user->id => trim((string) $user->name).' · '.(string) $user->email,
            ])
            ->all();
    }
}
