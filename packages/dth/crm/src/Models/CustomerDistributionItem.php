<?php

namespace Dth\Crm\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDistributionItem extends Model
{
    protected $table = 'crm_customer_distribution_items';
    protected $guarded = [];
    protected $fillable = [
        'distribution_batch_id', 'customer_id', 'original_owner_agent_profile_id',
        'assigned_agent_profile_id', 'assignment_type', 'result_status', 'reason',
    ];

    public function distributionBatch(): BelongsTo
    {
        return $this->belongsTo(CustomerDistributionBatch::class, 'distribution_batch_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function originalOwner(): BelongsTo
    {
        return $this->belongsTo(CrmAgentProfile::class, 'original_owner_agent_profile_id');
    }

    public function assignedAgentProfile(): BelongsTo
    {
        return $this->belongsTo(CrmAgentProfile::class, 'assigned_agent_profile_id');
    }
}
