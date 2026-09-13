<?php

namespace Dth\Marketing\Contracts;

use Dth\Marketing\DTO\EmailCampaignReference;

interface EmailMarketingBridge extends IntegrationProvider
{
    /**
     * @return array<int, EmailCampaignReference>
     */
    public function campaignsForMarketingCampaign(string $marketingCampaignReference): array;

    public function campaignAdminUrl(string $emailCampaignReference): ?string;
}
