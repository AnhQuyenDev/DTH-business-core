<?php

namespace Dth\HumanResource\Filament\Resources;

use Dth\HumanResource\Enums\BusinessFunction;
use Dth\HumanResource\Filament\Navigation\HumanResourceNavigationGroup;
use Dth\HumanResource\Filament\Resources\DepartmentResource\Pages;
use Dth\HumanResource\Models\Department;
use Dth\HumanResource\Support\StatusColor;
use Dth\HumanResource\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';
    protected static string|\UnitEnum|null $navigationGroup = HumanResourceNavigationGroup::HumanResource;
    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.departments', 'Departments', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.department', 'Department', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.department_plural', 'Departments', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('sections.department', 'Department'))
                ->schema([
                    TextInput::make('code')
                        ->label(UiText::get('fields.department_code', 'Department code'))
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (?Department $record): bool => $record !== null),
                    TextInput::make('name')
                        ->label(UiText::get('common.fields.name', 'Name'))
                        ->required()
                        ->maxLength(255),
                    Select::make('function_key')
                        ->label(UiText::get('fields.business_function', 'Business function'))
                        ->options(BusinessFunction::options())
                        ->searchable()
                        ->native(false),
                    Select::make('color')
                        ->label(UiText::get('fields.color', 'Color'))
                        ->options([
                            'primary' => UiText::get('colors.primary', 'Primary'),
                            'info' => UiText::get('colors.info', 'Info'),
                            'success' => UiText::get('colors.success', 'Success'),
                            'warning' => UiText::get('colors.warning', 'Warning'),
                            'danger' => UiText::get('colors.danger', 'Danger'),
                            'gray' => UiText::get('colors.gray', 'Gray'),
                        ])
                        ->default('gray')
                        ->native(false),
                    TextInput::make('sort_order')
                        ->label(UiText::get('fields.sort_order', 'Sort order'))
                        ->numeric()
                        ->default(0)
                        ->minValue(0),
                    Toggle::make('is_active')
                        ->label(UiText::get('common.status.active', 'Active'))
                        ->default(true),
                    Textarea::make('description')
                        ->label(UiText::get('common.fields.description', 'Description'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(UiText::get('fields.department_code', 'Department code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(UiText::get('common.fields.name', 'Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('function_key')
                    ->label(UiText::get('fields.business_function', 'Business function'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state instanceof BusinessFunction ? $state : BusinessFunction::tryFrom((string) $state))?->label() ?? UiText::get('common.fields.not_available', 'N/A'))
                    ->color(fn ($state): string => StatusColor::businessFunction($state)),
                TextColumn::make('employees_count')
                    ->label(UiText::get('fields.employee_count', 'Employees'))
                    ->counts('employees')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(UiText::get('common.status.active', 'Active'))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('function_key')
                    ->label(UiText::get('fields.business_function', 'Business function'))
                    ->options(BusinessFunction::options()),
                TernaryFilter::make('is_active')
                    ->label(UiText::get('common.status.active', 'Active')),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
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
            ->defaultSort('sort_order');
    }

    public static function canDelete(Model $record): bool
    {
        return parent::canDelete($record) && ! $record->employees()->exists();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}
