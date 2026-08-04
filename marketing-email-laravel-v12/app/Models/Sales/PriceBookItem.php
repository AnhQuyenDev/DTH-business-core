<?php

namespace App\Models\Sales;

use App\Enums\Sales\DiscountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceBookItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'price_book_id',
        'service_package_id',
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
}
