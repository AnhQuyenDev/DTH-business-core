<?php

namespace App\Models\Sales;

use App\Models\Crm\Staff;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpportunityInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'opportunity_id',
        'staff_id',
        'interaction_type',
        'subject',
        'content',
        'outcome',
        'interaction_at',
        'next_follow_up_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'interaction_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
