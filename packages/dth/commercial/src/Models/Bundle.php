<?php

namespace Dth\Commercial\Models;

use Dth\Commercial\Enums\AudienceType;
use Dth\Commercial\Enums\BillingPeriodUnit;
use Dth\Commercial\Enums\BundlePricingType;
use Dth\Commercial\Enums\ServiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bundle extends Model
{
    use SoftDeletes;

    protected $table = 'commercial_bundles';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'audience_type' => AudienceType::class,
            'pricing_type' => BundlePricingType::class,
            'billing_period_unit' => BillingPeriodUnit::class,
            'fixed_price' => 'decimal:2',
            'renewal_price' => 'decimal:2',
            'setup_fee' => 'decimal:2',
            'status' => ServiceStatus::class,
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $bundle): void {
            $bundle->bundle_code = strtoupper(trim((string) $bundle->bundle_code));
            $bundle->name = trim((string) $bundle->name);
            $bundle->currency = strtoupper(trim((string) ($bundle->currency ?: 'VND')));
        });
    }

    public function primaryService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'primary_service_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BundleItem::class, 'bundle_id')->orderBy('sort_order')->orderBy('id');
    }

    public function opportunityItems(): HasMany
    {
        return $this->hasMany(OpportunityItem::class, 'bundle_id');
    }

    public function reference(): string
    {
        return (string) $this->bundle_code;
    }

    public function effectivePrice(): ?float
    {
        $pricingType = $this->pricing_type instanceof BundlePricingType
            ? $this->pricing_type
            : BundlePricingType::tryFrom((string) $this->pricing_type);

        if ($pricingType === BundlePricingType::Fixed) {
            return $this->fixed_price !== null ? (float) $this->fixed_price : null;
        }

        $items = $this->relationLoaded('items')
            ? $this->items
            : $this->items()->with(['product.prices', 'selectedPrice'])->get();

        $total = 0.0;
        $hasPrice = false;
        foreach ($items as $item) {
            $productPrice = $item->priceFor($this->currency);
            $price = $item->price_override !== null
                ? (float) $item->price_override
                : (float) ($productPrice?->price ?? 0);
            if ($item->price_override !== null || $productPrice?->price !== null) {
                $hasPrice = true;
            }
            $total += $price * (float) $item->quantity;
        }

        return $hasPrice ? $total : null;
    }

    public function effectiveSetupFee(): float
    {
        $pricingType = $this->pricing_type instanceof BundlePricingType
            ? $this->pricing_type
            : BundlePricingType::tryFrom((string) $this->pricing_type);

        if ($pricingType === BundlePricingType::Fixed) {
            return (float) ($this->setup_fee ?? 0);
        }

        $items = $this->relationLoaded('items')
            ? $this->items
            : $this->items()->with(['product.prices', 'selectedPrice'])->get();

        return (float) $items->sum(function (BundleItem $item): float {
            $price = $item->priceFor($this->currency);
            return (float) ($price?->setup_fee ?? 0) * (float) $item->quantity;
        });
    }
}
