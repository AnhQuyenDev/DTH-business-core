<?php

namespace Dth\Commercial\Filament\Resources;

use Dth\Commercial\Enums\AudienceType;
use Dth\Commercial\Enums\BillingPeriodUnit;
use Dth\Commercial\Enums\ServiceStatus;
use Dth\Commercial\Filament\Navigation\CommercialNavigationGroup;
use Dth\Commercial\Filament\Resources\ProductResource\Pages;
use Dth\Commercial\Models\Product;
use Dth\Commercial\Models\Service;
use Dth\Commercial\Support\CommercialAuthorization;
use Dth\Commercial\Support\StatusColor;
use Dth\Commercial\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductResource extends Resource
{
    public const NAVIGATION_ICON = 'heroicon-o-cube';

    protected static ?string $model = Product::class;
    protected static string|\BackedEnum|null $navigationIcon = self::NAVIGATION_ICON;
    protected static string|\UnitEnum|null $navigationGroup = CommercialNavigationGroup::Commercial;
    protected static ?int $navigationSort = 20;
    protected static ?string $slug = 'commercial-products';

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.products', 'Products', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.product', 'Product', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.products', 'Products', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('sections.product_basic', 'Product information'))
                ->description(UiText::get('sections.product_basic_help', 'A product is a sellable item inside one service category. Customers may buy products individually without choosing a bundle.'))
                ->schema([
                    Select::make('service_id')
                        ->label(UiText::get('models.service', 'Service'))
                        ->options(fn (): array => Service::query()->where('status', 'active')->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->helperText(UiText::get('fields.product_service_help', 'Choose the service category that owns this product. Bundles can later combine products from different services.'))
                        ->required(),
                    TextInput::make('name')
                        ->label(UiText::get('common.fields.name', 'Name'))
                        ->placeholder(UiText::get('fields.product_name_placeholder', 'Example: VPS 4 vCPU / 8 GB RAM'))
                        ->helperText(UiText::get('fields.product_name_help', 'Use the customer-facing product or plan name that can be sold as an individual line item.'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('product_code')
                        ->label(UiText::get('fields.product_code', 'Product code'))
                        ->placeholder('VPS-4C8G')
                        ->helperText(UiText::get('fields.product_code_help', 'Stable SKU-like code used by imports, bundles, opportunities and future orders.'))
                        ->required()
                        ->maxLength(50)
                        ->unique(ignoreRecord: true),
                    Select::make('status')
                        ->label(UiText::get('common.fields.status', 'Status'))
                        ->options(ServiceStatus::options())
                        ->default(ServiceStatus::Active->value)
                        ->native(false)
                        ->required(),
                    Textarea::make('description')
                        ->label(UiText::get('common.fields.description', 'Description'))
                        ->placeholder(UiText::get('fields.product_description_placeholder', 'Describe what the customer receives and the main limits or included capacity.'))
                        ->helperText(UiText::get('fields.product_description_help', 'Keep the description reusable in bundles, opportunities, quotations and Marketing.'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(UiText::get('sections.product_rules', 'Commercial rules'))
                ->description(UiText::get('sections.product_rules_help', 'Define the target audience and quantity unit independently from pricing.'))
                ->schema([
                    Select::make('audience_type')
                        ->label(UiText::get('fields.audience', 'Audience'))
                        ->options(AudienceType::options())
                        ->default(AudienceType::Both->value)
                        ->native(false)
                        ->required(),
                    TextInput::make('unit')
                        ->label(UiText::get('fields.unit', 'Unit'))
                        ->default('item')
                        ->placeholder('user / mailbox / VPS / license / hour')
                        ->helperText(UiText::get('fields.product_unit_help', 'Commercial quantity unit, for example mailbox, user, VPS, domain, license, hour or project.'))
                        ->maxLength(50)
                        ->required(),
                    TextInput::make('default_quantity')
                        ->label(UiText::get('fields.default_quantity', 'Default quantity'))
                        ->numeric()
                        ->minValue(0.01)
                        ->step(0.01)
                        ->default(1)
                        ->required(),
                    TextInput::make('sort_order')
                        ->label(UiText::get('fields.sort_order', 'Sort order'))
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(UiText::get('sections.product_prices', 'Price list'))
                ->description(UiText::get('sections.product_prices_help', 'A product may have multiple prices by currency or billing cycle. Mark one default selling price for each currency.'))
                ->schema([
                    Repeater::make('prices')
                        ->relationship()
                        ->label(UiText::get('models.product_prices', 'Product prices'))
                        ->addActionLabel(UiText::get('actions.add_price', 'Add price'))
                        ->defaultItems(1)
                        ->schema([
                            TextInput::make('price_code')
                                ->label(UiText::get('fields.price_code', 'Price code'))
                                ->placeholder('LIST-VND-MONTHLY')
                                ->helperText(UiText::get('fields.price_code_help', 'Optional stable code for price-book integrations. If blank, the system generates one from currency and billing cycle.'))
                                ->maxLength(50),
                            TextInput::make('currency')
                                ->label(UiText::get('fields.currency', 'Currency'))
                                ->default('VND')
                                ->minLength(3)
                                ->maxLength(3)
                                ->required(),
                            TextInput::make('billing_period')
                                ->label(UiText::get('fields.billing_period', 'Billing period'))
                                ->numeric()
                                ->minValue(1)
                                ->placeholder('1'),
                            Select::make('billing_period_unit')
                                ->label(UiText::get('fields.billing_period_unit', 'Billing unit'))
                                ->options(BillingPeriodUnit::options())
                                ->native(false),
                            TextInput::make('price')
                                ->label(UiText::get('fields.price', 'Selling price'))
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01)
                                ->placeholder('0'),
                            TextInput::make('renewal_price')
                                ->label(UiText::get('fields.renewal_price', 'Renewal price'))
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01),
                            TextInput::make('setup_fee')
                                ->label(UiText::get('fields.setup_fee', 'Setup fee'))
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01)
                                ->default(0),
                            Select::make('status')
                                ->label(UiText::get('common.fields.status', 'Status'))
                                ->options(ServiceStatus::options())
                                ->default(ServiceStatus::Active->value)
                                ->native(false)
                                ->required(),
                            Toggle::make('is_default')
                                ->label(UiText::get('fields.default_price', 'Default price'))
                                ->helperText(UiText::get('fields.default_price_help', 'The default price is used when this product is added to an opportunity or a component-priced bundle.')),
                            DatePicker::make('valid_from')
                                ->label(UiText::get('fields.valid_from', 'Valid from'))
                                ->native(false)
                                ->displayFormat('d/m/Y'),
                            DatePicker::make('valid_until')
                                ->label(UiText::get('fields.valid_until', 'Valid until'))
                                ->native(false)
                                ->displayFormat('d/m/Y'),
                        ])
                        ->columns(4)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product_code')
                    ->label(UiText::get('fields.product_code', 'Product code'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('name')
                    ->label(UiText::get('common.fields.name', 'Name'))
                    ->description(fn (Product $record): ?string => filled($record->description) ? str($record->description)->limit(52)->toString() : null)
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('service.name')
                    ->label(UiText::get('models.service', 'Service'))
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('defaultPrice.price')
                    ->label(UiText::get('fields.default_price', 'Default price'))
                    ->formatStateUsing(function ($state, Product $record): string {
                        $price = $record->defaultPrice;
                        if (! $price || $price->price === null) {
                            return UiText::get('fields.contact_for_price', 'Contact');
                        }
                        $currency = strtoupper((string) ($price->currency ?: 'VND'));
                        $decimals = $currency === 'VND' ? 0 : 2;
                        return number_format((float) $price->price, $decimals, ',', '.').' '.$currency;
                    }),
                TextColumn::make('prices_count')
                    ->counts('prices')
                    ->label(UiText::get('fields.price_count', 'Prices'))
                    ->alignCenter(),
                TextColumn::make('audience_type')
                    ->label(UiText::get('fields.audience', 'Audience'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => AudienceType::options()[$state instanceof \BackedEnum ? $state->value : (string) $state] ?? (string) $state),
                TextColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => UiText::status($state))
                    ->color(fn ($state): string => StatusColor::for($state)),
            ])
            ->filters([
                SelectFilter::make('service_id')
                    ->label(UiText::get('models.service', 'Service'))
                    ->options(fn (): array => Service::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
                SelectFilter::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->options(ServiceStatus::options()),
                SelectFilter::make('audience_type')
                    ->label(UiText::get('fields.audience', 'Audience'))
                    ->options(AudienceType::options()),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\EditAction::make()->label(UiText::get('common.actions.edit', 'Edit'))->icon('heroicon-o-pencil-square'),
                    Actions\DeleteAction::make()->label(UiText::get('common.actions.delete', 'Delete'))->icon('heroicon-o-trash'),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->label(UiText::get('common.actions.delete', 'Delete'))->authorizeIndividualRecords(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return app(CommercialAuthorization::class)->allows('view');
    }

    public static function canCreate(): bool
    {
        return app(CommercialAuthorization::class)->allows('manage-catalog');
    }

    public static function canEdit(Model $record): bool
    {
        return app(CommercialAuthorization::class)->allows('manage-catalog');
    }

    public static function canDelete(Model $record): bool
    {
        return app(CommercialAuthorization::class)->allows('manage-catalog');
    }
}
