<?php

namespace App\Models\Sales;

use App\Enums\Sales\EmailStatus;
use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\QuotationStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'quotation_code',
        'customer_id',
        'assigned_staff_id',
        'price_book_id',
        'bank_account_id',
        'title',
        'version',
        'parent_quotation_id',
        'replaces_quotation_id',
        'quotation_date',
        'valid_until',
        'currency',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'status',
        'payment_status',
        'email_status',
        'customer_snapshot',
        'company_snapshot',
        'payment_snapshot',
        'terms_snapshot',
        'metadata',
        'public_token',
        'sent_at',
        'first_viewed_at',
        'last_viewed_at',
        'view_count',
        'accepted_at',
        'rejected_at',
        'revision_requested_at',
        'expired_at',
        'cancelled_at',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuotationStatus::class,
            'payment_status' => PaymentStatus::class,
            'email_status' => EmailStatus::class,
            'customer_snapshot' => 'json',
            'company_snapshot' => 'json',
            'payment_snapshot' => 'json',
            'terms_snapshot' => 'json',
            'metadata' => 'json',
            'quotation_date' => 'date',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'first_viewed_at' => 'datetime',
            'last_viewed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'revision_requested_at' => 'datetime',
            'expired_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'approved_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'view_count' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_staff_id');
    }

    public function priceBook(): BelongsTo
    {
        return $this->belongsTo(PriceBook::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class);
    }

    public function confirmations(): HasMany
    {
        return $this->hasMany(QuotationConfirmation::class);
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(QuotationEmailLog::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(QuotationDocument::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(QuotationApproval::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_quotation_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isCurrentVersion(): bool
    {
        return $this->status !== QuotationStatus::Superseded;
    }
}
