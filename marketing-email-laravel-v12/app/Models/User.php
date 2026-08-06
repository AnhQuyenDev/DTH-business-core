<?php

namespace App\Models;

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
        $value = $role instanceof UserRole
            ? $role->value
            : $role;

        return $this->role === $value;
    }

    /**
     * @param  array<int, UserRole|string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        $values = array_map(
            fn (UserRole|string $role): string => $role instanceof UserRole
                ? $role->value
                : $role,
            $roles,
        );

        return in_array($this->role, $values, true);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin);
    }

    public function isMarketingManager(): bool
    {
        return $this->hasAnyRole([
            UserRole::Admin,
            UserRole::MarketingManager,
        ]);
    }

    public function isMarketingStaff(): bool
    {
        return $this->hasAnyRole([
            UserRole::Admin,
            UserRole::MarketingManager,
            UserRole::MarketingStaff,
        ]);
    }

    public function isCustomerServiceManager(): bool
    {
        return $this->hasAnyRole([
            UserRole::Admin,
            UserRole::CustomerServiceManager,
        ]);
    }

    public function isCustomerServiceStaff(): bool
    {
        return $this->hasAnyRole([
            UserRole::Admin,
            UserRole::CustomerServiceManager,
            UserRole::CustomerServiceStaff,
        ]);
    }

    public function isSalesManager(): bool
    {
        return $this->hasAnyRole([
            UserRole::Admin,
            UserRole::SalesManager,
        ]);
    }

    public function isSalesStaff(): bool
    {
        return $this->hasAnyRole([
            UserRole::Admin,
            UserRole::SalesManager,
            UserRole::SalesStaff,
        ]);
    }

    public function isFinanceStaff(): bool
    {
        return $this->hasAnyRole([
            UserRole::Admin,
            UserRole::FinanceStaff,
        ]);
    }

    public function isViewer(): bool
    {
        return $this->hasRole(UserRole::Viewer);
    }

    public function canViewMarketingModule(): bool
    {
        return $this->hasAnyRole([
            UserRole::Admin,
            UserRole::MarketingManager,
            UserRole::MarketingStaff,
        ]);
    }

    public function canViewCrmModule(): bool
    {
        return $this->hasAnyRole([
            UserRole::Admin,
            UserRole::MarketingManager,
            UserRole::MarketingStaff,
            UserRole::CustomerServiceManager,
            UserRole::CustomerServiceStaff,
        ]);
    }

    public function canViewSalesModule(): bool
    {
        return $this->hasAnyRole([
            UserRole::Admin,
            UserRole::CustomerServiceManager,
            UserRole::SalesManager,
            UserRole::SalesStaff,
            UserRole::FinanceStaff,
        ]);
    }

    public function canViewCustomerCareModule(): bool
    {
        return $this->hasAnyRole([
            UserRole::Admin,
            UserRole::CustomerServiceManager,
            UserRole::CustomerServiceStaff,
        ]);
    }

    public function canAccessBusinessPanel(): bool
    {
        return in_array($this->role, UserRole::values(), true);
    }

    /**
     * @deprecated Dùng helper theo module thay vì helper tổng quát này.
     */
    public function isAnyMarketingUser(): bool
    {
        return $this->hasAnyRole([
            UserRole::Admin,
            UserRole::MarketingManager,
            UserRole::MarketingStaff,
            UserRole::CustomerServiceManager,
            UserRole::CustomerServiceStaff,
            UserRole::Viewer,
        ]);
    }
}
