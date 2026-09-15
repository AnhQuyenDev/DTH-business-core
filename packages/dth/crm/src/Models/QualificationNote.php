<?php

namespace Dth\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualificationNote extends Model
{
    protected $table = 'crm_qualification_notes';
    protected $guarded = [];

    public function qualification(): BelongsTo
    {
        return $this->belongsTo(ContactQualification::class, 'qualification_id');
    }

    public function agentProfile(): BelongsTo
    {
        return $this->belongsTo(CrmAgentProfile::class, 'agent_profile_id');
    }
}
