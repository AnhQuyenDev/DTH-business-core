<?php

namespace App\Models;

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
        return $this->isAnyMarketingUser();
    }

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isMarketingManager(): bool
    {
        return in_array($this->role, ['admin', 'marketing_manager'], true);
    }

    public function isMarketingStaff(): bool
    {
        return in_array($this->role, ['admin', 'marketing_manager', 'marketing_staff'], true);
    }

    public function isCustomerServiceStaff(): bool
    {
        return in_array($this->role, ['admin', 'customer_service_manager', 'customer_service_staff'], true);
    }

    public function isCustomerServiceManager(): bool
    {
        return in_array($this->role, ['admin', 'customer_service_manager'], true);
    }

    public function isAnyMarketingUser(): bool
    {
        return in_array($this->role, ['admin', 'marketing_manager', 'marketing_staff', 'customer_service_manager', 'customer_service_staff', 'viewer'], true);
    }
}
