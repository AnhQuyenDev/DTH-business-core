<?php

namespace Dth\Commercial\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpportunityInteraction extends Model
{
    protected $table = 'commercial_opportunity_interactions';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['interaction_at' => 'datetime', 'next_follow_up_at' => 'datetime', 'metadata' => 'array'];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'opportunity_id');
    }
}
