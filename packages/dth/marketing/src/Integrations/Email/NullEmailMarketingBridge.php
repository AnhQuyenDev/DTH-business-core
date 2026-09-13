<?php

namespace Dth\Marketing\Integrations\Email;

use Dth\Marketing\Contracts\EmailMarketingBridge;

final class NullEmailMarketingBridge implements EmailMarketingBridge
{
    public function available(): bool
    {
        return false;
    }

    public function capabilities(): array
    {
        return [];
    }

    public function campaignsForMarketingCampaign(string $marketingCampaignReference): array
    {
        return [];
    }

    public function campaignAdminUrl(string $emailCampaignReference): ?string
    {
        return null;
    }
}
