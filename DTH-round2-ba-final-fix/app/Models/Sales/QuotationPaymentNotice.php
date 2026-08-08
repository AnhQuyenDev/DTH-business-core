<?php

namespace App\Models\Sales;

use App\Enums\Sales\PaymentNoticeStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationPaymentNotice extends Model
{
    protected $fillable = [
        'quotation_id',
        'status',
        'payer_name',
        'payer_email',
        'declared_amount',
        'transfer_reference',
        'note',
        'submitted_at',
        'reviewed_at',
        'reviewed_by_user_id',
        'review_note',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentNoticeStatus::class,
            'declared_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }
}
