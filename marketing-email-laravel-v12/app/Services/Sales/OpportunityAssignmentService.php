<?php

namespace App\Services\Sales;

use App\Enums\Crm\StaffEmploymentStatus;
use App\Models\Crm\Staff;
use App\Models\Marketing\AuditLog;
use App\Models\Sales\Opportunity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class OpportunityAssignmentService
{
    public function reassign(
        Opportunity $opportunity,
        Staff $newOwner,
        string $reason,
        int $actorUserId,
    ): Opportunity {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'Vui lòng nhập lý do phân công lại cơ hội.',
            ]);
        }

        $actor = User::query()
            ->with([
                'staff.department',
                'staff.position',
            ])
            ->find($actorUserId);

        if (
            $actor === null
            || $actor->isAdmin()
            || ! $actor->isSalesManager()
        ) {
            throw ValidationException::withMessages([
                'assigned_staff_id' =>
                    'Chỉ quản lý bộ phận Kinh doanh được phân công lại cơ hội.',
            ]);
        }

        $this->assertEligibleSalesOwner($newOwner);

        return DB::transaction(function () use (
            $opportunity,
            $newOwner,
            $reason,
            $actorUserId,
        ): Opportunity {
            $locked = Opportunity::query()
                ->whereKey($opportunity->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->isTerminal()) {
                throw ValidationException::withMessages([
                    'assigned_staff_id' =>
                        'Không thể phân công lại cơ hội đã kết thúc.',
                ]);
            }

            $oldOwnerId = $locked->assigned_staff_id;

            if ((int) $oldOwnerId === (int) $newOwner->id) {
                throw ValidationException::withMessages([
                    'assigned_staff_id' =>
                        'Nhân viên này đang là người phụ trách cơ hội.',
                ]);
            }

            $locked->update([
                'assigned_staff_id' => $newOwner->id,
                'updated_by' => $actorUserId,
            ]);

            AuditLog::query()->create([
                'user_id' => $actorUserId,
                'action' => 'opportunity.reassigned',
                'auditable_type' => Opportunity::class,
                'auditable_id' => $locked->id,
                'old_values' => [
                    'assigned_staff_id' => $oldOwnerId,
                ],
                'new_values' => [
                    'assigned_staff_id' => $newOwner->id,
                    'reason' => $reason,
                ],
                'ip_address' => app()->runningInConsole()
                    ? null
                    : request()->ip(),
                'user_agent' => app()->runningInConsole()
                    ? null
                    : substr((string) request()->userAgent(), 0, 1000),
                'created_at' => now(),
            ]);

            return $locked->fresh([
                'assignedStaff',
                'company',
                'primaryContact',
            ]);
        });
    }

    private function assertEligibleSalesOwner(Staff $staff): void
    {
        $staff->loadMissing([
            'user',
            'department',
        ]);

        $employmentStatus = $staff->employment_status instanceof \BackedEnum
            ? $staff->employment_status->value
            : (string) $staff->employment_status;

        $blockedByAvailability = $staff->availabilities()
            ->active()
            ->where('can_receive_new_customers', false)
            ->exists();

        if (
            $employmentStatus !== StaffEmploymentStatus::Active->value
            || ! $staff->can_receive_customers
            || $staff->department?->function_key !== 'sales'
            || ! $staff->user?->is_active
            || ! $staff->user?->isSalesStaff()
            || $blockedByAvailability
        ) {
            throw ValidationException::withMessages([
                'assigned_staff_id' =>
                    'Người được chọn không phải nhân sự Kinh doanh đang hoạt động và đủ điều kiện nhận cơ hội.',
            ]);
        }
    }
}