<?php

namespace App\Models\Sales;

use App\Enums\Sales\EmailStatus;
use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\QuotationStatus;
use App\Models\Crm\Company;
use App\Models\Crm\Customer;
use App\Models\Crm\Staff;
use App\Models\Marketing\Contact;
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
        'opportunity_id',
        'company_id',
        'contact_id',
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

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
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

    public function getPartyDisplayNameAttribute(): string
    {
        return (string) (
            data_get($this->customer_snapshot, 'display_name')
            ?? data_get($this->company_snapshot, 'company_name')
            ?? $this->customer?->display_name
            ?? $this->company?->legal_name
            ?? $this->contact?->full_name
            ?? __('common.not_available')
        );
    }

    public function getPartyEmailAttribute(): ?string
    {
        return data_get($this->customer_snapshot, 'email')
            ?? data_get($this->company_snapshot, 'business_email')
            ?? $this->customer?->email
            ?? $this->contact?->businessProfile?->business_email
            ?? $this->contact?->personalProfile?->email;
    }

    public function getPartyPhoneAttribute(): ?string
    {
        return data_get($this->customer_snapshot, 'phone')
            ?? data_get($this->company_snapshot, 'business_phone')
            ?? $this->customer?->phone
            ?? $this->contact?->businessProfile?->business_phone
            ?? $this->contact?->personalProfile?->phone;
    }

    public function getPartyTypeAttribute(): string
    {
        return (string) (
            data_get($this->customer_snapshot, 'customer_type')
            ?? ($this->company_id ? 'business' : 'personal')
        );
    }

    public function isOpportunityQuotation(): bool
    {
        return $this->opportunity_id !== null;
    }

    public function isLegacyCustomerQuotation(): bool
    {
        return $this->opportunity_id === null
            && $this->customer_id !== null;
    }

    public function isCurrentVersion(): bool
    {
        return $this->status !== QuotationStatus::Superseded;
    }
}
