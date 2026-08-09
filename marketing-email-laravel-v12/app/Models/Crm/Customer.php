<?php

namespace App\Models\Crm;

use App\Models\Finance\Payment;
use App\Enums\Crm\CustomerAssignmentStatus;
use App\Enums\Crm\CustomerConsentStatus;
use App\Enums\Crm\CustomerStatus;
use App\Models\Marketing\CampaignRecipient;
use App\Models\Marketing\Contact;
use App\Models\Marketing\EmailEvent;
use App\Models\Marketing\SuppressionEntry;
use App\Models\Marketing\Tag;
use App\Models\Sales\Opportunity;
use App\Models\Sales\Quotation;
use App\Services\Marketing\AuditLogService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (self $customer): void {
            if (blank($customer->display_name)) {
                if ($customer->customer_type === 'personal' && filled($customer->first_name)) {
                    $customer->display_name = trim(implode(' ', array_filter([
                        $customer->first_name, $customer->last_name,
                    ])));
                } elseif (filled($customer->company_name)) {
                    $customer->display_name = $customer->company_name;
                } elseif (filled($customer->legal_representative)) {
                    $customer->display_name = $customer->legal_representative;
                }
            }

            if ($customer->customer_type === 'business' && filled($customer->company_name)) {
                $existing = static::where('company_name', $customer->company_name)
                    ->whereNotNull('company_group_id')
                    ->first();
                if ($existing) {
                    $customer->company_group_id = $existing->company_group_id;
                }
            }
        });

        static::created(function (self $customer): void {
            app(AuditLogService::class)->log('customer.created', $customer, [], $customer->attributesToArray());
        });

        static::updated(function (self $customer): void {
            app(AuditLogService::class)->log('customer.updated', $customer, $customer->getOriginal(), $customer->getChanges());
        });

        static::deleting(function (self $customer): void {
            $customer->assignments()
                ->where('status', CustomerAssignmentStatus::Active->value)
                ->update([
                    'status' => CustomerAssignmentStatus::Ended->value,
                    'ends_at' => now(),
                    'ended_at' => now(),
                    'ended_by_user_id' => Auth::id(),
                    'note' => 'Khách hàng đã bị xoá',
                ]);
        });

        static::deleted(function (self $customer): void {
            app(AuditLogService::class)->log('customer.deleted', $customer, $customer->getOriginal(), []);
        });
    }

    protected $fillable = [
        'customer_code',
        'contact_id',
        'company_id',
        'converted_from_opportunity_id',
        'customer_type',
        'display_name',
        'email',
        'normalized_email',
        'phone',
        'normalized_phone',
        'email_verified_at',
        'acquisition_source',
        'consent_status',
        'subscribed_at',
        'unsubscribed_at',
        'last_engaged_at',
        'status',
        'lifecycle_stage',
        'conversion_reason',
        'converted_at',
        'converted_by_staff_id',
        'first_purchase_at',
        'latest_purchase_at',
        'total_revenue',
        'priority',
        'next_follow_up_at',
        'metadata',
        // Personal fields
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'customer_ward',
        'customer_district',
        'customer_province',
        'customer_country',
        'occupation',
        // Business fields
        'company_name',
        'tax_code',
        'company_address',
        'company_ward',
        'company_province',
        'legal_representative',
        'contact_position',
        'business_email',
        'business_phone',
        'industry',
        'company_group_id',
    ];

    protected function casts(): array
    {
        return [
            'consent_status' => CustomerConsentStatus::class,
            'status' => CustomerStatus::class,
            'email_verified_at' => 'datetime',
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'last_engaged_at' => 'datetime',
            'converted_at' => 'datetime',
            'first_purchase_at' => 'datetime',
            'latest_purchase_at' => 'datetime',
            'total_revenue' => 'decimal:2',
            'next_follow_up_at' => 'datetime',
            'metadata' => 'json',
            'date_of_birth' => 'date',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function convertedFromOpportunity(): BelongsTo
    {
        return $this->belongsTo(
            Opportunity::class,
            'converted_from_opportunity_id'
        );
    }

    public function convertedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'converted_by_staff_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CustomerAssignment::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(CustomerInteraction::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'customer_tag')->withTimestamps();
    }

    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(CustomerList::class, 'customer_list_members')
            ->withPivot(['status', 'subscribed_at', 'unsubscribed_at'])
            ->withTimestamps();
    }

    public function campaignRecipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function emailEvents(): HasMany
    {
        return $this->hasMany(EmailEvent::class);
    }

    public function suppressionEntries(): HasMany
    {
        return $this->hasMany(SuppressionEntry::class);
    }

    public function currentOwner(): HasOneThrough
    {
        return $this->hasOneThrough(
            Staff::class,
            CustomerAssignment::class,
            'customer_id',
            'id',
            'id',
            'staff_id'
        )->where('customer_assignments.assignment_type', 'owner')
            ->where('customer_assignments.status', 'active');
    }

    public function visibleOwner(): HasOneThrough
    {
        return $this->hasOneThrough(
            Staff::class,
            CustomerAssignment::class,
            'customer_id',
            'id',
            'id',
            'staff_id'
        )->where('customer_assignments.assignment_type', 'owner')
            ->latest('customer_assignments.created_at');
    }

    public function currentSupportStaff()
    {
        return $this->assignments()
            ->where('assignment_type', 'support')
            ->where('status', 'active')
            ->with('staff')
            ->get()
            ->pluck('staff');
    }

    public function companyGroup(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'company_group_id', 'company_group_id');
    }

    public function companyMembers()
    {
        return $this->hasMany(Customer::class, 'company_group_id', 'company_group_id')
            ->where('id', '!=', $this->id);
    }

    public function isMarketable(): bool
    {
        return filled($this->email)
            && $this->consent_status === CustomerConsentStatus::Subscribed
            && in_array($this->status, [CustomerStatus::Potential, CustomerStatus::Active], true);
    }
}
