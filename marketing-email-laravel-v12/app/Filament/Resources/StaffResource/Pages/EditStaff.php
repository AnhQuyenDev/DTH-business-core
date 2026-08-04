<?php

namespace App\Filament\Resources\StaffResource\Pages;

use App\Enums\Crm\DistributionBatchType;
use App\Enums\Crm\DistributionStrategy;
use App\Enums\Crm\StaffEmploymentStatus;
use App\Filament\Resources\StaffResource;
use App\Models\Crm\CustomerAssignment;
use App\Models\Crm\Staff;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EditStaff extends EditRecord
{
    protected static string $resource = StaffResource::class;

    protected ?string $originalEmploymentStatus = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->originalEmploymentStatus = $this->record->getRawOriginal('employment_status');
        return $data;
    }

    protected function afterSave(): void
    {
        $staff = $this->record;
        $current = $staff->employment_status;

        if (!$this->originalEmploymentStatus || !$current instanceof StaffEmploymentStatus) {
            return;
        }

        $isActiveGoInactive = $this->originalEmploymentStatus === StaffEmploymentStatus::Active->value
            && in_array($current->value, [StaffEmploymentStatus::Inactive->value, StaffEmploymentStatus::Resigned->value], true);

        $isInactiveGoActive = $this->originalEmploymentStatus === StaffEmploymentStatus::Inactive->value
            && $current->value === StaffEmploymentStatus::Active->value;

        if (!$isActiveGoInactive && !$isInactiveGoActive) {
            return;
        }

        $userId = Auth::id() ?? User::query()->where('role', 'admin')->orderBy('id')->value('id') ?? 1;

        DB::transaction(function () use ($staff, $current, $isActiveGoInactive, $userId) {
            if ($isActiveGoInactive) {
                $this->handleDeactivated($staff, $current->value === StaffEmploymentStatus::Resigned->value, $userId);
            } else {
                $this->handleReactivated($staff, $userId);
            }
        });
    }

    private function handleDeactivated(Staff $staff, bool $isPermanent, int $userId): void
    {
        // Only reassign OWNER assignments — support assignments remain for tracking
        $ownerAssignments = CustomerAssignment::where('staff_id', $staff->id)
            ->where('assignment_type', 'owner')
            ->where('status', 'active')
            ->get();

        if ($ownerAssignments->isEmpty()) {
            return;
        }

        $customerIds = $ownerAssignments->pluck('customer_id');

        $originalOwnerMap = $ownerAssignments->mapWithKeys(fn ($a) => [
            $a->customer_id => $a->original_owner_staff_id ?? $staff->id,
        ]);

        $eligibleStaff = Staff::query()
            ->where('employment_status', StaffEmploymentStatus::Active->value)
            ->where('can_receive_customers', true)
            ->where('id', '!=', $staff->id)
            ->pluck('id');

        if ($eligibleStaff->isNotEmpty()) {
            $note = $isPermanent
                ? 'Auto-distributed: staff resigned'
                : 'Auto-distributed: staff inactive';

            app(\App\Services\Crm\CustomerDistributionService::class)->distribute(
                type: DistributionBatchType::StaffAbsence,
                strategy: DistributionStrategy::LeastLoaded,
                customerIds: $customerIds,
                staffIds: $eligibleStaff,
                initiatedByUserId: $userId,
                sourceStaffId: $staff->id,
                note: $note,
                originalOwnerMap: $originalOwnerMap,
            );
        }

        foreach ($ownerAssignments as $assignment) {
            $assignment->update([
                'status'           => 'ended',
                'ended_at'         => now(),
                'ended_by_user_id' => $userId,
                'note'             => $isPermanent ? 'Staff resigned' : 'Staff inactive',
            ]);
        }
    }

    private function handleReactivated(Staff $staff, int $userId): void
    {
        $supportAssignments = CustomerAssignment::where('original_owner_staff_id', $staff->id)
            ->where('assignment_type', 'support')
            ->where('status', 'active')
            ->get();

        if ($supportAssignments->isEmpty()) {
            return;
        }

        $customerIds = $supportAssignments->pluck('customer_id');

        foreach ($supportAssignments as $assignment) {
            $assignment->update([
                'status'           => 'ended',
                'ended_at'         => now(),
                'ended_by_user_id' => $userId,
                'note'             => 'Staff returned from inactive',
            ]);
        }

        app(\App\Services\Crm\CustomerDistributionService::class)->distribute(
            type: DistributionBatchType::StaffReturn,
            strategy: DistributionStrategy::LeastLoaded,
            customerIds: $customerIds,
            staffIds: collect([$staff->id]),
            initiatedByUserId: $userId,
            sourceStaffId: $staff->id,
            note: 'Auto-restored: staff reactivated',
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
