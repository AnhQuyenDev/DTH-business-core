<?php

namespace Dth\Commercial\Models;

use Dth\Commercial\Enums\BillingPeriodUnit;
use Dth\Commercial\Enums\ServiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductPrice extends Model
{
    protected $table = 'commercial_product_prices';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'billing_period_unit' => BillingPeriodUnit::class,
            'price' => 'decimal:2',
            'renewal_price' => 'decimal:2',
            'setup_fee' => 'decimal:2',
            'is_default' => 'boolean',
            'status' => ServiceStatus::class,
            'valid_from' => 'date',
            'valid_until' => 'date',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $price): void {
            $price->currency = strtoupper(trim((string) ($price->currency ?: 'VND')));

            if (blank($price->price_code)) {
                $unit = $price->billing_period_unit instanceof \BackedEnum
                    ? $price->billing_period_unit->value
                    : (string) ($price->billing_period_unit ?: 'custom');
                $period = $price->billing_period ?: 1;
                $price->price_code = Str::upper("LIST-{$price->currency}-{$period}-{$unit}");
            } else {
                $price->price_code = strtoupper(trim((string) $price->price_code));
            }
        });

        static::saved(function (self $price): void {
            if ($price->is_default) {
                static::query()
                    ->where('product_id', $price->product_id)
                    ->where('currency', $price->currency)
                    ->where($price->getKeyName(), '!=', $price->getKey())
                    ->update(['is_default' => false]);
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function cycleLabel(): string
    {
        if ($this->billing_period_unit === BillingPeriodUnit::OneTime) {
            return BillingPeriodUnit::options()[BillingPeriodUnit::OneTime->value];
        }

        if (! $this->billing_period || ! $this->billing_period_unit) {
            return '—';
        }

        $unit = $this->billing_period_unit instanceof BillingPeriodUnit
            ? $this->billing_period_unit->value
            : (string) $this->billing_period_unit;

        return $this->billing_period.' '.(BillingPeriodUnit::options()[$unit] ?? $unit);
    }
}
