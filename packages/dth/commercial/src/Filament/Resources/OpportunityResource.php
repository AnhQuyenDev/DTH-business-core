<?php

namespace Dth\Commercial\Filament\Resources;

use Dth\Commercial\Enums\OpportunityItemType;
use Dth\Commercial\Enums\OpportunityStage;
use Dth\Commercial\Filament\Navigation\CommercialNavigationGroup;
use Dth\Commercial\Filament\Resources\OpportunityResource\Pages;
use Dth\Commercial\Models\Bundle;
use Dth\Commercial\Models\Opportunity;
use Dth\Commercial\Models\Product;
use Dth\Commercial\Models\ProductPrice;
use Dth\Commercial\Models\Service;
use Dth\Commercial\Services\OpportunityWorkflowService;
use Dth\Commercial\Support\CommercialAuthorization;
use Dth\Commercial\Support\CrmLeadLookup;
use Dth\Commercial\Support\StatusColor;
use Dth\Commercial\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class OpportunityResource extends Resource
{
    public const NAVIGATION_ICON = 'heroicon-o-briefcase';

    protected static ?string $model = Opportunity::class;
    protected static string|\BackedEnum|null $navigationIcon = self::NAVIGATION_ICON;
    protected static string|\UnitEnum|null $navigationGroup = CommercialNavigationGroup::Commercial;
    protected static ?int $navigationSort = 40;
    protected static ?string $slug = 'commercial-opportunities';
    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.opportunities', 'Business opportunities', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.opportunity', 'Business opportunity', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.opportunities', 'Business opportunities', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('sections.opportunity_basic', 'Basic information'))
                ->description(UiText::get('sections.opportunity_basic_help', 'Reuse CRM customer context when available. Products and bundles are selected separately below, so one opportunity may span multiple services.'))
                ->schema([
                    Select::make('lead_reference')
                        ->label(UiText::get('fields.crm_lead', 'CRM Lead'))
                        ->placeholder(fn (): string => app(CrmLeadLookup::class)->available()
                            ? UiText::get('fields.crm_lead_placeholder', 'Search by Lead code, title, contact or company')
                            : UiText::get('fields.crm_lead_unavailable', 'CRM module is not currently available'))
                        ->helperText(fn (): string => app(CrmLeadLookup::class)->available()
                            ? UiText::get('fields.crm_lead_help_new', 'Selecting a Lead fills customer and owner snapshots. Its service interest is retained only as context; actual products are selected in Opportunity items.')
                            : UiText::get('fields.crm_lead_unavailable_help', 'The opportunity can still be created independently. Existing CRM snapshots remain readable.'))
                        ->options(fn (): array => app(CrmLeadLookup::class)->search('', 25))
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search): array => app(CrmLeadLookup::class)->search($search))
                        ->getOptionLabelUsing(fn ($value): ?string => app(CrmLeadLookup::class)->label($value))
                        ->disabled(fn (): bool => ! app(CrmLeadLookup::class)->available())
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                            if (blank($state)) {
                                $set('lead_code_snapshot', null);
                                $set('contact_reference', null);
                                $set('company_reference', null);
                                $set('assigned_employee_reference', null);
                                return;
                            }

                            $snapshot = app(CrmLeadLookup::class)->snapshot($state);
                            if (! $snapshot) {
                                return;
                            }

                            $set('lead_code_snapshot', $snapshot['lead_code']);
                            $set('contact_reference', $snapshot['contact_reference']);
                            $set('contact_name_snapshot', $snapshot['contact_name']);
                            $set('company_reference', $snapshot['company_reference']);
                            $set('company_name_snapshot', $snapshot['company_name']);
                            $set('assigned_employee_reference', $snapshot['owner_reference']);
                            $set('assigned_employee_name_snapshot', $snapshot['owner_name']);

                            if (blank($get('title')) && filled($snapshot['title'])) {
                                $set('title', $snapshot['title']);
                            }

                            if (blank($get('estimated_value')) && filled($snapshot['estimated_value'])) {
                                $set('estimated_value', $snapshot['estimated_value']);
                            }

                            $service = null;
                            if (filled($snapshot['service_reference'])) {
                                $service = Service::query()
                                    ->where('slug', $snapshot['service_reference'])
                                    ->orWhere('service_code', $snapshot['service_reference'])
                                    ->first();
                            }
                            if (! $service && filled($snapshot['service_name'])) {
                                $service = Service::query()->where('name', $snapshot['service_name'])->first();
                            }

                            $set('service_id', $service?->getKey());
                            $set('service_reference', $service?->reference() ?? $snapshot['service_reference']);
                            $set('service_name_snapshot', $service?->name ?? ($snapshot['service_name'] ?: $snapshot['service_reference']));
                        })
                        ->columnSpanFull(),

                    TextInput::make('title')
                        ->label(UiText::get('fields.opportunity_name', 'Opportunity name'))
                        ->placeholder(UiText::get('fields.opportunity_name_placeholder', 'Example: Cloud & CRM solution for ABC Company'))
                        ->helperText(UiText::get('fields.opportunity_name_help_new', 'Use a recognizable deal name. The opportunity can contain products from several service categories.'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('opportunity_code')
                        ->label(UiText::get('fields.opportunity_code', 'Opportunity code'))
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder(UiText::get('fields.generated_automatically', 'Generated automatically')),
                    TextInput::make('company_name_snapshot')
                        ->label(UiText::get('fields.company', 'Company'))
                        ->placeholder(UiText::get('fields.company_placeholder', 'Customer or company name'))
                        ->maxLength(255),
                    TextInput::make('contact_name_snapshot')
                        ->label(UiText::get('fields.contact', 'Contact'))
                        ->placeholder(UiText::get('fields.contact_placeholder', 'Main contact person'))
                        ->maxLength(255),
                    Select::make('stage')
                        ->label(UiText::get('fields.stage', 'Stage'))
                        ->options(OpportunityStage::options())
                        ->default(OpportunityStage::Discovery->value)
                        ->disabledOn('edit')
                        ->helperText(UiText::get('fields.stage_help', 'After creation, use “Move stage” so probability and outcome timestamps remain consistent.'))
                        ->required()
                        ->native(false),
                    TextInput::make('assigned_employee_name_snapshot')
                        ->label(UiText::get('fields.owner', 'Owner'))
                        ->placeholder(UiText::get('fields.owner_placeholder', 'Person responsible for this opportunity'))
                        ->maxLength(255),
                    TextInput::make('currency')
                        ->label(UiText::get('fields.currency', 'Currency'))
                        ->default('VND')
                        ->minLength(3)
                        ->maxLength(3)
                        ->helperText(UiText::get('fields.opportunity_currency_help', 'All opportunity line items use this currency so the opportunity total remains meaningful.'))
                        ->required(),

                    Hidden::make('lead_code_snapshot'),
                    Hidden::make('contact_reference'),
                    Hidden::make('company_reference'),
                    Hidden::make('assigned_employee_reference'),
                    Hidden::make('service_id'),
                    Hidden::make('service_reference'),
                    Hidden::make('service_name_snapshot'),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make(UiText::get('sections.opportunity_items', 'Products & bundles'))
                ->description(UiText::get('sections.opportunity_items_help', 'Add any combination of standalone products and predefined bundles. This is the line-item layer used to support A1 from Service A together with B2 from Service B in one deal.'))
                ->schema([
                    Repeater::make('items')
                        ->relationship()
                        ->label(UiText::get('models.opportunity_items', 'Opportunity items'))
                        ->addActionLabel(UiText::get('actions.add_opportunity_item', 'Add product / bundle'))
                        ->schema([
                            Select::make('item_type')
                                ->label(UiText::get('fields.item_type', 'Item type'))
                                ->options(OpportunityItemType::options())
                                ->default(OpportunityItemType::Product->value)
                                ->live()
                                ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                    if ($state === OpportunityItemType::Product->value) {
                                        $set('bundle_id', null);
                                    } else {
                                        $set('product_id', null);
                                        $set('product_price_id', null);
                                    }
                                    $set('unit_price', null);
                                    $set('setup_fee', 0);
                                    self::refreshOpportunityItemTotals($get, $set);
                                })
                                ->native(false)
                                ->required(),
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
                                ->visible(fn (Get $get): bool => $get('item_type') === OpportunityItemType::Product->value)
                                ->required(fn (Get $get): bool => $get('item_type') === OpportunityItemType::Product->value)
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                    $currency = strtoupper((string) ($get('../../currency') ?: 'VND'));
                                    $product = $state ? Product::query()->with(['service', 'prices'])->find($state) : null;
                                    if (! $product) {
                                        return;
                                    }
                                    $price = $product->preferredPrice($currency);
                                    $set('product_price_id', $price?->getKey());
                                    $set('item_code_snapshot', $product->product_code);
                                    $set('item_name_snapshot', $product->name);
                                    $set('service_name_snapshot', $product->service?->name);
                                    $set('description_snapshot', $product->description);
                                    $set('unit_snapshot', $product->unit);
                                    $set('quantity', $product->default_quantity ?: 1);
                                    $set('unit_price', $price?->price);
                                    $set('setup_fee', $price?->setup_fee ?? 0);
                                    $set('billing_period', $price?->billing_period);
                                    $set('billing_period_unit', $price?->billing_period_unit?->value);
                                    self::refreshOpportunityItemTotals($get, $set);
                                }),
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
                                        ->where('status', 'active')
                                        ->where('currency', $currency)
                                        ->orderByDesc('is_default')
                                        ->orderBy('billing_period')
                                        ->get()
                                        ->mapWithKeys(fn (ProductPrice $price): array => [
                                            $price->getKey() => $price->price_code.' · '.$price->cycleLabel().' · '.($price->price !== null ? number_format((float) $price->price, $currency === 'VND' ? 0 : 2, ',', '.').' '.$currency : UiText::get('fields.contact_for_price', 'Contact')),
                                        ])->all();
                                })
                                ->visible(fn (Get $get): bool => $get('item_type') === OpportunityItemType::Product->value && filled($get('product_id')))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                    $price = $state ? ProductPrice::query()->find($state) : null;
                                    if (! $price) {
                                        return;
                                    }
                                    $set('unit_price', $price->price);
                                    $set('setup_fee', $price->setup_fee ?? 0);
                                    $set('billing_period', $price->billing_period);
                                    $set('billing_period_unit', $price->billing_period_unit?->value);
                                    self::refreshOpportunityItemTotals($get, $set);
                                })
                                ->helperText(UiText::get('fields.opportunity_product_price_help', 'Choose the billing cycle/price for this product. Only prices in the opportunity currency are shown.')),
                            Select::make('bundle_id')
                                ->label(UiText::get('models.package', 'Bundle'))
                                ->options(function (Get $get): array {
                                    $currency = strtoupper((string) ($get('../../currency') ?: 'VND'));
                                    return Bundle::query()->where('status', 'active')->where('currency', $currency)->orderBy('name')->pluck('name', 'id')->all();
                                })
                                ->visible(fn (Get $get): bool => $get('item_type') === OpportunityItemType::Bundle->value)
                                ->required(fn (Get $get): bool => $get('item_type') === OpportunityItemType::Bundle->value)
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                    $bundle = $state ? Bundle::query()->with(['primaryService', 'items.product.prices', 'items.selectedPrice'])->find($state) : null;
                                    if (! $bundle) {
                                        return;
                                    }
                                    $set('product_price_id', null);
                                    $set('item_code_snapshot', $bundle->bundle_code);
                                    $set('item_name_snapshot', $bundle->name);
                                    $set('service_name_snapshot', $bundle->primaryService?->name);
                                    $set('description_snapshot', $bundle->description);
                                    $set('unit_snapshot', 'bundle');
                                    $set('quantity', 1);
                                    $set('unit_price', $bundle->effectivePrice());
                                    $set('setup_fee', $bundle->effectiveSetupFee());
                                    $set('billing_period', $bundle->billing_period);
                                    $set('billing_period_unit', $bundle->billing_period_unit?->value);
                                    self::refreshOpportunityItemTotals($get, $set);
                                }),
                            TextInput::make('quantity')
                                ->label(UiText::get('fields.quantity', 'Quantity'))
                                ->numeric()
                                ->minValue(0.01)
                                ->step(0.01)
                                ->default(1)
                                ->live(debounce: 350)
                                ->afterStateUpdated(function (Get $get, Set $set): void {
                                    self::refreshOpportunityItemTotals($get, $set);
                                })
                                ->required(),
                            TextInput::make('unit_price')
                                ->label(UiText::get('fields.unit_price', 'Unit price'))
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01)
                                ->live(debounce: 350)
                                ->afterStateUpdated(function (Get $get, Set $set): void {
                                    self::refreshOpportunityItemTotals($get, $set);
                                })
                                ->helperText(UiText::get('fields.unit_price_help', 'Defaults from the selected product price or bundle price, but may be adjusted for this deal.')),
                            TextInput::make('discount_percent')
                                ->label(UiText::get('fields.discount_percent', 'Discount (%)'))
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->step(0.01)
                                ->default(0)
                                ->live(debounce: 350)
                                ->afterStateUpdated(function (Get $get, Set $set): void {
                                    self::refreshOpportunityItemTotals($get, $set);
                                }),
                            TextInput::make('setup_fee')
                                ->label(UiText::get('fields.setup_fee', 'Setup fee'))
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01)
                                ->default(0)
                                ->live(debounce: 350)
                                ->afterStateUpdated(function (Get $get, Set $set): void {
                                    self::refreshOpportunityItemTotals($get, $set);
                                }),
                            TextInput::make('subtotal')
                                ->label(UiText::get('fields.subtotal', 'Subtotal'))
                                ->numeric()
                                ->disabled()
                                ->dehydrated(false),
                            TextInput::make('discount_amount')
                                ->label(UiText::get('fields.discount_amount', 'Discount amount'))
                                ->numeric()
                                ->disabled()
                                ->dehydrated(false),
                            TextInput::make('total')
                                ->label(UiText::get('fields.line_total', 'Line total'))
                                ->numeric()
                                ->disabled()
                                ->dehydrated(false),
                            Textarea::make('description_snapshot')
                                ->label(UiText::get('fields.item_description', 'Line description'))
                                ->rows(2)
                                ->columnSpanFull(),
                            Hidden::make('line_key'),
                            Hidden::make('item_code_snapshot'),
                            Hidden::make('item_name_snapshot'),
                            Hidden::make('service_name_snapshot'),
                            Hidden::make('unit_snapshot'),
                            Hidden::make('billing_period'),
                            Hidden::make('billing_period_unit'),
                        ])
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set): void {
                            $set('../estimated_value', self::calculateOpportunityStateTotal((array) $state));
                        })
                        ->columns(4)
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Section::make(UiText::get('sections.opportunity_value', 'Value & timeline'))
                ->description(UiText::get('sections.opportunity_value_help_new', 'When line items exist, estimated value is recalculated from their totals. Probability and close date remain opportunity-level forecast fields.'))
                ->schema([
                    TextInput::make('estimated_value')
                        ->label(UiText::get('fields.estimated_value', 'Estimated value'))
                        ->numeric()
                        ->minValue(0)
                        ->placeholder('0')
                        ->helperText(UiText::get('fields.estimated_value_help_new', 'For legacy or item-less opportunities this can be entered manually. Once line items exist, the system derives it from quantity, price, discount and setup fees.')),
                    TextInput::make('probability')
                        ->label(UiText::get('fields.probability', 'Probability'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->default(OpportunityStage::Discovery->probability()),
                    DatePicker::make('expected_close_date')
                        ->label(UiText::get('fields.expected_close_date', 'Expected close date'))
                        ->native(false)
                        ->displayFormat('d/m/Y'),
                    Textarea::make('lost_reason')
                        ->label(UiText::get('fields.lost_reason', 'Lost reason'))
                        ->rows(3)
                        ->columnSpanFull()
                        ->visible(fn ($record): bool => $record?->stage === OpportunityStage::Lost),
                ])
                ->columns(3)
                ->columnSpanFull(),
        ]);
    }

    /** @return array{subtotal: float, discount_amount: float, total: float} */
    private static function calculateOpportunityItemAmounts(array $item): array
    {
        $quantity = max(0, (float) ($item['quantity'] ?? 0));
        $unitPrice = max(0, (float) ($item['unit_price'] ?? 0));
        $discountPercent = min(100, max(0, (float) ($item['discount_percent'] ?? 0)));
        $setupFee = max(0, (float) ($item['setup_fee'] ?? 0));

        $subtotal = round($quantity * $unitPrice, 2);
        $discountAmount = round($subtotal * ($discountPercent / 100), 2);

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'total' => round($subtotal - $discountAmount + $setupFee, 2),
        ];
    }

    private static function calculateOpportunityStateTotal(array $items): float
    {
        return round(array_reduce(
            $items,
            static fn (float $carry, mixed $item): float => $carry + self::calculateOpportunityItemAmounts(is_array($item) ? $item : [])['total'],
            0.0,
        ), 2);
    }

    private static function refreshOpportunityItemTotals(Get $get, Set $set): void
    {
        $amounts = self::calculateOpportunityItemAmounts([
            'quantity' => $get('quantity'),
            'unit_price' => $get('unit_price'),
            'discount_percent' => $get('discount_percent'),
            'setup_fee' => $get('setup_fee'),
        ]);

        $set('subtotal', $amounts['subtotal']);
        $set('discount_amount', $amounts['discount_amount']);
        $set('total', $amounts['total']);

        $set('../../estimated_value', self::calculateOpportunityStateTotal((array) $get('../../items')));

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('opportunity_code')
                    ->label(UiText::get('fields.opportunity_code', 'Opportunity code'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('title')
                    ->label(UiText::get('common.fields.name', 'Name'))
                    ->description(fn (Opportunity $record): ?string => $record->company_name_snapshot ?: $record->contact_name_snapshot)
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('items_count')
                    ->counts('items')
                    ->label(UiText::get('fields.line_items', 'Line items'))
                    ->alignCenter(),
                TextColumn::make('service_name_snapshot')
                    ->label(UiText::get('fields.legacy_service_interest', 'CRM service interest'))
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),
                TextColumn::make('stage')
                    ->label(UiText::get('fields.stage', 'Stage'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof OpportunityStage
                        ? $state->label()
                        : (OpportunityStage::tryFrom((string) $state)?->label() ?? (string) $state))
                    ->color(fn ($state): string => StatusColor::for($state instanceof \BackedEnum ? $state->value : (string) $state)),
                TextColumn::make('estimated_value')
                    ->label(UiText::get('fields.estimated_value', 'Estimated value'))
                    ->formatStateUsing(fn ($state, Opportunity $record): string => number_format((float) ($state ?? 0), strtoupper((string) $record->currency) === 'VND' ? 0 : 2, ',', '.').' '.strtoupper((string) ($record->currency ?: 'VND')))
                    ->sortable(),
                TextColumn::make('probability')
                    ->label(UiText::get('fields.probability', 'Probability'))
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('expected_close_date')
                    ->label(UiText::get('fields.expected_close_date', 'Expected close date'))
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('assigned_employee_name_snapshot')
                    ->label(UiText::get('fields.owner', 'Owner'))
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('stage')
                    ->label(UiText::get('fields.stage', 'Stage'))
                    ->options(OpportunityStage::options()),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\Action::make('transition')
                        ->label(UiText::get('actions.move_stage', 'Move stage'))
                        ->icon('heroicon-o-arrow-right-circle')
                        ->visible(fn (Opportunity $record): bool => ! $record->isTerminal())
                        ->schema(fn (Opportunity $record): array => [
                            Select::make('stage')
                                ->label(UiText::get('fields.next_stage', 'Next stage'))
                                ->options(app(OpportunityWorkflowService::class)->allowedTransitions($record))
                                ->native(false)
                                ->required(),
                            Textarea::make('lost_reason')
                                ->label(UiText::get('fields.lost_reason', 'Lost reason'))
                                ->rows(3),
                            DatePicker::make('expected_close_date')
                                ->label(UiText::get('fields.expected_close_date', 'Expected close date'))
                                ->native(false),
                        ])
                        ->action(function (Opportunity $record, array $data): void {
                            try {
                                app(OpportunityWorkflowService::class)->transition(
                                    $record,
                                    OpportunityStage::from((string) $data['stage']),
                                    $data,
                                    auth()->id(),
                                );

                                Notification::make()->success()->title(UiText::get('notifications.stage_updated', 'Opportunity stage updated'))->send();
                            } catch (ValidationException $exception) {
                                Notification::make()
                                    ->danger()
                                    ->title(UiText::get('notifications.stage_update_failed', 'Unable to update stage'))
                                    ->body(collect($exception->errors())->flatten()->first() ?: $exception->getMessage())
                                    ->send();
                            }
                        }),
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
            'index' => Pages\ListOpportunities::route('/'),
            'create' => Pages\CreateOpportunity::route('/create'),
            'edit' => Pages\EditOpportunity::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return app(CommercialAuthorization::class)->allows('view');
    }

    public static function canCreate(): bool
    {
        return app(CommercialAuthorization::class)->allows('manage-opportunities');
    }

    public static function canEdit(Model $record): bool
    {
        return app(CommercialAuthorization::class)->allows('manage-opportunities');
    }

    public static function canDelete(Model $record): bool
    {
        return app(CommercialAuthorization::class)->allows('manage-opportunities');
    }
}
