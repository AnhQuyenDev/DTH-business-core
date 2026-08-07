<?php

namespace App\Models\Crm;

use App\Enums\Crm\LeadIntakeStatus;
use App\Enums\Crm\StaffEmploymentStatus;
use App\Models\Marketing\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Staff extends Model
{
    use HasFactory, SoftDeletes;

    public const CUSTOMER_SERVICE_DEPARTMENT_CODE = 'customer_service';

    protected $fillable = [
        'user_id',
        'employee_code',
        'full_name',
        'department_id',
        'position_id',
        'phone',
        'employment_status',
        'can_receive_customers',
        'customer_capacity',
        'distribution_weight',
        'started_at',
        'ended_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'employment_status' => StaffEmploymentStatus::class,
            'can_receive_customers' => 'boolean',
            'customer_capacity' => 'integer',
            'distribution_weight' => 'decimal:2',
            'started_at' => 'date',
            'ended_at' => 'date',
            'metadata' => 'json',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(StaffAvailability::class);
    }

    public function assignedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_staff_id');
    }

    public function ownedCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'account_owner_staff_id');
    }

    public function companyAssignments(): HasMany
    {
        return $this->hasMany(CompanyAssignment::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CustomerAssignment::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(CustomerInteraction::class);
    }

    public function activityLogs(): HasManyThrough
    {
        return $this->hasManyThrough(
            AuditLog::class,
            User::class,
            'id',
            'user_id',
            'user_id',
            'id'
        );
    }

    public function ownedCustomers()
    {
        return $this->hasManyThrough(
            Customer::class,
            CustomerAssignment::class,
            'staff_id',
            'id',
            'id',
            'customer_id'
        )->where('customer_assignments.assignment_type', 'owner')
            ->where('customer_assignments.status', 'active');
    }

    public function supportedCustomers()
    {
        return $this->hasManyThrough(
            Customer::class,
            CustomerAssignment::class,
            'staff_id',
            'id',
            'id',
            'customer_id'
        )->where('customer_assignments.assignment_type', 'support')
            ->where('customer_assignments.status', 'active');
    }

    public function scopeEligibleForLeadDistribution(Builder $query): Builder
    {
        return $query
            ->whereHas(
                'department',
                fn (Builder $query): Builder => $query->where(
                    'code',
                    self::CUSTOMER_SERVICE_DEPARTMENT_CODE
                )
            )
            ->where(
                'employment_status',
                StaffEmploymentStatus::Active->value
            )
            ->where('can_receive_customers', true)
            ->whereDoesntHave(
                'availabilities',
                fn ($query) => $query
                    ->active()
                    ->where('can_receive_new_customers', false)
            );
    }

    public function isAvailable(): bool
    {
        return $this->employment_status === StaffEmploymentStatus::Active
            && $this->can_receive_customers;
    }

    public function managingCount(): int
    {
        return $this->assignments()
            ->where('assignment_type', 'owner')
            ->where('status', 'active')
            ->count();
    }

    public function supportingCount(): int
    {
        return $this->assignments()
            ->where('assignment_type', 'support')
            ->where('status', 'active')
            ->count();
    }

    public function currentLoad(): int
    {
        return $this->managingCount() + $this->supportingCount();
    }

    public function hasCapacity(): bool
    {
        if ($this->customer_capacity === null) {
            return true;
        }

        return $this->currentLoad() < $this->customer_capacity;
    }

    public function canReceiveNewLeads(): bool
    {
        if (
            $this->department?->code
                !== self::CUSTOMER_SERVICE_DEPARTMENT_CODE
            || $this->employment_status
                !== StaffEmploymentStatus::Active
            || ! $this->can_receive_customers
        ) {
            return false;
        }

        return ! $this->availabilities()
            ->active()
            ->where('can_receive_new_customers', false)
            ->exists();
    }

    public function openLeadCount(): int
    {
        return $this->assignedLeads()
            ->whereIn('intake_status', [
                LeadIntakeStatus::New->value,
                LeadIntakeStatus::Active->value,
            ])
            ->count();
    }
}
