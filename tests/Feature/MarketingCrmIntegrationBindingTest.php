<?php

namespace Tests\Feature;

use Dth\Crm\Integrations\Marketing\DthMarketingAudienceProvider;
use Dth\Crm\Integrations\Marketing\DthMarketingLeadProvider;
use Dth\Marketing\Contracts\AudienceProvider;
use Dth\Marketing\Contracts\LeadProvider;
use Tests\TestCase;

class MarketingCrmIntegrationBindingTest extends TestCase
{
    public function test_marketing_uses_crm_adapters_when_both_modules_are_installed(): void
    {
        $this->assertInstanceOf(DthMarketingAudienceProvider::class, app(AudienceProvider::class));
        $this->assertInstanceOf(DthMarketingLeadProvider::class, app(LeadProvider::class));
    }
}
