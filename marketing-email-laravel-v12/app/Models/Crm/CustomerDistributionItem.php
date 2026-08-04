<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDistributionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'distribution_batch_id',
        'customer_id',
        'original_owner_staff_id',
        'assigned_staff_id',
        'assignment_type',
        'result_status',
        'reason',
        'customer_assignment_id',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(CustomerDistributionBatch::class, 'distribution_batch_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function originalOwner(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'original_owner_staff_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_staff_id');
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(CustomerAssignment::class, 'customer_assignment_id');
    }
}
