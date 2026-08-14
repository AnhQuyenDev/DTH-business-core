<?php

namespace App\Models;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\Crm\PositionAuthority;
use App\Enums\UserRole;
use App\Models\Crm\Staff;
use App\Models\Marketing\AuditLog;
use App\Services\Security\RbacSyncService;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected static function booted(): void
    {
        static::saved(function (self $user): void {
            try {
                app(RbacSyncService::class)->syncUserCanonicalRoles($user);
            } catch (\Throwable) {
                // Permission tables may not exist during initial migration/bootstrap.
            }
        });
    }

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

    public function hasLegacyRole(UserRole|string $role): bool
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
    public function hasAnyLegacyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasLegacyRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function isSuperAdmin(): bool
    {
        if ($this->role === UserRole::SuperAdmin->value) {
            return true;
        }

        try {
            return $this->hasRole('super_admin');
        } catch (\Throwable) {
            return false;
        }
    }

    public function isAdmin(): bool
    {
        // Backward-compatible system-admin check: Super Admin must pass all
        // legacy Admin-only infrastructure guards while remaining separately
        // identifiable through isSuperAdmin().
        return $this->isSuperAdmin() || $this->role === UserRole::Admin->value;
    }

    public function isExecutive(): bool
    {
        return $this->role === UserRole::Executive->value;
    }

    public function isSystemUser(): bool
    {
        return $this->hasLegacyRole(UserRole::User);
    }

    public function isViewer(): bool
    {
        return $this->role === UserRole::Viewer->value;
    }

    public function canReadAcrossBusiness(): bool
    {
        return $this->isSuperAdmin() || $this->isExecutive() || $this->isViewer();
    }

    public function departmentFunction(): ?DepartmentFunction
    {
        $this->loadMissing('staff.department');

        return $this->staff?->department?->function();
    }

    public function primaryBusinessFunction(): ?DepartmentFunction
    {
        $this->loadMissing('staff');

        return $this->staff?->primaryBusinessFunction();
    }

    public function hasBusinessFunction(DepartmentFunction|string $function): bool
    {
        $this->loadMissing('staff');

        return $this->staff?->hasBusinessFunction($function) ?? false;
    }

    public function businessAuthorityFor(DepartmentFunction|string $function): ?PositionAuthority
    {
        $this->loadMissing('staff');

        return $this->staff?->businessAuthorityFor($function);
    }

    public function hasBusinessManagerAuthority(DepartmentFunction|string $function): bool
    {
        $this->loadMissing('staff');

        return $this->staff?->hasBusinessManagerAuthority($function) ?? false;
    }

    /** @return array<int, DepartmentFunction> */
    public function managedBusinessFunctions(): array
    {
        $this->loadMissing('staff.businessFunctions');

        if (! $this->staff) {
            return [];
        }

        return collect(DepartmentFunction::cases())
            ->filter(fn (DepartmentFunction $function): bool => ! in_array($function, [DepartmentFunction::Admin, DepartmentFunction::Other], true)
                && $this->staff->hasBusinessManagerAuthority($function)
            )
            ->values()
            ->all();
    }

    public function isBusinessManager(): bool
    {
        return $this->managedBusinessFunctions() !== [];
    }

    public function belongsToDepartmentFunction(DepartmentFunction $function): bool
    {
        return $this->hasBusinessFunction($function);
    }

    public function hasDepartmentManagerAuthority(): bool
    {
        $this->loadMissing('staff.position');

        return $this->staff?->position?->grantsDepartmentManagerAuthority()
            ?? false;
    }

    public function isMarketingManager(): bool
    {
        return $this->isSuperAdmin() || $this->hasLegacyRole(UserRole::MarketingManager);
    }

    public function isMarketingStaff(): bool
    {
        return $this->isSuperAdmin() || $this->hasLegacyRole(UserRole::MarketingStaff);
    }

    public function isCustomerServiceManager(): bool
    {
        return $this->isSuperAdmin() || $this->hasLegacyRole(UserRole::CustomerServiceManager);
    }

    public function isCustomerServiceStaff(): bool
    {
        return $this->isSuperAdmin() || $this->hasLegacyRole(UserRole::CustomerServiceStaff);
    }

    public function isSalesManager(): bool
    {
        return $this->isSuperAdmin() || $this->hasLegacyRole(UserRole::SalesManager);
    }

    public function isSalesStaff(): bool
    {
        return $this->isSuperAdmin() || $this->hasLegacyRole(UserRole::SalesStaff);
    }

    public function isFinanceStaff(): bool
    {
        return $this->isSuperAdmin() || $this->hasLegacyRole(UserRole::FinanceStaff);
    }

    public function canViewMarketingModule(): bool
    {
        return $this->isSuperAdmin()
            || $this->isAdmin()
            || $this->canReadAcrossBusiness()
            || $this->isMarketingStaff();
    }

    public function canViewCrmModule(): bool
    {
        return $this->isSuperAdmin()
            || $this->isAdmin()
            || $this->canReadAcrossBusiness()
            || $this->isMarketingStaff()
            || $this->isSalesStaff();
    }

    public function canViewSalesModule(): bool
    {
        return $this->isSuperAdmin()
            || $this->isAdmin()
            || $this->canReadAcrossBusiness()
            || $this->isSalesStaff()
            || $this->isFinanceStaff();
    }

    public function canViewCustomerCareModule(): bool
    {
        return $this->isSuperAdmin()
            || $this->isAdmin()
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
                if ($this->hasLegacyRole($legacyRole)) {
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
        return $this->canViewMarketingModule();
    }

    private function matchesLegacyBusinessAlias(UserRole $role): bool
    {
        // System Admin is infrastructure/configuration authority, not an
        // implicit business-role holder. Business workflow permissions must
        // come from the employee's Department + Position authority.
        if ($this->isSuperAdmin() || $this->isAdmin() || $this->canReadAcrossBusiness()) {
            return false;
        }

        if (! $this->isSystemUser()) {
            return false;
        }

        return match ($role) {
            UserRole::MarketingManager => $this->hasBusinessFunction(DepartmentFunction::Marketing)
                && $this->hasBusinessManagerAuthority(DepartmentFunction::Marketing),

            UserRole::MarketingStaff => $this->hasBusinessFunction(DepartmentFunction::Marketing),

            UserRole::CustomerServiceManager => $this->hasBusinessFunction(DepartmentFunction::CustomerService)
                && $this->hasBusinessManagerAuthority(DepartmentFunction::CustomerService),

            UserRole::CustomerServiceStaff => $this->hasBusinessFunction(DepartmentFunction::CustomerService),

            UserRole::SalesManager => $this->hasBusinessFunction(DepartmentFunction::Sales)
                && $this->hasBusinessManagerAuthority(DepartmentFunction::Sales),

            UserRole::SalesStaff => $this->hasBusinessFunction(DepartmentFunction::Sales),

            UserRole::FinanceStaff => $this->hasBusinessFunction(DepartmentFunction::Finance),

            default => false,
        };
    }
}
