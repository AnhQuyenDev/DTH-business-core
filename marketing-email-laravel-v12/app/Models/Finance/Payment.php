<?php

namespace App\Models\Finance;

use App\Models\Crm\Customer;
use App\Models\Crm\Staff;
use App\Models\Sales\BankAccount;
use App\Models\Sales\Opportunity;
use App\Models\Sales\Quotation;
use App\Models\Sales\QuotationPaymentNotice;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{
    public const STATUS_VERIFIED = 'verified';
    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'payment_code',
        'quotation_id',
        'customer_id',
        'opportunity_id',
        'payment_notice_id',
        'bank_account_id',
        'sales_staff_id',
        'amount',
        'net_amount',
        'tax_amount',
        'currency',
        'payment_method',
        'transfer_reference',
        'status',
        'invoice_status',
        'invoice_provider',
        'external_invoice_id',
        'invoice_number',
        'invoice_url',
        'invoice_issued_at',
        'paid_at',
        'verified_at',
        'verified_by_user_id',
        'note',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
            'invoice_issued_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function quotation(): BelongsTo { return $this->belongsTo(Quotation::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function opportunity(): BelongsTo { return $this->belongsTo(Opportunity::class); }
    public function paymentNotice(): BelongsTo { return $this->belongsTo(QuotationPaymentNotice::class); }
    public function bankAccount(): BelongsTo { return $this->belongsTo(BankAccount::class); }
    public function salesStaff(): BelongsTo { return $this->belongsTo(Staff::class, 'sales_staff_id'); }
    public function verifiedBy(): BelongsTo { return $this->belongsTo(User::class, 'verified_by_user_id'); }
    public function revenueLines(): HasMany { return $this->hasMany(PaymentRevenueLine::class); }
    public function attribution(): HasOne { return $this->hasOne(PaymentAttribution::class); }
    public function receipt(): HasOne { return $this->hasOne(PaymentReceipt::class); }

    public function scopeVerified($query)
    {
        return $query->where('status', self::STATUS_VERIFIED);
    }
}
