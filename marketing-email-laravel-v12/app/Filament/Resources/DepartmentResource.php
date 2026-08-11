<?php

namespace App\Filament\Resources;

use App\Enums\Crm\DepartmentFunction;
use App\Filament\Resources\DepartmentResource\Pages;
use App\Models\Crm\Department;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.department.singular');
    }

    public static function getModelLabel(): string
    {
        return __('resource.department.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.department.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('system.manage-organization') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('system.manage-organization') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('system.manage-organization') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('system.manage-organization') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('resource.department.singular'))
                ->schema([
                    TextInput::make('code')->label(__('field.department_code'))->required()->maxLength(50)->unique(ignoreRecord: true),
                    TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
                    Select::make('function_key')->label(__('field.department_function'))->options(DepartmentFunction::options())->native(false)->required()->helperText(__('helper.department_function')),
                    Select::make('color')->label(__('field.color'))->options(Department::colorOptions())->native(false)->default('gray')->required()->helperText(__('helper.department_color')),
                    Textarea::make('description')->label(__('field.description'))->rows(3)->columnSpanFull(),
                    Toggle::make('is_active')->label(__('field.is_active'))->default(true),
                ])->columns(['default' => 1, 'md' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            static::makeSelectableColumn(
                TextColumn::make('name')
                    ->label(__('field.name'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (Department $record): string => $record->color ?? 'gray')
            ),
            static::makeSelectableColumn(
                TextColumn::make('code')
                    ->label(__('field.department_code'))
                    ->searchable()
                    ->sortable()
            ),
            static::makeSelectableColumn(
                TextColumn::make('function_key')
                    ->label(__('field.department_function'))
                    ->badge()
                    ->formatStateUsing(
                        fn (?string $state): string => $state
                            ? DepartmentFunction::tryFrom($state)?->label()
                                ?? __('common.not_available')
                            : __('common.not_available')
                    )
                    ->color(
                        fn (?string $state): string => DepartmentFunction::tryFrom((string) $state)?->color()
                            ?? 'gray'
                    )
            ),
            static::makeSelectableColumn(
                TextColumn::make('positions_count')
                    ->label(__('field.positions'))
                    ->counts('positions')
            ),
            static::makeSelectableColumn(
                TextColumn::make('staff_count')
                    ->label(__('field.staff_count'))
                    ->counts('staff')
            ),
            static::makeSelectableColumn(
                TextColumn::make('sending_accounts_count')
                    ->label(__('field.sending_accounts'))
                    ->counts('sendingAccounts')
            ),
            static::makeSelectableColumn(
                IconColumn::make('is_active')
                    ->label(__('field.is_active'))
                    ->boolean()
            ),
        ])
            ->defaultSort('name')
            ->actions([ActionGroup::make([
                EditAction::make(),
                DeleteAction::make(),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton()]);
    }

    private static function makeSelectableColumn(Column $column): Column
    {
        return $column->action(function (Department $record, $livewire): void {
            $livewire->selectDepartment($record->id);
        });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
        ];
    }
}
