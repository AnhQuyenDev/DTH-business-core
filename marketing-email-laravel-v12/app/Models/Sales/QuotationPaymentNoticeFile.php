<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationPaymentNoticeFile extends Model
{
    protected $fillable = [
        'payment_notice_id',
        'disk',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'sha256',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'uploaded_at' => 'datetime',
        ];
    }

    public function paymentNotice(): BelongsTo
    {
        return $this->belongsTo(QuotationPaymentNotice::class, 'payment_notice_id');
    }
}
