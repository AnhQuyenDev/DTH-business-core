<?php

namespace Dth\Marketing\Integrations\Finance;

use DateTimeInterface;
use Dth\Marketing\Contracts\RevenueProvider;
use Dth\Marketing\DTO\RevenueSummary;

final class NullRevenueProvider implements RevenueProvider
{
    public function available(): bool
    {
        return false;
    }

    public function capabilities(): array
    {
        return [];
    }

    public function summarizeCampaign(
        string $marketingCampaignReference,
        ?DateTimeInterface $start = null,
        ?DateTimeInterface $end = null,
    ): ?RevenueSummary {
        return null;
    }
}
