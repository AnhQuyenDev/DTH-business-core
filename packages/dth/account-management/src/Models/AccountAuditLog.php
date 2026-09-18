<?php
namespace Dth\AccountManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountAuditLog extends Model
{
    protected $table = 'account_audit_logs';
    public $timestamps = false;
    protected $fillable = ['user_id', 'module', 'event', 'subject_type', 'subject_id', 'description', 'old_values', 'new_values', 'ip_address', 'user_agent', 'created_at'];
    protected function casts(): array { return ['old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime']; }
    public function actor(): BelongsTo { return $this->belongsTo(AccountUser::class, 'user_id'); }
}
