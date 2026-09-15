<?php

namespace Dth\HumanResource\Models;

use Dth\HumanResource\Enums\AvailabilityStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAvailability extends Model
{
    protected $table = 'hr_employee_availabilities';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => AvailabilityStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'can_receive_new_work' => 'boolean',
            'can_support_existing_work' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $availability): void {
            $status = $availability->status instanceof AvailabilityStatus
                ? $availability->status
                : AvailabilityStatus::tryFrom((string) $availability->status);

            if ($status) {
                if ($availability->can_receive_new_work === null) {
                    $availability->can_receive_new_work = $status->canReceiveNewWork();
                }
                if ($availability->can_support_existing_work === null) {
                    $availability->can_support_existing_work = $status->canSupportExistingWork();
                }
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo((string) config('auth.providers.users.model', \App\Models\User::class), 'approved_by_user_id');
    }
}
