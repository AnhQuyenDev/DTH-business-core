<?php

namespace App\Filament\Resources\Sales;

use App\Enums\Sales\AudienceType;
use App\Enums\Sales\DiscountType;
use App\Enums\Sales\PriceBookStatus;
use App\Enums\Sales\TaxMode;
use App\Filament\Resources\Sales\PriceBookResource\Pages;
use App\Filament\Resources\Sales\PriceBookResource\RelationManagers\PriceBookAccessRuleRelationManager;
use App\Models\Sales\PriceBook;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PriceBookResource extends Resource
{
    protected static ?string $model = PriceBook::class;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.sales');
    }

    public static function getModelLabel(): string
    {
        return __('resource.price_book.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.price_book.plural');
    }

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?int $navigationSort = 40;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('sales.view-price-books') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('sales.manage-price-books') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('sales.manage-price-books') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('sales.manage-price-books') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('section.price_book_details'))
                ->description(__('helper.price_book_details'))
                ->columns(12)
                ->schema([
                    TextInput::make('price_book_code')
                        ->label(__('field.price_book_code'))
                        ->required()
                        ->maxLength(30)
                        ->unique(ignoreRecord: true)
                        ->columnSpan(['default' => 12, 'md' => 4]),
                    TextInput::make('name')
                        ->label(__('field.name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(['default' => 12, 'md' => 8]),
                    Textarea::make('description')
                        ->label(__('field.description'))
                        ->rows(3)
                        ->columnSpanFull(),
                    Select::make('audience_type')
                        ->label(__('field.audience_type'))
                        ->options(AudienceType::options())
                        ->native(false)
                        ->default('both')
                        ->required()
                        ->columnSpan(['default' => 12, 'md' => 4]),
                    Select::make('currency')
                        ->label(__('field.currency'))
                        ->options(['VND' => 'VND', 'USD' => 'USD'])
                        ->native(false)
                        ->default('VND')
                        ->required()
                        ->columnSpan(['default' => 12, 'md' => 4]),
                    Select::make('tax_mode')
                        ->label(__('field.tax_mode'))
                        ->options(TaxMode::options())
                        ->native(false)
                        ->default('exclusive')
                        ->required()
                        ->helperText(__('helper.tax_mode'))
                        ->columnSpan(['default' => 12, 'md' => 4]),
                    DatePicker::make('valid_from')
                        ->label(__('field.valid_from'))
                        ->required()
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->columnSpan(['default' => 12, 'md' => 3]),
                    DatePicker::make('valid_until')
                        ->label(__('field.valid_until'))
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->minDate(fn (Get $get) => $get('valid_from'))
                        ->columnSpan(['default' => 12, 'md' => 3]),
                    Select::make('status')
                        ->label(__('field.status'))
                        ->options(PriceBookStatus::options())
                        ->native(false)
                        ->default('draft')
                        ->required()
                        ->columnSpan(['default' => 12, 'md' => 3]),
                    Toggle::make('is_default')
                        ->label(__('field.is_default'))
                        ->helperText(__('helper.price_book_default'))
                        ->columnSpan(['default' => 12, 'md' => 3]),
                ]),

            Section::make(__('section.price_book_items'))
                ->description(__('helper.price_book_items'))
                ->schema([
                    Repeater::make('items')
                        ->relationship('items')
                        ->label('')
                        ->schema([
                            Select::make('service_package_id')
                                ->label(__('resource.service_package.singular'))
                                ->relationship('servicePackage', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->columnSpan(['default' => 12, 'lg' => 4]),
                            TextInput::make('unit_price')
                                ->label(__('field.unit_price'))
                                ->numeric()
                                ->required()
                                ->minValue(0.01)
                                ->prefix('VND')
                                ->columnSpan(['default' => 12, 'sm' => 6, 'lg' => 2]),
                            TextInput::make('vat_rate')
                                ->label(__('field.vat_rate'))
                                ->numeric()
                                ->default(10)
                                ->minValue(0)
                                ->maxValue(100)
                                ->suffix('%')
                                ->columnSpan(['default' => 12, 'sm' => 6, 'lg' => 2]),
                            TextInput::make('minimum_quantity')
                                ->label(__('field.minimum_quantity'))
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required()
                                ->columnSpan(['default' => 12, 'sm' => 6, 'lg' => 2]),
                            TextInput::make('maximum_quantity')
                                ->label(__('field.maximum_quantity'))
                                ->numeric()
                                ->nullable()
                                ->minValue(fn (Get $get): int => max(1, (int) ($get('minimum_quantity') ?? 1)))
                                ->columnSpan(['default' => 12, 'sm' => 6, 'lg' => 2]),
                            Select::make('default_discount_type')
                                ->label(__('field.default_discount_type'))
                                ->options(DiscountType::options())
                                ->native(false)
                                ->nullable()
                                ->live()
                                ->required(
                                    fn (Get $get): bool => (float) ($get('default_discount_value') ?? 0) > 0
                                        || (float) ($get('maximum_discount_value') ?? 0) > 0
                                )
                                ->helperText(__('helper.price_book_discount_type'))
                                ->columnSpan(['default' => 12, 'md' => 4]),
                            TextInput::make('default_discount_value')
                                ->label(__('field.default_discount_value'))
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->maxValue(
                                    fn (Get $get): ?float => filled($get('maximum_discount_value'))
                                        ? (float) $get('maximum_discount_value')
                                        : ($get('default_discount_type') === DiscountType::Percentage->value ? 100 : null)
                                )
                                ->columnSpan(['default' => 12, 'md' => 4]),
                            TextInput::make('maximum_discount_value')
                                ->label(__('field.maximum_discount_value'))
                                ->numeric()
                                ->default(0)
                                ->minValue(0)
                                ->maxValue(
                                    fn (Get $get): ?float => $get('default_discount_type') === DiscountType::Percentage->value
                                        ? 100
                                        : null
                                )
                                ->live()
                                ->columnSpan(['default' => 12, 'md' => 4]),
                            Textarea::make('scope_override')
                                ->label(__('field.scope_override'))
                                ->rows(2)
                                ->columnSpan(['default' => 12, 'xl' => 6]),
                            Textarea::make('terms_override')
                                ->label(__('field.terms_override'))
                                ->rows(2)
                                ->columnSpan(['default' => 12, 'xl' => 6]),
                            Hidden::make('sort_order')->default(0),
                        ])
                        ->columns(12)
                        ->defaultItems(0)
                        ->addActionLabel(__('action.add_item'))
                        ->deleteAction(fn (Action $action) => $action->label(__('action.delete_item')))
                        ->addable()
                        ->deletable()
                        ->reorderable()
                        ->collapsible(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('price_book_code')->label(__('field.price_book_code'))->searchable()->sortable(),
            TextColumn::make('name')->label(__('field.name'))->searchable()->sortable(),
            TextColumn::make('audience_type')->label(__('field.audience_type'))->badge()
                ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                ->color(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color') ? $state->color() : 'gray'),
            TextColumn::make('currency')->label(__('field.currency')),
            TextColumn::make('status')->label(__('field.status'))->badge()
                ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                ->color(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color') ? $state->color() : 'gray'),
            IconColumn::make('is_default')->label(__('field.is_default'))->boolean(),
            TextColumn::make('valid_from')
                ->label(__('field.valid_from'))
                ->date('d/m/Y')
                ->sortable(),
            TextColumn::make('valid_until')
                ->label(__('field.valid_until'))
                ->date('d/m/Y')
                ->sortable(),
            TextColumn::make('created_at')
                ->label(__('field.created_at'))
                ->dateTime('d/m/Y H:i')
                ->sortable()
                ->toggleable(),        ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('action.bulk_delete'))
                        ->modalHeading(__('action.bulk_delete'))
                        ->requiresConfirmation()
                        ->visible(
                            fn (): bool => auth()->user()?->can(
                                'sales.manage-price-books'
                            ) ?? false
                        ),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PriceBookAccessRuleRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPriceBooks::route('/'),
            'create' => Pages\CreatePriceBook::route('/create'),
            'edit' => Pages\EditPriceBook::route('/{record}/edit'),
        ];
    }
}