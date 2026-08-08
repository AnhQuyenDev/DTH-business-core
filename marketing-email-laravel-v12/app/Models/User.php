<?php

namespace App\Models;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\UserRole;
use App\Models\Crm\Staff;
use App\Models\Marketing\AuditLog;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->canAccessBusinessPanel();
    }

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    public function hasRole(UserRole|string $role): bool
    {
        $resolved = $role instanceof UserRole
            ? $role
            : UserRole::tryFrom($role);

        if ($resolved === null) {
            return $this->role === $role;
        }

        // Tương thích với dữ liệu/tests cũ trong thời gian chuyển đổi.
        if ($this->role === $resolved->value) {
            return true;
        }

        $current = UserRole::tryFrom((string) $this->role);

        // User cũ mang role theo phòng ban được xem là User nghiệp vụ.
        if ($resolved === UserRole::User) {
            return $current?->isLegacyAlias() ?? false;
        }

        // Tương thích ngữ nghĩa cũ: manager cũng là staff của cùng phòng ban.
        if ($current?->isLegacyAlias()) {
            $legacyHierarchyMatch = match ($resolved) {
                UserRole::MarketingStaff => $current === UserRole::MarketingManager,
                UserRole::CustomerServiceStaff => $current === UserRole::CustomerServiceManager,
                UserRole::SalesStaff => $current === UserRole::SalesManager,
                default => false,
            };

            if ($legacyHierarchyMatch) {
                return true;
            }
        }

        if (! $resolved->isLegacyAlias()) {
            return false;
        }

        return $this->matchesLegacyBusinessAlias($resolved);
    }

    /**
     * @param  array<int, UserRole|string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin->value;
    }

    public function isExecutive(): bool
    {
        return $this->role === UserRole::Executive->value;
    }

    public function isSystemUser(): bool
    {
        return $this->hasRole(UserRole::User);
    }

    public function isViewer(): bool
    {
        return $this->role === UserRole::Viewer->value;
    }

    public function canReadAcrossBusiness(): bool
    {
        return $this->isExecutive() || $this->isViewer();
    }

    public function departmentFunction(): ?DepartmentFunction
    {
        $this->loadMissing('staff.department');

        return $this->staff?->department?->function();
    }

    public function belongsToDepartmentFunction(DepartmentFunction $function): bool
    {
        return $this->departmentFunction() === $function;
    }

    public function hasDepartmentManagerAuthority(): bool
    {
        $this->loadMissing('staff.position');

        return $this->staff?->position?->grantsDepartmentManagerAuthority()
            ?? false;
    }

    public function isMarketingManager(): bool
    {
        return $this->hasRole(UserRole::MarketingManager);
    }

    public function isMarketingStaff(): bool
    {
        return $this->hasRole(UserRole::MarketingStaff);
    }

    public function isCustomerServiceManager(): bool
    {
        return $this->hasRole(UserRole::CustomerServiceManager);
    }

    public function isCustomerServiceStaff(): bool
    {
        return $this->hasRole(UserRole::CustomerServiceStaff);
    }

    public function isSalesManager(): bool
    {
        return $this->hasRole(UserRole::SalesManager);
    }

    public function isSalesStaff(): bool
    {
        return $this->hasRole(UserRole::SalesStaff);
    }

    public function isFinanceStaff(): bool
    {
        return $this->hasRole(UserRole::FinanceStaff);
    }

    public function canViewMarketingModule(): bool
    {
        return $this->isAdmin()
            || $this->canReadAcrossBusiness()
            || $this->isMarketingStaff();
    }

    public function canViewCrmModule(): bool
    {
        return $this->isAdmin()
            || $this->canReadAcrossBusiness()
            || $this->isMarketingStaff()
            || $this->isCustomerServiceStaff();
    }

    public function canViewSalesModule(): bool
    {
        return $this->isAdmin()
            || $this->canReadAcrossBusiness()
            || $this->isCustomerServiceManager()
            || $this->isSalesStaff()
            || $this->isFinanceStaff();
    }

    public function canViewCustomerCareModule(): bool
    {
        return $this->isAdmin()
            || $this->canReadAcrossBusiness()
            || $this->isCustomerServiceStaff();
    }

    public function canAccessBusinessPanel(): bool
    {
        return $this->is_active
            && UserRole::tryFrom((string) $this->role) !== null;
    }

    /**
     * Các key dùng để tương thích với rule truy cập cũ (ví dụ Price Book).
     *
     * @return array<int, string>
     */
    public function effectiveRoleKeys(): array
    {
        $keys = [(string) $this->role];

        if ($this->isSystemUser()) {
            $keys[] = UserRole::User->value;

            foreach ([
                UserRole::MarketingManager,
                UserRole::MarketingStaff,
                UserRole::CustomerServiceManager,
                UserRole::CustomerServiceStaff,
                UserRole::SalesManager,
                UserRole::SalesStaff,
                UserRole::FinanceStaff,
            ] as $legacyRole) {
                if ($this->hasRole($legacyRole)) {
                    $keys[] = $legacyRole->value;
                }
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * @deprecated Use module-specific helpers instead.
     */
    public function isAnyMarketingUser(): bool
    {
        return $this->canViewMarketingModule()
            || $this->isCustomerServiceStaff();
    }

    private function matchesLegacyBusinessAlias(UserRole $role): bool
    {
        // Giữ tương thích hành vi Admin hiện tại của hệ thống.
        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->isSystemUser()) {
            return false;
        }

        return match ($role) {
            UserRole::MarketingManager =>
                $this->belongsToDepartmentFunction(DepartmentFunction::Marketing)
                && $this->hasDepartmentManagerAuthority(),

            UserRole::MarketingStaff =>
                $this->belongsToDepartmentFunction(DepartmentFunction::Marketing),

            UserRole::CustomerServiceManager =>
                $this->belongsToDepartmentFunction(DepartmentFunction::CustomerService)
                && $this->hasDepartmentManagerAuthority(),

            UserRole::CustomerServiceStaff =>
                $this->belongsToDepartmentFunction(DepartmentFunction::CustomerService),

            UserRole::SalesManager =>
                $this->belongsToDepartmentFunction(DepartmentFunction::Sales)
                && $this->hasDepartmentManagerAuthority(),

            UserRole::SalesStaff =>
                $this->belongsToDepartmentFunction(DepartmentFunction::Sales),

            UserRole::FinanceStaff =>
                $this->belongsToDepartmentFunction(DepartmentFunction::Finance),

            default => false,
        };
    }
}
