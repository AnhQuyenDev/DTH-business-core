<?php

namespace App\Services\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\DistributionStrategy;
use App\Enums\Crm\LeadIntakeStatus;
use App\Enums\Crm\StaffEmploymentStatus;
use App\Models\Crm\Lead;
use App\Models\Crm\Staff;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class LeadDistributionService
{
    public function __construct(
        private readonly LeadAssignmentService $leadAssignmentService,
    ) {}

    public function distributeUnassigned(
        DistributionStrategy $strategy = DistributionStrategy::LeastLoaded,
        ?array $staffIds = null,
        ?int $landingPageId = null,
        ?int $assignedByUserId = null,
    ): array {
        if ($strategy === DistributionStrategy::Manual) {
            throw ValidationException::withMessages([
                'strategy' => __('validation.manual_strategy_not_automatic'),
            ]);
        }

        $query = Lead::query()
            ->whereNull('assigned_staff_id')
            ->where('intake_status', LeadIntakeStatus::New->value)
            ->whereHas(
                'qualification',
                fn ($query) => $query->where(
                    'status',
                    ContactQualificationStatus::New->value
                )
            )
            ->with([
                'qualification',
                'company.accountOwner.availabilities',
                'submission',
            ])
            ->orderBy('created_at')
            ->orderBy('id');

        if ($landingPageId !== null) {
            $query->whereHas(
                'submission',
                fn ($query) => $query->where(
                    'landing_page_id',
                    $landingPageId
                )
            );
        }

        $unassigned = $query->get();

        if ($unassigned->isEmpty()) {
            return [
                'assigned' => 0,
                'skipped' => 0,
                'total' => 0,
                'errors' => [],
            ];
        }

        $eligibleStaff = $this->getEligibleStaff($staffIds);

        if ($eligibleStaff->isEmpty()) {
            return [
                'assigned' => 0,
                'skipped' => $unassigned->count(),
                'total' => $unassigned->count(),
                'errors' => [__('validation.no_eligible_staff')],
            ];
        }

        $assigned = 0;
        $skipped = 0;
        $errors = [];
        $roundRobinIndex = 0;

        foreach ($unassigned as $lead) {
            $target = $this->resolveTarget(
                lead: $lead,
                eligibleStaff: $eligibleStaff,
                strategy: $strategy,
                roundRobinIndex: $roundRobinIndex,
            );

            if ($target === null) {
                $skipped++;
                $errors[] = "{$lead->lead_code}: "
                    .__('validation.company_owner_not_available');

                continue;
            }

            try {
                $this->leadAssignmentService->assign(
                    lead: $lead,
                    staff: $target,
                    assignedByUserId: $assignedByUserId,
                    reason: 'Tự động phân phối theo '.$strategy->value,
                );

                $assigned++;
            } catch (ValidationException $exception) {
                $skipped++;
                $errors[] = "{$lead->lead_code}: "
                    .collect($exception->errors())
                        ->flatten()
                        ->first();
            }
        }

        return [
            'assigned' => $assigned,
            'skipped' => $skipped,
            'total' => $unassigned->count(),
            'errors' => array_values(array_unique($errors)),
        ];
    }

    private function getEligibleStaff(?array $staffIds = null): Collection
    {
        $query = Staff::query()
            ->where(
                'employment_status',
                StaffEmploymentStatus::Active->value
            )
            ->where('can_receive_customers', true)
            ->whereDoesntHave(
                'availabilities',
                fn ($query) => $query
                    ->active()
                    ->where('can_receive_new_customers', false)
            );

        if ($staffIds !== null) {
            $query->whereIn('id', $staffIds);
        }

        return $query
            ->orderBy('id')
            ->get();
    }

    private function resolveTarget(
        Lead $lead,
        Collection $eligibleStaff,
        DistributionStrategy $strategy,
        int &$roundRobinIndex,
    ): ?Staff {
        $accountOwnerId = $lead->company?->account_owner_staff_id;

        if ($accountOwnerId !== null) {
            // Không giao sang Staff khác khi Account Owner không sẵn sàng.
            // Đưa Lead vào skipped để Manager xử lý thủ công.
            return $eligibleStaff->firstWhere('id', $accountOwnerId);
        }

        return match ($strategy) {
            DistributionStrategy::RoundRobin => $this->roundRobin(
                $eligibleStaff,
                $roundRobinIndex,
            ),
            DistributionStrategy::LeastLoaded => $this->leastLoaded(
                $eligibleStaff
            ),
            DistributionStrategy::Weighted => $this->weighted(
                $eligibleStaff
            ),
            DistributionStrategy::Manual => null,
        };
    }

    private function roundRobin(
        Collection $staff,
        int &$index,
    ): ?Staff {
        if ($staff->isEmpty()) {
            return null;
        }

        $target = $staff->values()->get($index % $staff->count());
        $index++;

        return $target;
    }

    private function leastLoaded(Collection $staff): ?Staff
    {
        return $staff
            ->sort(function (Staff $left, Staff $right): int {
                return [
                    $this->openLeadLoad($left),
                    $left->id,
                ] <=> [
                    $this->openLeadLoad($right),
                    $right->id,
                ];
            })
            ->first();
    }

    private function weighted(Collection $staff): ?Staff
    {
        return $staff
            ->sort(function (Staff $left, Staff $right): int {
                $leftLoad = $this->openLeadLoad($left)
                    / max((float) $left->distribution_weight, 0.01);
                $rightLoad = $this->openLeadLoad($right)
                    / max((float) $right->distribution_weight, 0.01);

                return [$leftLoad, $left->id]
                    <=> [$rightLoad, $right->id];
            })
            ->first();
    }

    private function openLeadLoad(Staff $staff): int
    {
        return Lead::query()
            ->where('assigned_staff_id', $staff->id)
            ->whereIn('intake_status', [
                LeadIntakeStatus::New->value,
                LeadIntakeStatus::Active->value,
            ])
            ->count();
    }
}
