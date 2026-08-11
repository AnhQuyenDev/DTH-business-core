<?php

namespace App\Models\Crm;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\Crm\LeadIntakeStatus;
use App\Enums\Crm\PositionAuthority;
use App\Enums\Crm\StaffEmploymentStatus;
use App\Enums\UserRole;
use App\Models\Marketing\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

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

    protected static function booted(): void
    {
        static::creating(function (self $staff): void {
            if (blank($staff->employee_code)) {
                $staff->employee_code = static::nextEmployeeCode();
            }

            $staff->phone = static::normalizePhone($staff->phone);
        });

        static::saving(function (self $staff): void {
            $staff->full_name = trim((string) $staff->full_name);
            $staff->phone = static::normalizePhone($staff->phone);

            if (filled($staff->phone)) {
                $duplicatePhone = static::query()
                    ->where('phone', $staff->phone)
                    ->when($staff->exists, fn (Builder $query): Builder => $query->whereKeyNot($staff->getKey()))
                    ->exists();

                if ($duplicatePhone) {
                    throw ValidationException::withMessages([
                        'phone' => __('validation.unique', ['attribute' => __('configuration.staff.phone')]),
                    ]);
                }
            }
        });
    }

    public static function normalizePhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return null;
        }

        $hasInternationalPrefix = str_starts_with($phone, '+');
        $digits = preg_replace('/\\D+/', '', $phone) ?: '';

        return $digits === '' ? null : ($hasInternationalPrefix ? '+' : '').$digits;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function nextEmployeeCode(): string
    {
        $next = (static::withTrashed()->max('id') ?? 0) + 1;

        do {
            $code = 'EMP-'.str_pad((string) $next++, 4, '0', STR_PAD_LEFT);
        } while (static::withTrashed()->where('employee_code', $code)->exists());

        return $code;
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

    public function businessFunctions(): HasMany
    {
        return $this->hasMany(StaffBusinessFunction::class);
    }

    public function primaryBusinessFunction(): ?DepartmentFunction
    {
        $this->loadMissing('businessFunctions');

        // Once a Staff has explicit capability rows, those rows are the
        // authoritative business-permission source. Department becomes
        // organization/reporting only and must not silently re-grant a
        // capability that an Admin intentionally removed or disabled.
        if ($this->businessFunctions->isNotEmpty()) {
            $assignment = $this->businessFunctions
                ->where('is_active', true)
                ->sortBy([
                    ['is_primary', 'desc'],
                    ['id', 'asc'],
                ])
                ->first();

            if ($assignment?->function_key instanceof DepartmentFunction) {
                return $assignment->function_key;
            }

            return $assignment?->function_key
                ? DepartmentFunction::tryFrom((string) $assignment->function_key)
                : null;
        }

        // Legacy compatibility only: rows created before the V1 migration or
        // isolated tests without capability assignments may still resolve from
        // their physical Department.
        $this->loadMissing('department');

        return $this->department?->function();
    }

    public function hasBusinessFunction(DepartmentFunction|string $function): bool
    {
        $resolved = $function instanceof DepartmentFunction
            ? $function
            : DepartmentFunction::tryFrom((string) $function);

        if ($resolved === null) {
            return false;
        }

        $this->loadMissing('businessFunctions');

        if ($this->businessFunctions->isNotEmpty()) {
            return $this->businessFunctions->contains(
                fn (StaffBusinessFunction $assignment): bool =>
                    $assignment->is_active
                    && $assignment->function_key === $resolved
            );
        }

        // Backward-compatible fallback for legacy rows and tests that have not
        // created staff_business_functions yet.
        $this->loadMissing(['department', 'user']);

        if ($this->department?->function() === $resolved) {
            return true;
        }

        $legacyRole = UserRole::tryFrom((string) $this->user?->role);

        return match ($resolved) {
            DepartmentFunction::Marketing => in_array($legacyRole, [UserRole::MarketingManager, UserRole::MarketingStaff], true),
            DepartmentFunction::CustomerService => in_array($legacyRole, [UserRole::CustomerServiceManager, UserRole::CustomerServiceStaff], true),
            DepartmentFunction::Sales => in_array($legacyRole, [UserRole::SalesManager, UserRole::SalesStaff], true),
            DepartmentFunction::Finance => $legacyRole === UserRole::FinanceStaff,
            default => false,
        };
    }

    public function businessAuthorityFor(DepartmentFunction|string $function): ?PositionAuthority
    {
        $resolved = $function instanceof DepartmentFunction
            ? $function
            : DepartmentFunction::tryFrom((string) $function);

        if ($resolved === null) {
            return null;
        }

        $this->loadMissing('businessFunctions');

        if ($this->businessFunctions->isNotEmpty()) {
            $assignment = $this->businessFunctions->first(
                fn (StaffBusinessFunction $assignment): bool =>
                    $assignment->is_active
                    && $assignment->function_key === $resolved
            );

            if ($assignment?->authority_level instanceof PositionAuthority) {
                return $assignment->authority_level;
            }

            return $assignment?->authority_level
                ? PositionAuthority::tryFrom((string) $assignment->authority_level)
                : null;
        }

        $this->loadMissing(['department', 'position', 'user']);

        if ($this->department?->function() === $resolved) {
            return $this->position?->authority_level;
        }

        $legacyRole = UserRole::tryFrom((string) $this->user?->role);

        return match ($resolved) {
            DepartmentFunction::Marketing => match ($legacyRole) {
                UserRole::MarketingManager => PositionAuthority::Manager,
                UserRole::MarketingStaff => PositionAuthority::Member,
                default => null,
            },
            DepartmentFunction::CustomerService => match ($legacyRole) {
                UserRole::CustomerServiceManager => PositionAuthority::Manager,
                UserRole::CustomerServiceStaff => PositionAuthority::Member,
                default => null,
            },
            DepartmentFunction::Sales => match ($legacyRole) {
                UserRole::SalesManager => PositionAuthority::Manager,
                UserRole::SalesStaff => PositionAuthority::Member,
                default => null,
            },
            DepartmentFunction::Finance => $legacyRole === UserRole::FinanceStaff
                ? PositionAuthority::Member
                : null,
            default => null,
        };
    }

    public function hasBusinessManagerAuthority(DepartmentFunction|string $function): bool
    {
        return $this->businessAuthorityFor($function)?->isDepartmentManager() ?? false;
    }

    public function scopeWithBusinessFunction(Builder $query, DepartmentFunction|string $function): Builder
    {
        $resolved = $function instanceof DepartmentFunction
            ? $function
            : DepartmentFunction::tryFrom((string) $function);

        if ($resolved === null) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(function (Builder $query) use ($resolved): void {
            $query->whereHas('businessFunctions', fn (Builder $assignment): Builder =>
                $assignment->where('function_key', $resolved->value)
                    ->where('is_active', true)
            )->orWhere(function (Builder $legacy) use ($resolved): void {
                $legacy->whereDoesntHave('businessFunctions')
                    ->whereHas('department', fn (Builder $department): Builder =>
                        $department->where('function_key', $resolved->value)
                    );
            });
        });
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
            ->withBusinessFunction(DepartmentFunction::Sales)
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
            ! $this->hasBusinessFunction(DepartmentFunction::Sales)
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

    public function scopeEligibleForCustomerOwnership(Builder $query): Builder
    {
        return $query
            ->withBusinessFunction(DepartmentFunction::CustomerService)
            ->whereHas(
                'user',
                fn (Builder $query): Builder => $query->where('is_active', true)
            )
            ->where(
                'employment_status',
                StaffEmploymentStatus::Active->value
            )
            ->where('can_receive_customers', true)
            ->whereDoesntHave(
                'availabilities',
                fn (Builder $query): Builder => $query
                    ->active()
                    ->where('can_receive_new_customers', false)
            );
    }

    public function canReceiveNewCustomers(): bool
    {
        $this->loadMissing(['department', 'user']);

        return $this->hasBusinessFunction(DepartmentFunction::CustomerService)
            && $this->user?->is_active
            && $this->employment_status === StaffEmploymentStatus::Active
            && $this->can_receive_customers
            && $this->hasCapacity()
            && ! $this->availabilities()
                ->active()
                ->where('can_receive_new_customers', false)
                ->exists();
    }

    public function scopeEligibleForOpportunityOwnership(
        Builder $query
    ): Builder {
        return $query
            ->withBusinessFunction(DepartmentFunction::Sales)
            ->whereHas(
                'user',
                fn (Builder $query): Builder => $query
                    ->where('is_active', true)
                    ->whereIn('role', [
                        UserRole::User->value,
                        UserRole::SalesManager->value,
                        UserRole::SalesStaff->value,
                    ])
            )
            ->where(
                'employment_status',
                StaffEmploymentStatus::Active->value
            )
            ->where('can_receive_customers', true);
    }
}
