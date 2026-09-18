<?php
namespace Dth\AccountManagement\Models;

use Dth\AccountManagement\Enums\InvitationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AccountInvitation extends Model
{
    protected $table = 'account_invitations';
    protected $fillable = ['email', 'name', 'token_hash', 'status', 'expires_at', 'sent_at', 'accepted_at', 'invited_by_user_id', 'accepted_by_user_id', 'metadata'];
    protected function casts(): array
    {
        return ['status' => InvitationStatus::class, 'expires_at' => 'datetime', 'sent_at' => 'datetime', 'accepted_at' => 'datetime', 'metadata' => 'array'];
    }
    public function roles(): BelongsToMany { return $this->belongsToMany(AccountRole::class, 'account_invitation_role', 'invitation_id', 'role_id')->withTimestamps(); }
    public function groups(): BelongsToMany { return $this->belongsToMany(AccountGroup::class, 'account_invitation_group', 'invitation_id', 'group_id')->withTimestamps(); }
    public function invitedBy(): BelongsTo { return $this->belongsTo(AccountUser::class, 'invited_by_user_id'); }
    public function acceptedBy(): BelongsTo { return $this->belongsTo(AccountUser::class, 'accepted_by_user_id'); }
}
