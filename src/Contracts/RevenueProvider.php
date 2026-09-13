<?php

namespace Dth\Marketing\Contracts;

use DateTimeInterface;
use Dth\Marketing\DTO\RevenueSummary;

interface RevenueProvider extends IntegrationProvider
{
    public function summarizeCampaign(
        string $marketingCampaignReference,
        ?DateTimeInterface $start = null,
        ?DateTimeInterface $end = null,
    ): ?RevenueSummary;
}
