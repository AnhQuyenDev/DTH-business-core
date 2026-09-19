<?php

namespace Dth\Commercial\Filament\Resources;

use Dth\Commercial\Enums\AudienceType;
use Dth\Commercial\Enums\BillingPeriodUnit;
use Dth\Commercial\Enums\BundlePricingType;
use Dth\Commercial\Enums\ServiceStatus;
use Dth\Commercial\Filament\Navigation\CommercialNavigationGroup;
use Dth\Commercial\Filament\Resources\BundleResource\Pages;
use Dth\Commercial\Models\Bundle;
use Dth\Commercial\Models\Product;
use Dth\Commercial\Models\ProductPrice;
use Dth\Commercial\Models\Service;
use Dth\Commercial\Support\CommercialAuthorization;
use Dth\Commercial\Support\StatusColor;
use Dth\Commercial\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class BundleResource extends Resource
{
    public const NAVIGATION_ICON = 'heroicon-o-gift';

    protected static ?string $model = Bundle::class;
    protected static string|\BackedEnum|null $navigationIcon = self::NAVIGATION_ICON;
    protected static string|\UnitEnum|null $navigationGroup = CommercialNavigationGroup::Commercial;
    protected static ?int $navigationSort = 30;
    protected static ?string $slug = 'commercial-service-packages';

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.packages', 'Service bundles', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.package', 'Service bundle', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.packages', 'Service bundles', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('sections.bundle_basic', 'Bundle information'))
                ->description(UiText::get('sections.bundle_basic_help', 'A bundle is a predefined combination of products. Its products may belong to the same service or to different services.'))
                ->schema([
                    Select::make('primary_service_id')
                        ->label(UiText::get('fields.primary_service', 'Primary service'))
                        ->options(fn (): array => Service::query()->where('status', 'active')->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->helperText(UiText::get('fields.primary_service_help', 'Optional grouping service for navigation and Marketing. It does not restrict which products may be included in the bundle.')),
                    TextInput::make('name')
                        ->label(UiText::get('common.fields.name', 'Name'))
                        ->placeholder(UiText::get('fields.bundle_name_placeholder', 'Example: Startup Website bundle'))
                        ->helperText(UiText::get('fields.bundle_name_help', 'Use a customer-facing name for the predefined combination.'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('bundle_code')
                        ->label(UiText::get('fields.bundle_code', 'Bundle code'))
                        ->placeholder('BND-STARTUP-WEB')
                        ->helperText(UiText::get('fields.bundle_code_help', 'Stable business code used by imports, campaigns, opportunities and future orders.'))
                        ->required()
                        ->maxLength(50)
                        ->unique(ignoreRecord: true),
                    Select::make('status')
                        ->label(UiText::get('common.fields.status', 'Status'))
                        ->options(ServiceStatus::options())
                        ->default(ServiceStatus::Active->value)
                        ->native(false)
                        ->required(),
                    Select::make('audience_type')
                        ->label(UiText::get('fields.audience', 'Audience'))
                        ->options(AudienceType::options())
                        ->default(AudienceType::Both->value)
                        ->native(false)
                        ->required(),
                    TextInput::make('sort_order')
                        ->label(UiText::get('fields.sort_order', 'Sort order'))
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                    Textarea::make('description')
                        ->label(UiText::get('common.fields.description', 'Description'))
                        ->placeholder(UiText::get('fields.bundle_description_placeholder', 'Explain the use case and value of buying these products together.'))
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(UiText::get('sections.bundle_pricing', 'Bundle pricing'))
                ->description(UiText::get('sections.bundle_pricing_help', 'Use component pricing to sum product prices, or set one fixed promotional price for the whole bundle.'))
                ->schema([
                    Select::make('pricing_type')
                        ->label(UiText::get('fields.pricing_type', 'Pricing type'))
                        ->options(BundlePricingType::options())
                        ->default(BundlePricingType::ComponentSum->value)
                        ->live()
                        ->native(false)
                        ->required(),
                    TextInput::make('currency')
                        ->label(UiText::get('fields.currency', 'Currency'))
                        ->default('VND')
                        ->minLength(3)
                        ->maxLength(3)
                        ->live(onBlur: true)
                        ->required(),
                    TextInput::make('fixed_price')
                        ->label(UiText::get('fields.bundle_fixed_price', 'Fixed bundle price'))
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->visible(fn (Get $get): bool => $get('pricing_type') === BundlePricingType::Fixed->value),
                    TextInput::make('renewal_price')
                        ->label(UiText::get('fields.renewal_price', 'Renewal price'))
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->visible(fn (Get $get): bool => $get('pricing_type') === BundlePricingType::Fixed->value),
                    TextInput::make('setup_fee')
                        ->label(UiText::get('fields.setup_fee', 'Setup fee'))
                        ->numeric()
                        ->minValue(0)
                        ->step(0.01)
                        ->default(0)
                        ->visible(fn (Get $get): bool => $get('pricing_type') === BundlePricingType::Fixed->value),
                    TextInput::make('billing_period')
                        ->label(UiText::get('fields.billing_period', 'Billing period'))
                        ->numeric()
                        ->minValue(1),
                    Select::make('billing_period_unit')
                        ->label(UiText::get('fields.billing_period_unit', 'Billing unit'))
                        ->options(BillingPeriodUnit::options())
                        ->native(false),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make(UiText::get('sections.bundle_items', 'Products in bundle'))
                ->description(UiText::get('sections.bundle_items_help', 'Add any active product from any service. A bundle is only a shortcut offer; customers can still buy each product separately.'))
                ->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->label(UiText::get('models.bundle_items', 'Bundle products'))
                        ->addActionLabel(UiText::get('actions.add_bundle_product', 'Add product'))
                        ->minItems(1)
                        ->schema([
                            Select::make('product_id')
                                ->label(UiText::get('models.product', 'Product'))
                                ->options(fn (): array => Product::query()
                                    ->with('service:id,name')
                                    ->where('status', 'active')
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (Product $product): array => [
                                        $product->getKey() => trim(($product->service?->name ? $product->service->name.' · ' : '').$product->product_code.' · '.$product->name),
                                    ])->all())
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                    $currency = strtoupper((string) ($get('../../currency') ?: 'VND'));
                                    $product = $state ? Product::query()->with('prices')->find($state) : null;
                                    $set('product_price_id', $product?->preferredPrice($currency)?->getKey());
                                })
                                ->required()
                                ->columnSpan(2),
                            Select::make('product_price_id')
                                ->label(UiText::get('fields.product_price', 'Product price'))
                                ->options(function (Get $get): array {
                                    $productId = $get('product_id');
                                    if (! $productId) {
                                        return [];
                                    }
                                    $currency = strtoupper((string) ($get('../../currency') ?: 'VND'));
                                    return ProductPrice::query()
                                        ->where('product_id', $productId)
                                        ->where('status', ServiceStatus::Active->value)
                                        ->where('currency', $currency)
                                        ->orderByDesc('is_default')
                                        ->orderBy('billing_period')
                                        ->get()
                                        ->mapWithKeys(fn (ProductPrice $price): array => [
                                            $price->getKey() => $price->price_code.' · '.$price->cycleLabel().' · '.($price->price !== null ? number_format((float) $price->price, $currency === 'VND' ? 0 : 2, ',', '.').' '.$currency : UiText::get('fields.contact_for_price', 'Contact')),
                                        ])->all();
                                })
                                ->searchable()
                                ->preload()
                                ->helperText(UiText::get('fields.product_price_help', 'Choose the product price/cycle used by component-sum pricing. Only prices in the bundle currency are shown.'))
                                ->columnSpan(2),
                            TextInput::make('quantity')
                                ->label(UiText::get('fields.quantity', 'Quantity'))
                                ->numeric()
                                ->minValue(0.01)
                                ->step(0.01)
                                ->default(1)
                                ->required(),
                            Toggle::make('required')
                                ->label(UiText::get('fields.required_component', 'Required'))
                                ->default(true)
                                ->helperText(UiText::get('fields.required_component_help', 'Optional components may later be deselected in a quote or order.')),
                            TextInput::make('price_override')
                                ->label(UiText::get('fields.price_override', 'Price override'))
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01)
                                ->helperText(UiText::get('fields.price_override_help', 'Optional per-unit price used only when this product is sold through this bundle.')),
                            TextInput::make('sort_order')
                                ->label(UiText::get('fields.sort_order', 'Sort order'))
                                ->numeric()
                                ->minValue(0)
                                ->default(0),
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
                TextColumn::make('bundle_code')
                    ->label(UiText::get('fields.bundle_code', 'Bundle code'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('name')
                    ->label(UiText::get('common.fields.name', 'Name'))
                    ->description(fn (Bundle $record): ?string => filled($record->description) ? str($record->description)->limit(52)->toString() : null)
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('primaryService.name')
                    ->label(UiText::get('fields.primary_service', 'Primary service'))
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->label(UiText::get('fields.product_count', 'Products'))
                    ->alignCenter(),
                TextColumn::make('pricing_type')
                    ->label(UiText::get('fields.pricing_type', 'Pricing type'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => BundlePricingType::options()[$state instanceof \BackedEnum ? $state->value : (string) $state] ?? (string) $state),
                TextColumn::make('fixed_price')
                    ->label(UiText::get('fields.bundle_price', 'Bundle price'))
                    ->formatStateUsing(function ($state, Bundle $record): string {
                        $price = $record->effectivePrice();
                        if ($price === null) {
                            return UiText::get('fields.contact_for_price', 'Contact');
                        }
                        $currency = strtoupper((string) ($record->currency ?: 'VND'));
                        $decimals = $currency === 'VND' ? 0 : 2;
                        return number_format($price, $decimals, ',', '.').' '.$currency;
                    }),
                TextColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => UiText::status($state))
                    ->color(fn ($state): string => StatusColor::for($state)),
            ])
            ->filters([
                SelectFilter::make('primary_service_id')
                    ->label(UiText::get('fields.primary_service', 'Primary service'))
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
            'index' => Pages\ListBundles::route('/'),
            'create' => Pages\CreateBundle::route('/create'),
            'edit' => Pages\EditBundle::route('/{record}/edit'),
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
