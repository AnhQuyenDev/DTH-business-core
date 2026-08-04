<?php

namespace App\Services\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\DistributionStrategy;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Staff;
use App\Models\Marketing\LandingPageSubmission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class LeadDistributionService
{
    public function distributeUnassigned(
        DistributionStrategy $strategy = DistributionStrategy::LeastLoaded,
        ?array $staffIds = null,
        ?int $landingPageId = null,
    ): array {
        $query = LandingPageSubmission::query()
            ->whereNull('assigned_staff_id')
            ->orderBy('submitted_at');

        if ($landingPageId !== null) {
            $query->where('landing_page_id', $landingPageId);
        }

        $unassigned = $query->get();

        if ($unassigned->isEmpty()) {
            return ['assigned' => 0, 'skipped' => 0, 'total' => 0];
        }

        $eligibleStaff = $this->getEligibleStaff($staffIds);
        if ($eligibleStaff->isEmpty()) {
            return ['assigned' => 0, 'skipped' => $unassigned->count(), 'total' => $unassigned->count()];
        }

        $assigned = 0;
        $skipped = 0;

        $strategyFn = $this->resolveStrategy($strategy);

        foreach ($unassigned as $submission) {
            $target = $strategyFn($eligibleStaff);
            if (! $target) {
                $skipped++;
                continue;
            }

            DB::transaction(function () use ($submission, $target, &$assigned) {
                $submission->update(['assigned_staff_id' => $target->id]);

                ContactQualification::firstOrCreate(
                    ['contact_id' => $submission->contact_id],
                    ['status' => ContactQualificationStatus::New->value]
                )->update([
                    'assigned_staff_id' => $target->id,
                    'status' => ContactQualificationStatus::Assigned->value,
                ]);
            });

            $assigned++;
        }

        return [
            'assigned' => $assigned,
            'skipped' => $skipped,
            'total' => $unassigned->count(),
        ];
    }

    private function getEligibleStaff(?array $staffIds = null): Collection
    {
        $query = Staff::query()
            ->where('employment_status', 'active')
            ->where('can_receive_customers', true)
            ->whereDoesntHave('availabilities', fn ($q) => $q
                ->active()
                ->where('can_receive_new_customers', false)
            );

        if ($staffIds !== null) {
            $query->whereIn('id', $staffIds);
        }

        return $query->orderBy('full_name')->get();
    }

    private function resolveStrategy(DistributionStrategy $strategy): callable
    {
        return match ($strategy) {
            DistributionStrategy::RoundRobin => function (Collection $staff) {
                static $index = 0;
                return $staff->get($index++ % $staff->count());
            },
            DistributionStrategy::LeastLoaded => function (Collection $staff) {
                $loads = $staff->mapWithKeys(fn (Staff $s) => [
                    $s->id => ContactQualification::where('assigned_staff_id', $s->id)
                        ->whereNotIn('status', [
                            ContactQualificationStatus::Converted->value,
                            ContactQualificationStatus::Duplicate->value,
                            ContactQualificationStatus::Spam->value,
                            ContactQualificationStatus::Archived->value,
                        ])
                        ->count(),
                ]);
                $minLoad = $loads->min();
                $candidates = $staff->filter(fn (Staff $s) => $loads[$s->id] === $minLoad);
                return $candidates->sortBy('id')->first();
            },
            DistributionStrategy::Weighted => function (Collection $staff) {
                $loads = $staff->mapWithKeys(fn (Staff $s) => [
                    $s->id => ContactQualification::where('assigned_staff_id', $s->id)
                        ->whereNotIn('status', [
                            ContactQualificationStatus::Converted->value,
                            ContactQualificationStatus::Duplicate->value,
                            ContactQualificationStatus::Spam->value,
                            ContactQualificationStatus::Archived->value,
                        ])
                        ->count() / max($s->distribution_weight, 0.01),
                ]);
                $minLoad = $loads->min();
                $candidates = $staff->filter(fn (Staff $s) => $loads[$s->id] === $minLoad);
                return $candidates->sortBy('id')->first();
            },
            default => fn (Collection $staff) => $staff->sortBy('id')->first(),
        };
    }
}
