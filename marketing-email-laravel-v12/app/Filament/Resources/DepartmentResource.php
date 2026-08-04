<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Models\Crm\Department;
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

    protected static ?int $navigationSort = 35;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.crm');
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
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('code')->label(__('field.department_code'))->required()->maxLength(50)->unique(ignoreRecord: true),
            TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
            Textarea::make('description')->label(__('field.description'))->rows(3),
            Toggle::make('is_active')->label(__('field.is_active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            static::makeSelectableColumn(TextColumn::make('name')->label(__('field.name'))->searchable()->sortable()),
            static::makeSelectableColumn(TextColumn::make('code')->label(__('field.department_code'))->searchable()->sortable()->badge()),
            static::makeSelectableColumn(TextColumn::make('positions_count')->label(__('field.positions'))->counts('positions')),
            static::makeSelectableColumn(TextColumn::make('staff_count')->label(__('field.staff_count'))->counts('staff')),
            static::makeSelectableColumn(TextColumn::make('sending_accounts_count')->label(__('field.sending_accounts'))->counts('sendingAccounts')),
            static::makeSelectableColumn(IconColumn::make('is_active')->label(__('field.is_active'))->boolean()),
        ])
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
