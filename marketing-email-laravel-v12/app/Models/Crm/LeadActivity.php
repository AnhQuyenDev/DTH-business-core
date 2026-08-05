<?php

namespace App\Models\Crm;

use App\Enums\Crm\LeadActivityStatus;
use App\Enums\Crm\LeadActivityType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    protected $fillable = [
        'lead_id',
        'staff_id',
        'created_by_user_id',
        'activity_type',
        'status',
        'subject',
        'content',
        'outcome',
        'activity_at',
        'next_follow_up_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'activity_type' => LeadActivityType::class,
            'status' => LeadActivityStatus::class,
            'activity_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
