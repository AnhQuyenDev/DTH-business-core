<?php

namespace App\Filament\Resources\Sales\PriceBookResource\RelationManagers;

use App\Enums\Sales\DiscountType;
use App\Enums\Sales\PriceBookAccessType;
use App\Enums\UserRole;
use App\Models\Crm\Department;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
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
            Section::make(__('uiux.form.access_scope'))
                ->schema([
                    Select::make('access_type')
                        ->label(__('field.access_type'))
                        ->options(PriceBookAccessType::options())
                        ->native(false)
                        ->live()
                        ->required(),
                    Select::make('role')
                        ->label(__('field.system_role'))
                        ->options(UserRole::options())
                        ->native(false)
                        ->visible(fn (Get $get): bool => $get('access_type') === PriceBookAccessType::Role->value)
                        ->required(fn (Get $get): bool => $get('access_type') === PriceBookAccessType::Role->value),
                    Select::make('department')
                        ->label(__('field.department'))
                        ->options(Department::codeOptions())
                        ->searchable()
                        ->native(false)
                        ->visible(fn (Get $get): bool => $get('access_type') === PriceBookAccessType::Department->value)
                        ->required(fn (Get $get): bool => $get('access_type') === PriceBookAccessType::Department->value),
                    Select::make('staff_id')
                        ->label(__('field.staff'))
                        ->relationship('staff', 'full_name', modifyQueryUsing: fn ($query) => $query->whereHas('user', fn ($q) => $q->where('is_active', true)))
                        ->searchable()
                        ->preload()
                        ->visible(fn (Get $get): bool => $get('access_type') === PriceBookAccessType::Staff->value)
                        ->required(fn (Get $get): bool => $get('access_type') === PriceBookAccessType::Staff->value),
                ])
                ->columns(['default' => 1, 'md' => 2]),
            Section::make(__('uiux.form.permissions'))
                ->schema([
                    Toggle::make('can_view')
                        ->label(__('field.can_view'))
                        ->default(true),
                    Toggle::make('can_create_quotation')
                        ->label(__('field.can_create_quotation')),
                ])
                ->columns(['default' => 1, 'md' => 2]),
            Section::make(__('uiux.form.discount_policy'))
                ->schema([
                    Select::make('discount_limit_type')
                        ->label(__('field.discount_limit_type'))
                        ->options(DiscountType::options())
                        ->native(false)
                        ->nullable()
                        ->live(),
                    TextInput::make('discount_limit_value')
                        ->label(__('field.discount_limit_value'))
                        ->numeric()
                        ->minValue(0)
                        ->visible(fn (Get $get): bool => filled($get('discount_limit_type')))
                        ->nullable(),
                ])
                ->columns(['default' => 1, 'md' => 2])
                ->collapsible(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('access_type')
                    ->label(__('field.access_type'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => PriceBookAccessType::tryFrom((string) $state)?->label() ?? '—'),
                TextColumn::make('role')
                    ->label(__('field.role'))
                    ->formatStateUsing(fn (?string $state): string => filled($state)
                        ? (UserRole::tryFrom($state)?->label() ?? $state)
                        : '—'),
                TextColumn::make('department')
                    ->label(__('field.department'))
                    ->formatStateUsing(fn (?string $state): string => filled($state)
                        ? (Department::codeOptions()[$state] ?? $state)
                        : '—'),
                TextColumn::make('staff.full_name')->label(__('field.staff')),
                IconColumn::make('can_view')->label(__('field.view'))->boolean(),
                IconColumn::make('can_create_quotation')->label(__('field.can_create_quotation'))->boolean(),
                TextColumn::make('discount_limit_value')->label(__('field.discount_limit_value')),
            ])
            ->headerActions([
                CreateAction::make()->label(__('action.add_access_rule')),
            ])
            ->actions([ActionGroup::make([
                EditAction::make(),
                DeleteAction::make(),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton()]);
    }
}
