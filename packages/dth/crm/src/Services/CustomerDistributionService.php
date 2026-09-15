<?php

namespace Dth\Crm\Services;

use Dth\Crm\Models\CrmAgentProfile;
use Dth\Crm\Models\Customer;
use Dth\Crm\Models\CustomerAssignment;
use Dth\Crm\Models\CustomerDistributionBatch;
use Dth\Crm\Models\CustomerDistributionItem;
use Dth\Crm\Support\CodeGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class CustomerDistributionService
{
    public function __construct(private CodeGenerator $codes) {}

    public function distribute(array $customerIds, ?int $actor = null): CustomerDistributionBatch
    {
        return DB::transaction(function () use ($customerIds, $actor): CustomerDistributionBatch {
            $agents = $this->eligibleAgents();

            $batch = CustomerDistributionBatch::create([
                'batch_code' => $this->codes->make('DIST'),
                'batch_type' => 'customer',
                'strategy' => 'round_robin',
                'status' => 'processing',
                'total_items' => count($customerIds),
                'created_by_user_id' => $actor,
            ]);

            if ($agents->isEmpty()) {
                $batch->update(['status' => 'failed']);

                return $batch;
            }

            $processed = 0;
            foreach (Customer::query()->whereKey($customerIds)->get() as $customer) {
                $profile = $agents[$processed % $agents->count()];

                CustomerAssignment::query()
                    ->where('customer_id', $customer->id)
                    ->where('status', 'active')
                    ->update(['status' => 'ended', 'ends_at' => now()]);

                CustomerAssignment::create([
                    'customer_id' => $customer->id,
                    'agent_profile_id' => $profile->id,
                    'assignment_type' => 'owner',
                    'status' => 'active',
                    'reason' => 'rebalance',
                    'assigned_by_user_id' => $actor,
                    'starts_at' => now(),
                ]);

                CustomerDistributionItem::create([
                    'distribution_batch_id' => $batch->id,
                    'customer_id' => $customer->id,
                    'assigned_agent_profile_id' => $profile->id,
                    'assignment_type' => 'owner',
                    'result_status' => 'assigned',
                ]);

                $processed++;
            }

            $batch->update([
                'processed_items' => $processed,
                'status' => 'completed',
            ]);

            return $batch;
        });
    }

    /** @return Collection<int, CrmAgentProfile> */
    private function eligibleAgents(): Collection
    {
        return CrmAgentProfile::query()
            ->assignmentEnabled()
            ->with('employee.availabilities')
            ->withCount([
                'customerAssignments as active_customer_count' => fn ($assignment) => $assignment->where('status', 'active'),
            ])
            ->get()
            ->filter(fn (CrmAgentProfile $profile): bool => $profile->isAvailableForNewWork())
            ->filter(function (CrmAgentProfile $profile): bool {
                if ($profile->customer_capacity === null) {
                    return true;
                }

                return (int) $profile->active_customer_count < (int) $profile->customer_capacity;
            })
            ->values();
    }
}
