<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReceipt extends Model
{
    protected $fillable = [
        'payment_id', 'receipt_code', 'disk', 'file_path', 'file_name',
        'mime_type', 'sha256', 'generated_at', 'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'snapshot' => 'array',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
