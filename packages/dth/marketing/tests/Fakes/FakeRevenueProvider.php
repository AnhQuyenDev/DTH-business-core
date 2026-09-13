<?php

namespace Dth\Marketing\Tests\Fakes;

use DateTimeInterface;
use Dth\Marketing\Contracts\RevenueProvider;
use Dth\Marketing\DTO\RevenueSummary;

final class FakeRevenueProvider implements RevenueProvider
{
    /** @param array<string, RevenueSummary> $summaries */
    public function __construct(private readonly array $summaries = []) {}

    public function available(): bool
    {
        return true;
    }

    public function capabilities(): array
    {
        return ['campaign_revenue' => true, 'paid_customers' => true];
    }

    public function summarizeCampaign(
        string $marketingCampaignReference,
        ?DateTimeInterface $start = null,
        ?DateTimeInterface $end = null,
    ): ?RevenueSummary {
        return $this->summaries[$marketingCampaignReference] ?? null;
    }
}
