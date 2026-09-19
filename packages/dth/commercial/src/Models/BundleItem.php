<?php

namespace Dth\Commercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BundleItem extends Model
{
    protected $table = 'commercial_bundle_items';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'required' => 'boolean',
            'price_override' => 'decimal:2',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class, 'bundle_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function selectedPrice(): BelongsTo
    {
        return $this->belongsTo(ProductPrice::class, 'product_price_id');
    }

    public function priceFor(?string $currency = null): ?ProductPrice
    {
        $currency = $currency ? strtoupper(trim($currency)) : null;
        $selected = $this->relationLoaded('selectedPrice')
            ? $this->selectedPrice
            : ($this->product_price_id ? $this->selectedPrice()->first() : null);

        if ($selected && (! $currency || strtoupper((string) $selected->currency) === $currency)) {
            return $selected;
        }

        $product = $this->relationLoaded('product') ? $this->product : $this->product()->first();

        return $product?->preferredPrice($currency);
    }
}
