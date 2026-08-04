<?php

namespace App\Models\Crm;

use App\Enums\Crm\CustomerAssignmentStatus;
use App\Enums\Crm\CustomerAssignmentType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'staff_id',
        'assignment_type',
        'status',
        'starts_at',
        'ends_at',
        'assigned_by_user_id',
        'reason',
        'original_owner_staff_id',
        'distribution_batch_id',
        'note',
        'ended_by_user_id',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'assignment_type' => CustomerAssignmentType::class,
            'status' => CustomerAssignmentStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function endedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ended_by_user_id');
    }

    public function originalOwner(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'original_owner_staff_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(CustomerDistributionBatch::class, 'distribution_batch_id');
    }
}
