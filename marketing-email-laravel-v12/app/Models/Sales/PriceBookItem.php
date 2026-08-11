<?php

namespace App\Models\Sales;

use App\Enums\Sales\DiscountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class PriceBookItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'price_book_id',
        'service_package_id',
        'service_product_id',
        'unit_price',
        'minimum_quantity',
        'maximum_quantity',
        'default_discount_type',
        'default_discount_value',
        'maximum_discount_value',
        'vat_rate',
        'description',
        'scope_override',
        'terms_override',
        'sort_order',
    ];


    protected static function booted(): void
    {
        static::saving(function (PriceBookItem $item): void {
            $hasPackage = filled($item->service_package_id);
            $hasProduct = filled($item->service_product_id);

            if ($hasPackage === $hasProduct) {
                throw ValidationException::withMessages([
                    'price_book_item' => __('v1.catalog.price_item_requires_single_sellable'),
                ]);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'default_discount_type' => DiscountType::class,
            'unit_price' => 'decimal:2',
            'default_discount_value' => 'decimal:2',
            'maximum_discount_value' => 'decimal:2',
            'vat_rate' => 'decimal:2',
        ];
    }

    public function priceBook(): BelongsTo
    {
        return $this->belongsTo(PriceBook::class);
    }

    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class);
    }

    public function serviceProduct(): BelongsTo
    {
        return $this->belongsTo(ServiceProduct::class);
    }

    public function sellable(): ServicePackage|ServiceProduct|null
    {
        return $this->serviceProduct ?: $this->servicePackage;
    }
}
