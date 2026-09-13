<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Enums\MarketingCampaignStatus;
use Dth\Marketing\Models\MarketingCampaign;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

final class MarketingCampaignLifecycleService
{
    public function __construct(private readonly MarketingAuditTrailService $audit) {}

    /** @return array<string, array<int, string>> */
    public function transitions(): array
    {
        return [
            MarketingCampaignStatus::Draft->value => [
                MarketingCampaignStatus::Active->value,
                MarketingCampaignStatus::Cancelled->value,
            ],
            MarketingCampaignStatus::Active->value => [
                MarketingCampaignStatus::Paused->value,
                MarketingCampaignStatus::Completed->value,
                MarketingCampaignStatus::Cancelled->value,
            ],
            MarketingCampaignStatus::Paused->value => [
                MarketingCampaignStatus::Active->value,
                MarketingCampaignStatus::Completed->value,
                MarketingCampaignStatus::Cancelled->value,
            ],
            MarketingCampaignStatus::Completed->value => [],
            MarketingCampaignStatus::Cancelled->value => [],
        ];
    }

    /** @return array<int, MarketingCampaignStatus> */
    public function allowedTargets(MarketingCampaign $campaign): array
    {
        $current = $this->statusValue($campaign->status);
        $targets = $this->transitions()[$current] ?? [];

        return array_values(array_filter(array_map(
            static fn (string $value): ?MarketingCampaignStatus => MarketingCampaignStatus::tryFrom($value),
            $targets,
        )));
    }

    public function assertTransition(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }

        if (! in_array($to, $this->transitions()[$from] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "Invalid Marketing Campaign transition: {$from} -> {$to}.",
            ]);
        }
    }

    public function assertDates(?string $startDate, ?string $endDate): void
    {
        if ($startDate === null || $endDate === null) {
            return;
        }

        if (Carbon::parse($endDate)->lt(Carbon::parse($startDate))) {
            throw ValidationException::withMessages([
                'end_date' => 'End date must be on or after start date.',
            ]);
        }
    }

    /** @param array<int, string> $dirty */
    public function assertChangesAllowed(string $originalStatus, array $dirty): void
    {
        if (! in_array($originalStatus, [
            MarketingCampaignStatus::Completed->value,
            MarketingCampaignStatus::Cancelled->value,
        ], true)) {
            return;
        }

        $scopeFields = [
            'name',
            'slug',
            'description',
            'start_date',
            'end_date',
            'budget',
            'currency',
            'owner_type',
            'owner_reference',
            'context',
            'service_references',
            'service_snapshot',
        ];

        if (array_intersect($scopeFields, $dirty) !== []) {
            throw ValidationException::withMessages([
                'status' => 'Completed or cancelled campaigns are historical records and their business scope is locked.',
            ]);
        }
    }

    public function transition(MarketingCampaign $campaign, MarketingCampaignStatus $target): MarketingCampaign
    {
        $from = $this->statusValue($campaign->status);
        $this->assertTransition($from, $target->value);

        if ($target === MarketingCampaignStatus::Active) {
            app(CampaignServiceScopeService::class)->assertActivationReady($campaign);
            $this->assertDates(
                $campaign->start_date?->toDateString(),
                $campaign->end_date?->toDateString(),
            );
        }

        $campaign->status = $target;
        $campaign->status_changed_at = now();

        if ($target === MarketingCampaignStatus::Completed && $campaign->end_date === null) {
            $campaign->end_date = today();
        }

        $campaign->save();
        $this->audit->log(
            'campaign.status_changed',
            $campaign,
            ['status' => $from],
            ['status' => $target->value],
        );

        return $campaign->refresh();
    }

    private function statusValue(mixed $status): string
    {
        return $status instanceof \BackedEnum ? (string) $status->value : (string) $status;
    }
}
