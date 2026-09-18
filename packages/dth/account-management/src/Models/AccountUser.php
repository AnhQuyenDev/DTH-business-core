<?php
namespace Dth\AccountManagement\Models;

use Dth\AccountManagement\Enums\AccountStatus;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class AccountUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';
    protected $fillable = [
        'name', 'email', 'password', 'phone', 'preferred_locale', 'timezone', 'account_status',
        'must_change_password', 'locked_until', 'last_login_at', 'last_login_ip', 'failed_login_attempts', 'account_metadata',
    ];
    protected $hidden = ['password', 'remember_token'];
    protected function casts(): array
    {
        return [
            'password' => 'hashed', 'account_status' => AccountStatus::class, 'must_change_password' => 'boolean',
            'locked_until' => 'datetime', 'last_login_at' => 'datetime', 'account_metadata' => 'array',
        ];
    }

    public function roles(): BelongsToMany { return $this->belongsToMany(AccountRole::class, 'account_role_user', 'user_id', 'role_id')->withTimestamps(); }
    public function groups(): BelongsToMany { return $this->belongsToMany(AccountGroup::class, 'account_group_user', 'user_id', 'group_id')->withTimestamps(); }
    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(AccountPermission::class, 'account_user_permissions', 'user_id', 'permission_id')->withPivot('effect')->withTimestamps();
    }
    public function sessions(): HasMany { return $this->hasMany(SessionRecord::class, 'user_id'); }

    public function isLocked(): bool { return $this->locked_until?->isFuture() ?? false; }
    public function isActive(): bool { return ($this->account_status?->value ?? (string) $this->account_status) === 'active' && ! $this->isLocked(); }
}
