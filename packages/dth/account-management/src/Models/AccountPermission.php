<?php
namespace Dth\AccountManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AccountPermission extends Model
{
    protected $table = 'account_permissions';
    protected $fillable = ['key', 'module', 'name', 'description'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(AccountRole::class, 'account_permission_role', 'permission_id', 'role_id')->withTimestamps();
    }
}
