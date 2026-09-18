<?php
namespace Dth\AccountManagement\Models;

use Dth\AccountManagement\Enums\DataScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AccountRole extends Model
{
    protected $table = 'account_roles';
    protected $fillable = ['key', 'name', 'description', 'color', 'data_scope', 'is_system', 'is_active'];
    protected function casts(): array { return ['data_scope' => DataScope::class, 'is_system' => 'boolean', 'is_active' => 'boolean']; }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(AccountPermission::class, 'account_permission_role', 'role_id', 'permission_id')->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(AccountUser::class, 'account_role_user', 'role_id', 'user_id')->withTimestamps();
    }
}
