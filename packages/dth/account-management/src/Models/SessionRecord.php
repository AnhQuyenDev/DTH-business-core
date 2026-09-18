<?php
namespace Dth\AccountManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionRecord extends Model
{
    protected $table = 'sessions';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $guarded = [];
    protected $hidden = ['payload'];
    public function user(): BelongsTo { return $this->belongsTo(AccountUser::class, 'user_id'); }
}
