<?php

namespace Dth\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    protected $table = 'crm_lead_activities';
    protected $guarded = [];
    protected $fillable = [
        'lead_id', 'agent_profile_id', 'type', 'status', 'subject', 'content', 'outcome',
        'activity_at', 'next_follow_up_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'activity_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function agentProfile(): BelongsTo
    {
        return $this->belongsTo(CrmAgentProfile::class, 'agent_profile_id');
    }
}
