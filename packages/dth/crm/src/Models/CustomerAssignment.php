<?php

namespace Dth\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAssignment extends Model
{
    protected $table = 'crm_customer_assignments';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function agentProfile(): BelongsTo
    {
        return $this->belongsTo(CrmAgentProfile::class, 'agent_profile_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
