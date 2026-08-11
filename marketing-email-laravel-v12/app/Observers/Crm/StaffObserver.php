<?php

namespace App\Observers\Crm;

use App\Enums\Crm\DistributionBatchType;
use App\Enums\Crm\DistributionStrategy;
use App\Enums\Crm\StaffEmploymentStatus;
use App\Models\Crm\CustomerAssignment;
use App\Models\Crm\Staff;
use App\Models\User;
use App\Services\Crm\CustomerDistributionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StaffObserver
{
    public function saved(Staff $staff): void
    {
        if ($staff->user_id === null) {
            return;
        }

        $updates = [];

        if ($staff->wasChanged('full_name')) {
            $updates['name'] = $staff->full_name;
        }

        if ($staff->wasChanged('employment_status')) {
            $updates['is_active'] = $staff->employment_status === StaffEmploymentStatus::Active;
        }

        if ($updates !== []) {
            User::query()
                ->whereKey($staff->user_id)
                ->update($updates);
        }
    }

    public function saving(Staff $staff): void
    {
        if (! $staff->isDirty('employment_status')) {
            return;
        }

        $original = $staff->getOriginal('employment_status');
        $current = $staff->employment_status;

        Log::debug('StaffObserver.saving triggered', [
            'staff_id' => $staff->id,
            'original' => $original,
            'current_type' => gettype($current),
            'current_value' => $current instanceof StaffEmploymentStatus ? $current->value : $current,
            'is_dirty' => $staff->isDirty('employment_status'),
        ]);

        if (! is_string($original) || ! $current instanceof StaffEmploymentStatus) {
            Log::warning('StaffObserver: invalid types', [
                'original_type' => gettype($original),
                'current_type' => gettype($current),
            ]);

            return;
        }

        $isActiveGoInactive = $original === StaffEmploymentStatus::Active->value
            && in_array($current->value, [StaffEmploymentStatus::Inactive->value, StaffEmploymentStatus::Resigned->value], true);

        $isInactiveGoActive = $original === StaffEmploymentStatus::Inactive->value
            && $current->value === StaffEmploymentStatus::Active->value;

        Log::debug('StaffObserver: conditions', [
            'isActiveGoInactive' => $isActiveGoInactive,
            'isInactiveGoActive' => $isInactiveGoActive,
        ]);

        if (! $isActiveGoInactive && ! $isInactiveGoActive) {
            return;
        }

        DB::transaction(function () use ($staff, $current, $isActiveGoInactive) {

            Staff::query()->whereKey($staff->id)->update(['employment_status' => $current->value]);

            if ($isActiveGoInactive) {
                $this->handleDeactivated($staff, $current->value === StaffEmploymentStatus::Resigned->value);
            } else {
                $this->handleReactivated($staff);
            }
        });
    }

    private function handleDeactivated(Staff $staff, bool $isPermanent): void
    {
        $userId = Auth::id() ?? $this->resolveSystemUserId();

        // Only reassign OWNER assignments — support assignments remain for tracking
        $ownerAssignments = CustomerAssignment::where('staff_id', $staff->id)
            ->where('assignment_type', 'owner')
            ->where('status', 'active')
            ->get();

        $customerIds = $ownerAssignments->pluck('customer_id');

        $originalOwnerMap = $ownerAssignments->mapWithKeys(fn ($a) => [
            $a->customer_id => $a->original_owner_staff_id ?? $staff->id,
        ]);

        Log::debug('StaffObserver::handleDeactivated', [
            'staff_id' => $staff->id,
            'owner_assignments_count' => $ownerAssignments->count(),
            'user_id' => $userId,
        ]);

        // End owner assignments and redistribute
        if ($ownerAssignments->isNotEmpty()) {
            $eligibleStaff = Staff::query()
                ->eligibleForCustomerOwnership()
                ->where('id', '!=', $staff->id)
                ->pluck('id');

            Log::debug('StaffObserver: eligible staff', ['count' => $eligibleStaff->count(), 'ids' => $eligibleStaff->toArray()]);

            if ($eligibleStaff->isNotEmpty()) {
                $note = $isPermanent
                    ? 'Auto-distributed: staff resigned'
                    : 'Auto-distributed: staff inactive';

                app(CustomerDistributionService::class)->distribute(
                    type: DistributionBatchType::StaffAbsence,
                    strategy: DistributionStrategy::LeastLoaded,
                    customerIds: $customerIds,
                    staffIds: $eligibleStaff,
                    initiatedByUserId: $userId,
                    sourceStaffId: $staff->id,
                    originalOwnerMap: $originalOwnerMap,
                    note: $note,
                );
            } else {
                Log::warning('StaffObserver: no eligible staff for reassignment', [
                    'customer_count' => $customerIds->count(),
                ]);
            }

            foreach ($ownerAssignments as $assignment) {
                $assignment->update([
                    'status' => 'ended',
                    'ended_at' => now(),
                    'ended_by_user_id' => $userId,
                    'note' => $isPermanent ? 'Staff resigned' : 'Staff inactive',
                ]);
            }
        }

        // Support assignments are kept active so admin can still see support count
        Log::info('StaffObserver: deactivation complete', [
            'staff_id' => $staff->id,
            'ended_owner_assignments' => $ownerAssignments->count(),
            'support_assignments_preserved' => CustomerAssignment::where('staff_id', $staff->id)
                ->where('assignment_type', 'support')
                ->where('status', 'active')
                ->count(),
        ]);
    }

    private function handleReactivated(Staff $staff): void
    {
        $supportAssignments = CustomerAssignment::where('original_owner_staff_id', $staff->id)
            ->where('assignment_type', 'support')
            ->where('status', 'active')
            ->get();

        $customerIds = $supportAssignments->pluck('customer_id');
        $userId = Auth::id() ?? $this->resolveSystemUserId();

        Log::debug('StaffObserver::handleReactivated', [
            'staff_id' => $staff->id,
            'support_assignments_count' => $supportAssignments->count(),
            'user_id' => $userId,
        ]);

        if ($supportAssignments->isEmpty()) {
            Log::info('StaffObserver: no support assignments to restore', ['staff_id' => $staff->id]);

            return;
        }

        if (! $staff->fresh()->canReceiveNewCustomers()) {
            Log::warning('StaffObserver: reactivated staff is no longer eligible for Customer Care ownership', [
                'staff_id' => $staff->id,
            ]);

            return;
        }

        foreach ($supportAssignments as $assignment) {
            $assignment->update([
                'status' => 'ended',
                'ended_at' => now(),
                'ended_by_user_id' => $userId,
                'note' => 'Staff returned from inactive',
            ]);
        }

        app(CustomerDistributionService::class)->distribute(
            type: DistributionBatchType::StaffReturn,
            strategy: DistributionStrategy::LeastLoaded,
            customerIds: $customerIds,
            staffIds: collect([$staff->id]),
            initiatedByUserId: $userId,
            sourceStaffId: $staff->id,
            note: 'Auto-restored: staff reactivated',
        );

        Log::info('StaffObserver: reactivation complete', [
            'staff_id' => $staff->id,
            'restored_customers' => $customerIds->count(),
        ]);
    }

    private function resolveSystemUserId(): int
    {
        return User::query()->where('role', 'admin')->orderBy('id')->value('id') ?? 1;
    }
}
