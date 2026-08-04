<?php

namespace App\Models\Crm;

use App\Enums\Crm\DistributionBatchStatus;
use App\Enums\Crm\DistributionBatchType;
use App\Enums\Crm\DistributionStrategy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerDistributionBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_code',
        'type',
        'status',
        'source_staff_id',
        'effective_from',
        'effective_until',
        'strategy',
        'total_customers',
        'total_assigned',
        'initiated_by_user_id',
        'completed_at',
        'note',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'type' => DistributionBatchType::class,
            'status' => DistributionBatchStatus::class,
            'strategy' => DistributionStrategy::class,
            'effective_from' => 'datetime',
            'effective_until' => 'datetime',
            'completed_at' => 'datetime',
            'total_customers' => 'integer',
            'total_assigned' => 'integer',
            'payload' => 'json',
        ];
    }

    public function sourceStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'source_staff_id');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CustomerDistributionItem::class, 'distribution_batch_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(CustomerAssignment::class, 'distribution_batch_id');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', DistributionBatchStatus::Completed);
    }
}
