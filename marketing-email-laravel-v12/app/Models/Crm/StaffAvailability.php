<?php

namespace App\Models\Crm;

use App\Enums\Crm\StaffAvailabilityStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'status',
        'starts_at',
        'ends_at',
        'can_receive_new_customers',
        'can_support_customers',
        'reason',
        'note',
        'approved_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => StaffAvailabilityStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'can_receive_new_customers' => 'boolean',
            'can_support_customers' => 'boolean',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function scopeActive($query)
    {
        return $query->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }
}
