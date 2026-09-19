<?php

namespace Dth\Commercial\Models;

use Dth\Commercial\Enums\BillingPeriodUnit;
use Dth\Commercial\Enums\OpportunityItemType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpportunityItem extends Model
{
    protected $table = 'commercial_opportunity_items';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'item_type' => OpportunityItemType::class,
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'setup_fee' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'billing_period_unit' => BillingPeriodUnit::class,
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            $item->hydrateSnapshotAndPricing();
            $item->calculateTotals();
        });

        static::saved(function (self $item): void {
            Opportunity::query()->find($item->opportunity_id)?->recalculateEstimatedValue();
        });

        static::deleted(function (self $item): void {
            Opportunity::query()->find($item->opportunity_id)?->recalculateEstimatedValue(zeroWhenEmpty: true);
        });
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'opportunity_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function selectedPrice(): BelongsTo
    {
        return $this->belongsTo(ProductPrice::class, 'product_price_id');
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class, 'bundle_id');
    }

    private function hydrateSnapshotAndPricing(): void
    {
        $opportunity = $this->opportunity()->first();
        $currency = strtoupper((string) ($opportunity?->currency ?: $this->currency ?: 'VND'));
        $this->currency = $currency;

        $type = $this->item_type instanceof OpportunityItemType
            ? $this->item_type
            : OpportunityItemType::tryFrom((string) $this->item_type);

        if ($type === OpportunityItemType::Product || ($type === null && $this->product_id)) {
            $product = Product::query()->with('service')->find($this->product_id);
            if ($product) {
                $this->item_type = OpportunityItemType::Product->value;
                $this->bundle_id = null;
                $this->item_code_snapshot = $product->product_code;
                $this->item_name_snapshot = $product->name;
                $this->service_name_snapshot = $product->service?->name;
                $this->description_snapshot ??= $product->description;
                $this->unit_snapshot ??= $product->unit;

                $selectedPrice = $this->product_price_id
                    ? ProductPrice::query()->where('product_id', $product->getKey())->find($this->product_price_id)
                    : null;
                $price = $selectedPrice && strtoupper((string) $selectedPrice->currency) === $currency
                    ? $selectedPrice
                    : $product->preferredPrice($currency);
                $this->product_price_id = $price?->getKey();
                if ($price) {
                    $this->unit_price ??= $price->price;
                    if ((float) ($this->setup_fee ?? 0) === 0.0) {
                        $this->setup_fee = $price->setup_fee ?? 0;
                    }
                    $this->billing_period ??= $price->billing_period;
                    $this->billing_period_unit ??= $price->billing_period_unit?->value;
                }
            }
        }

        if ($type === OpportunityItemType::Bundle || ($type === null && $this->bundle_id)) {
            $bundle = Bundle::query()->with(['primaryService', 'items.product.prices', 'items.selectedPrice'])->find($this->bundle_id);
            if ($bundle) {
                $this->item_type = OpportunityItemType::Bundle->value;
                $this->product_id = null;
                $this->product_price_id = null;
                $this->item_code_snapshot = $bundle->bundle_code;
                $this->item_name_snapshot = $bundle->name;
                $this->service_name_snapshot = $bundle->primaryService?->name;
                $this->description_snapshot ??= $bundle->description;
                $this->unit_snapshot ??= 'bundle';
                $this->unit_price ??= $bundle->effectivePrice();
                if ((float) ($this->setup_fee ?? 0) === 0.0) {
                    $this->setup_fee = $bundle->effectiveSetupFee();
                }
                $this->billing_period ??= $bundle->billing_period;
                $this->billing_period_unit ??= $bundle->billing_period_unit?->value;
            }
        }
    }

    private function calculateTotals(): void
    {
        $quantity = max(0, (float) ($this->quantity ?: 0));
        $unitPrice = max(0, (float) ($this->unit_price ?: 0));
        $discountPercent = min(100, max(0, (float) ($this->discount_percent ?: 0)));
        $setupFee = max(0, (float) ($this->setup_fee ?: 0));

        $subtotal = round($quantity * $unitPrice, 2);
        $discountAmount = round($subtotal * ($discountPercent / 100), 2);

        $this->subtotal = $subtotal;
        $this->discount_amount = $discountAmount;
        $this->total = round($subtotal - $discountAmount + $setupFee, 2);
    }
}
