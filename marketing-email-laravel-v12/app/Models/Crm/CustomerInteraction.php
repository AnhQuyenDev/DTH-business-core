<?php

namespace App\Models\Crm;

use App\Enums\Crm\InteractionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerInteraction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'staff_id',
        'customer_assignment_id',
        'interaction_type',
        'subject',
        'content',
        'outcome',
        'status',
        'interaction_at',
        'next_follow_up_at',
        'is_support_action',
        'original_owner_staff_id',
    ];

    protected function casts(): array
    {
        return [
            'interaction_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
            'is_support_action' => 'boolean',
            'status' => InteractionStatus::class,
        ];
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->whereNotNull('next_follow_up_at')
            ->whereIn('status', [InteractionStatus::Scheduled, InteractionStatus::Rescheduled]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(CustomerAssignment::class, 'customer_assignment_id');
    }

    public function originalOwner(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'original_owner_staff_id');
    }
}
