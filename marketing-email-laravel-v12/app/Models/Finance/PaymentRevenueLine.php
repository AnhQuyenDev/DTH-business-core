<?php

namespace App\Models\Finance;

use App\Models\Sales\QuotationItem;
use App\Models\Sales\Service;
use App\Models\Sales\ServicePackage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRevenueLine extends Model
{
    protected $fillable = [
        'payment_id', 'quotation_item_id', 'service_id', 'service_package_id',
        'service_code_snapshot', 'service_name_snapshot', 'package_code_snapshot',
        'package_name_snapshot', 'quantity', 'net_amount', 'tax_amount', 'gross_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'net_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'gross_amount' => 'decimal:2',
        ];
    }

    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
    public function quotationItem(): BelongsTo { return $this->belongsTo(QuotationItem::class); }
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
    public function servicePackage(): BelongsTo { return $this->belongsTo(ServicePackage::class); }
}
