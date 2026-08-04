<?php

namespace App\Models\Sales;

use App\Enums\Sales\DiscountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'service_id',
        'service_package_id',
        'price_book_item_id',
        'service_code_snapshot',
        'service_name_snapshot',
        'package_code_snapshot',
        'package_name_snapshot',
        'description_snapshot',
        'scope_snapshot',
        'terms_snapshot',
        'unit',
        'quantity',
        'unit_price',
        'discount_type',
        'discount_value',
        'discount_amount',
        'vat_rate',
        'vat_amount',
        'line_subtotal',
        'line_total',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,
            'unit_price' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'line_subtotal' => 'decimal:2',
            'line_total' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function servicePackage(): BelongsTo
    {
        return $this->belongsTo(ServicePackage::class);
    }
}
