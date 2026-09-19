<?php

namespace Dth\Commercial\Models;

use Dth\Commercial\Enums\AudienceType;
use Dth\Commercial\Enums\ServiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'commercial_products';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'audience_type' => AudienceType::class,
            'status' => ServiceStatus::class,
            'default_quantity' => 'decimal:2',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $product): void {
            $product->product_code = strtoupper(trim((string) $product->product_code));
            $product->name = trim((string) $product->name);
            $product->unit = trim((string) ($product->unit ?: 'item'));
        });
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'product_id')->orderByDesc('is_default')->orderBy('id');
    }

    public function defaultPrice(): HasOne
    {
        return $this->hasOne(ProductPrice::class, 'product_id')
            ->where('is_default', true)
            ->where('status', ServiceStatus::Active->value)
            ->orderBy('id');
    }

    public function bundleItems(): HasMany
    {
        return $this->hasMany(BundleItem::class, 'product_id');
    }

    public function bundles(): BelongsToMany
    {
        return $this->belongsToMany(Bundle::class, 'commercial_bundle_items', 'product_id', 'bundle_id')
            ->withPivot(['quantity', 'required', 'price_override', 'sort_order'])
            ->withTimestamps();
    }

    public function opportunityItems(): HasMany
    {
        return $this->hasMany(OpportunityItem::class, 'product_id');
    }

    public function reference(): string
    {
        return (string) $this->product_code;
    }

    public function preferredPrice(?string $currency = null): ?ProductPrice
    {
        $currency = $currency ? strtoupper(trim($currency)) : null;
        $today = now()->startOfDay();

        if ($this->relationLoaded('prices')) {
            $eligible = $this->prices
                ->filter(function (ProductPrice $price) use ($currency, $today): bool {
                    $status = $price->status instanceof ServiceStatus ? $price->status->value : (string) $price->status;
                    if ($status !== ServiceStatus::Active->value) {
                        return false;
                    }
                    if ($currency && strtoupper((string) $price->currency) !== $currency) {
                        return false;
                    }
                    if ($price->valid_from && $price->valid_from->startOfDay()->gt($today)) {
                        return false;
                    }
                    if ($price->valid_until && $price->valid_until->endOfDay()->lt($today)) {
                        return false;
                    }
                    return true;
                })
                ->sortBy(fn (ProductPrice $price): array => [$price->is_default ? 0 : 1, $price->getKey()]);

            return $eligible->first();
        }

        $query = $this->prices()
            ->where('status', ServiceStatus::Active->value)
            ->when($currency, fn ($q) => $q->where('currency', $currency))
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhereDate('valid_from', '<=', $today))
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', $today));

        return (clone $query)->where('is_default', true)->first()
            ?? $query->first();
    }
}
