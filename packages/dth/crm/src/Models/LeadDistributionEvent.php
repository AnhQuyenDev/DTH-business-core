<?php

namespace Dth\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadDistributionEvent extends Model
{
    protected $table = 'crm_lead_distribution_events';
    protected $guarded = [];
    protected $fillable = [
        'lead_id', 'from_agent_profile_id', 'to_agent_profile_id', 'strategy', 'event', 'status',
        'reason', 'actor_user_id', 'responded_at',
    ];

    protected function casts(): array
    {
        return ['responded_at' => 'datetime'];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function fromAgentProfile(): BelongsTo
    {
        return $this->belongsTo(CrmAgentProfile::class, 'from_agent_profile_id');
    }

    public function toAgentProfile(): BelongsTo
    {
        return $this->belongsTo(CrmAgentProfile::class, 'to_agent_profile_id');
    }
}
