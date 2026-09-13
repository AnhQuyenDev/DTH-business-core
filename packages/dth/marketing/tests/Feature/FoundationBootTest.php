<?php

namespace Dth\Marketing\Tests\Feature;

use Dth\Marketing\Contracts\AudienceProvider;
use Dth\Marketing\Contracts\CatalogProvider;
use Dth\Marketing\Contracts\EmailMarketingBridge;
use Dth\Marketing\Contracts\LeadProvider;
use Dth\Marketing\Contracts\RevenueProvider;
use Dth\Marketing\Integrations\Crm\NullAudienceProvider;
use Dth\Marketing\Integrations\Sales\NullCatalogProvider;
use Dth\Marketing\Integrations\Email\NullEmailMarketingBridge;
use Dth\Marketing\Integrations\Crm\NullLeadProvider;
use Dth\Marketing\Integrations\Finance\NullRevenueProvider;
use Dth\Marketing\Support\IntegrationHealthService;
use Dth\Marketing\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class FoundationBootTest extends TestCase
{
    public function test_package_boots_with_null_integrations_and_foundation_schema(): void
    {
        $this->assertTrue((bool) config('dth-marketing.enabled'));
        $this->assertTrue(Schema::hasTable('marketing_campaigns'));

        $this->assertInstanceOf(NullAudienceProvider::class, app(AudienceProvider::class));
        $this->assertInstanceOf(NullLeadProvider::class, app(LeadProvider::class));
        $this->assertInstanceOf(NullCatalogProvider::class, app(CatalogProvider::class));
        $this->assertInstanceOf(NullRevenueProvider::class, app(RevenueProvider::class));
        $this->assertInstanceOf(NullEmailMarketingBridge::class, app(EmailMarketingBridge::class));

        $health = app(IntegrationHealthService::class)->snapshot();

        foreach ($health as $integration) {
            $this->assertFalse($integration['available']);
            $this->assertSame([], $integration['capabilities']);
        }
    }
}
