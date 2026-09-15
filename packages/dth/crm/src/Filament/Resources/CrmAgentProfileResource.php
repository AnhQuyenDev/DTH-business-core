<?php

namespace Dth\Crm\Filament\Resources;

use Dth\Crm\Filament\Navigation\CrmNavigationGroup;
use Dth\Crm\Filament\Resources\CrmAgentProfileResource\Pages;
use Dth\Crm\Models\CrmAgentProfile;
use Dth\Crm\Support\UiText;
use Dth\HumanResource\Enums\EmploymentStatus;
use Dth\HumanResource\Models\Employee;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CrmAgentProfileResource extends Resource
{
    protected static ?string $model = CrmAgentProfile::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';
    protected static string|\UnitEnum|null $navigationGroup = CrmNavigationGroup::Crm;
    protected static ?int $navigationSort = 70;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.agent_profiles', 'CRM team settings', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.agent_profile', 'CRM team member', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.agent_profiles', 'CRM team settings', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('sections.agent_profile', 'CRM assignment profile'))
                ->description(UiText::get('sections.agent_profile_description', 'Employee identity, department, job title and work availability are managed by Human Resource. CRM only stores assignment capacity.'))
                ->schema([
                    Select::make('employee_id')
                        ->label(UiText::get('fields.hr_employee', 'HR employee'))
                        ->options(fn (): array => Employee::query()
                            ->where('employment_status', '!=', EmploymentStatus::Resigned->value)
                            ->orderBy('full_name')
                            ->get(['id', 'employee_code', 'full_name'])
                            ->mapWithKeys(fn (Employee $employee): array => [
                                $employee->id => $employee->displayLabel(),
                            ])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->unique(ignoreRecord: true),
                    Toggle::make('assignment_enabled')
                        ->label(UiText::get('fields.assignment_enabled', 'Can receive CRM assignments'))
                        ->default(true),
                    TextInput::make('lead_capacity')
                        ->label(UiText::get('fields.lead_capacity', 'Lead capacity'))
                        ->numeric()
                        ->default(50)
                        ->minValue(0)
                        ->required(),
                    TextInput::make('customer_capacity')
                        ->label(UiText::get('fields.customer_capacity', 'Customer capacity'))
                        ->numeric()
                        ->minValue(0),
                    TextInput::make('distribution_weight')
                        ->label(UiText::get('fields.distribution_weight', 'Distribution weight'))
                        ->numeric()
                        ->default(1)
                        ->minValue(0.01)
                        ->required(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.employee_code')
                    ->label(UiText::get('fields.employee_code', 'Employee code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.full_name')
                    ->label(UiText::get('fields.agent_name', 'Employee'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee.department.name')
                    ->label(UiText::get('fields.department', 'Department'))
                    ->placeholder('—'),
                TextColumn::make('employee.position.title')
                    ->label(UiText::get('fields.position', 'Job title'))
                    ->placeholder('—'),
                TextColumn::make('employee.employment_status')
                    ->label(UiText::get('fields.employment_status', 'Employment status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state instanceof EmploymentStatus ? $state : EmploymentStatus::tryFrom((string) $state))?->label() ?? (string) $state)
                    ->color(fn ($state): string => ($state instanceof EmploymentStatus ? $state : EmploymentStatus::tryFrom((string) $state))?->color() ?? 'gray'),
                IconColumn::make('assignment_enabled')
                    ->label(UiText::get('fields.assignment_enabled_short', 'Receive work'))
                    ->boolean(),
                TextColumn::make('lead_capacity')
                    ->label(UiText::get('fields.lead_capacity', 'Lead capacity'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('distribution_weight')
                    ->label(UiText::get('fields.distribution_weight', 'Weight'))
                    ->numeric(decimalPlaces: 2)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('assignment_enabled')
                    ->label(UiText::get('fields.assignment_enabled', 'Can receive CRM assignments')),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrmAgentProfiles::route('/'),
            'create' => Pages\CreateCrmAgentProfile::route('/create'),
            'view' => Pages\ViewCrmAgentProfile::route('/{record}'),
            'edit' => Pages\EditCrmAgentProfile::route('/{record}/edit'),
        ];
    }
}
