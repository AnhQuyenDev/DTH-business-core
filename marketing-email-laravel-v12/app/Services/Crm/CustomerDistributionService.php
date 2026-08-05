<?php

namespace App\Services\Crm;

use App\Enums\Crm\CustomerAssignmentReason;
use App\Enums\Crm\CustomerAssignmentStatus;
use App\Enums\Crm\CustomerAssignmentType;
use App\Enums\Crm\DistributionBatchStatus;
use App\Enums\Crm\DistributionBatchType;
use App\Enums\Crm\DistributionStrategy;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerAssignment;
use App\Models\Crm\CustomerDistributionBatch;
use App\Models\Crm\Staff;
use App\Services\Marketing\AuditLogService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class CustomerDistributionService
{
    public function __construct(
        private AuditLogService $auditLog,
    ) {}

    public function distribute(
        DistributionBatchType $type,
        DistributionStrategy $strategy,
        Collection $customerIds,
        Collection $staffIds,
        int $initiatedByUserId,
        ?int $sourceStaffId = null,
        ?string $note = null,
        ?Collection $originalOwnerMap = null,
    ): CustomerDistributionBatch {
        return DB::transaction(function () use (
            $type, $strategy, $customerIds, $staffIds,
            $initiatedByUserId, $sourceStaffId, $note, $originalOwnerMap,
        ) {
            $customers = Customer::whereIn('id', $customerIds)->lockForUpdate()->get();
            $staffList = Staff::whereIn('id', $staffIds)
                ->where('employment_status', 'active')
                ->where('can_receive_customers', true)
                ->whereDoesntHave('availabilities', fn ($q) => $q
                    ->active()
                    ->where('can_receive_new_customers', false)
                )
                ->get()
                ->filter(fn (Staff $s) => $s->hasCapacity())
                ->values();

            $batch = CustomerDistributionBatch::create([
                'batch_code' => 'DIST-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                'type' => $type->value,
                'status' => DistributionBatchStatus::Processing->value,
                'source_staff_id' => $sourceStaffId,
                'effective_from' => now(),
                'strategy' => $strategy->value,
                'total_customers' => $customers->count(),
                'initiated_by_user_id' => $initiatedByUserId,
                'note' => $note,
            ]);

            $assignedCount = 0;

            $assignmentType = $type === DistributionBatchType::StaffAbsence
                ? CustomerAssignmentType::Support->value
                : CustomerAssignmentType::Owner->value;

            // Pre-load current counts per staff (owner + support) for in-transaction tracking
            $pendingLoads = $staffList->mapWithKeys(fn (Staff $s) => [
                $s->id => CustomerAssignment::where('staff_id', $s->id)
                    ->whereIn('assignment_type', ['owner', 'support'])
                    ->where('status', 'active')
                    ->count(),
            ]);

            $strategyFn = $this->resolveStrategy($strategy, $staffList);

            foreach ($customers as $i => $customer) {
                // Filter out staff who would exceed capacity with this assignment
                $eligible = $staffList->filter(fn (Staff $s) => $s->customer_capacity === null
                    || $pendingLoads[$s->id] < $s->customer_capacity);

                $targetStaff = $strategyFn($i, $customer, $eligible, $pendingLoads);

                if (! $targetStaff) {
                    $batch->items()->create([
                        'customer_id' => $customer->id,
                        'assigned_staff_id' => $staffList->first()?->id,
                        'assignment_type' => $assignmentType,
                        'result_status' => 'skipped',
                        'reason' => 'No eligible staff available or all at capacity',
                    ]);

                    $this->auditLog->log('distribution.skipped', $customer, [], [
                        'batch_id' => $batch->id,
                        'reason' => 'No eligible staff available or all at capacity',
                    ]);

                    continue;
                }

                $pendingLoads->put($targetStaff->id, $pendingLoads->get($targetStaff->id) + 1);

                $assignment = CustomerAssignment::create([
                    'customer_id' => $customer->id,
                    'staff_id' => $targetStaff->id,
                    'assignment_type' => $assignmentType,
                    'status' => CustomerAssignmentStatus::Active->value,
                    'starts_at' => now(),
                    'assigned_by_user_id' => $initiatedByUserId,
                    'reason' => $this->mapReason($type)->value,
                    'original_owner_staff_id' => $originalOwnerMap?->get($customer->id) ?? $sourceStaffId,
                    'distribution_batch_id' => $batch->id,
                    'note' => $note,
                ]);

                $batch->items()->create([
                    'customer_id' => $customer->id,
                    'original_owner_staff_id' => $originalOwnerMap?->get($customer->id) ?? $customer->currentOwner?->id,
                    'assigned_staff_id' => $targetStaff->id,
                    'assignment_type' => $assignmentType,
                    'result_status' => 'success',
                    'customer_assignment_id' => $assignment->id,
                ]);

                $assignedCount++;

                $this->auditLog->log('customer.'.$assignmentType.'_assigned', $customer, [], [
                    'staff_id' => $targetStaff->id,
                    'assignment_type' => $assignmentType,
                    'batch_id' => $batch->id,
                ]);
            }

            $batch->update([
                'total_assigned' => $assignedCount,
                'status' => DistributionBatchStatus::Completed->value,
                'completed_at' => now(),
            ]);

            $this->auditLog->log('distribution.completed', $batch, [], [
                'batch_id' => $batch->id,
                'total' => $assignedCount,
            ]);

            return $batch;
        });
    }

    private function resolveStrategy(DistributionStrategy $strategy, Collection $staffList): callable
    {
        return match ($strategy) {
            DistributionStrategy::RoundRobin => function (int $index, Customer $customer, Collection $staff, Collection $loads) {
                return $staff->get($index % $staff->count());
            },
            DistributionStrategy::LeastLoaded => function (int $index, Customer $customer, Collection $staff, Collection $loads) {
                $minLoad = $loads->only($staff->pluck('id'))->min();
                $candidates = $staff->filter(fn (Staff $s) => ($loads[$s->id] ?? 0) === $minLoad);

                return $candidates->sortBy('id')->first();
            },
            DistributionStrategy::Weighted => function (int $index, Customer $customer, Collection $staff, Collection $loads) {
                $weighted = $loads->only($staff->pluck('id'))->mapWithKeys(fn ($load, $staffId) => [
                    $staffId => $load / max($staff->firstWhere('id', $staffId)?->distribution_weight ?? 1, 0.01),
                ]);
                $minLoad = $weighted->min();
                $candidates = $staff->filter(fn (Staff $s) => ($weighted[$s->id] ?? 0) === $minLoad);

                return $candidates->sortBy('id')->first();
            },
            DistributionStrategy::Manual => function (int $index, Customer $customer, Collection $staff, Collection $loads) {
                return $staff->get($index % max($staff->count(), 1));
            },
        };
    }

    private function mapReason(DistributionBatchType $type): CustomerAssignmentReason
    {
        return match ($type) {
            DistributionBatchType::Initial => CustomerAssignmentReason::InitialDistribution,
            DistributionBatchType::NewCustomer => CustomerAssignmentReason::NewCustomer,
            DistributionBatchType::StaffAbsence => CustomerAssignmentReason::StaffAbsence,
            DistributionBatchType::StaffReturn => CustomerAssignmentReason::StaffReturn,
            DistributionBatchType::Rebalance => CustomerAssignmentReason::Rebalance,
            DistributionBatchType::Manual => CustomerAssignmentReason::Manual,
        };
    }
}
