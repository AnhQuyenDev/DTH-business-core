<?php
namespace Dth\AccountManagement\Models;

use Dth\AccountManagement\Enums\GroupType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountGroup extends Model
{
    protected $table = 'account_groups';
    protected $fillable = ['parent_id', 'code', 'name', 'type', 'description', 'is_active'];
    protected function casts(): array { return ['type' => GroupType::class, 'is_active' => 'boolean']; }
    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id'); }
    public function users(): BelongsToMany { return $this->belongsToMany(AccountUser::class, 'account_group_user', 'group_id', 'user_id')->withTimestamps(); }
}
