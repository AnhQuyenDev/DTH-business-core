<?php

namespace App\Filament\Resources\Sales\PriceBookResource\RelationManagers;

use App\Enums\Sales\DiscountType;
use App\Enums\Sales\PriceBookAccessType;
use App\Models\Crm\Department;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PriceBookAccessRuleRelationManager extends RelationManager
{
    protected static string $relationship = 'accessRules';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('relation.title.price_book_access_rules');
    }

    public function getLabel(): string
    {
        return __('relation.title.price_book_access_rules');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('access_type')
                ->label(__('field.access_type'))
                ->options(PriceBookAccessType::options())
                ->required(),
            Select::make('role')
                ->label(__('field.role'))
                ->options([
                    'admin' => __('enum.role.admin'),
                    'manager' => __('field.manager'),
                    'staff' => __('field.staff'),
                ])
                ->nullable(),
            Select::make('department')
                ->label(__('field.department'))
                ->options(Department::codeOptions())
                ->searchable()
                ->nullable(),
            Select::make('staff_id')
                ->label(__('field.staff'))
                ->relationship('staff', 'full_name')
                ->nullable()
                ->searchable(),
            Toggle::make('can_view')
                ->label(__('field.can_view'))
                ->default(true),
            Toggle::make('can_create_quotation')
                ->label(__('field.can_create_quotation')),
            Select::make('discount_limit_type')
                ->label(__('field.discount_limit_type'))
                ->options(DiscountType::options())
                ->nullable(),
            TextInput::make('discount_limit_value')
                ->label(__('field.discount_limit_value'))
                ->numeric()
                ->nullable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('access_type')->label(__('field.access_type')),
                TextColumn::make('role')->label(__('field.role')),
                TextColumn::make('department')->label(__('field.department')),
                TextColumn::make('staff.full_name')->label(__('field.staff')),
                IconColumn::make('can_view')->label(__('field.view'))->boolean(),
                IconColumn::make('can_create_quotation')->label(__('field.can_create_quotation'))->boolean(),
                TextColumn::make('discount_limit_value')->label(__('field.discount_limit_value')),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([ActionGroup::make([
                EditAction::make(),
                DeleteAction::make(),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton()]);
    }
}
