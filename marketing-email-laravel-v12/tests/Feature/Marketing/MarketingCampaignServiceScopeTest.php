<?php

namespace Tests\Feature\Marketing;

use App\Models\Marketing\LandingPage;
use App\Models\Marketing\MarketingCampaign;
use App\Models\Sales\Service;
use App\Services\Marketing\MarketingCampaignServiceScopeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MarketingCampaignServiceScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_can_advertise_multiple_services_and_landing_page_must_stay_in_scope(): void
    {
        $hosting = $this->makeService('HOSTING', 'Web Hosting');
        $vps = $this->makeService('VPS', 'VPS');
        $email = $this->makeService('EMAIL', 'Email doanh nghiệp');

        $campaign = MarketingCampaign::query()->create([
            'name' => 'Hạ tầng Web 2026',
            'slug' => 'ha-tang-web-2026',
            'status' => 'active',
        ]);
        $campaign->services()->sync([$hosting->id, $vps->id]);

        $scope = app(MarketingCampaignServiceScopeService::class);

        $options = $scope->serviceOptionsForCampaign($campaign->id);

        $this->assertArrayHasKey($hosting->id, $options);
        $this->assertArrayHasKey($vps->id, $options);
        $this->assertArrayNotHasKey($email->id, $options);

        $scope->assertLandingPageServiceAllowed($campaign->id, $hosting->id);
        $scope->assertLandingPageServiceAllowed($campaign->id, $vps->id);

        $this->expectException(ValidationException::class);
        $scope->assertLandingPageServiceAllowed($campaign->id, $email->id);
    }

    public function test_campaign_configuration_rejects_landing_page_outside_advertised_services(): void
    {
        $hosting = $this->makeService('HOSTING', 'Web Hosting');
        $vps = $this->makeService('VPS', 'VPS');

        $campaign = MarketingCampaign::query()->create([
            'name' => 'Hosting Campaign',
            'slug' => 'hosting-campaign',
            'status' => 'draft',
        ]);

        $vpsPage = LandingPage::query()->create([
            'name' => 'Giới thiệu VPS',
            'slug' => 'gioi-thieu-vps',
            'status' => 'draft',
            'service_id' => $vps->id,
        ]);

        $scope = app(MarketingCampaignServiceScopeService::class);

        try {
            $scope->assertCampaignConfiguration(
                [$hosting->id],
                [$vpsPage->id],
                $campaign->id,
            );
            $this->fail('Expected validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('landing_page_ids', $exception->errors());
        }
    }

    public function test_campaign_configuration_requires_at_least_one_active_service(): void
    {
        $scope = app(MarketingCampaignServiceScopeService::class);

        try {
            $scope->assertCampaignConfiguration([]);
            $this->fail('Expected validation exception was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('service_ids', $exception->errors());
        }
    }

    public function test_compatible_landing_page_options_only_return_pages_in_selected_service_scope_and_not_owned_elsewhere(): void
    {
        $hosting = $this->makeService('HOSTING', 'Web Hosting');
        $vps = $this->makeService('VPS', 'VPS');

        $campaignA = MarketingCampaign::query()->create([
            'name' => 'Campaign A',
            'slug' => 'campaign-a',
            'status' => 'draft',
        ]);
        $campaignB = MarketingCampaign::query()->create([
            'name' => 'Campaign B',
            'slug' => 'campaign-b',
            'status' => 'draft',
        ]);

        $hostingFree = LandingPage::query()->create([
            'name' => 'Hosting chưa gắn Campaign',
            'slug' => 'hosting-free',
            'status' => 'draft',
            'service_id' => $hosting->id,
        ]);
        $hostingA = LandingPage::query()->create([
            'name' => 'Hosting của Campaign A',
            'slug' => 'hosting-a',
            'status' => 'draft',
            'service_id' => $hosting->id,
            'marketing_campaign_id' => $campaignA->id,
        ]);
        $vpsA = LandingPage::query()->create([
            'name' => 'VPS của Campaign A',
            'slug' => 'vps-a',
            'status' => 'draft',
            'service_id' => $vps->id,
            'marketing_campaign_id' => $campaignA->id,
        ]);
        $hostingB = LandingPage::query()->create([
            'name' => 'Hosting của Campaign B',
            'slug' => 'hosting-b',
            'status' => 'draft',
            'service_id' => $hosting->id,
            'marketing_campaign_id' => $campaignB->id,
        ]);
        $vpsFree = LandingPage::query()->create([
            'name' => 'VPS chưa gắn Campaign',
            'slug' => 'vps-free',
            'status' => 'draft',
            'service_id' => $vps->id,
        ]);

        $options = app(MarketingCampaignServiceScopeService::class)
            ->compatibleLandingPageOptions([$hosting->id], $campaignA->id);

        $this->assertArrayHasKey($hostingFree->id, $options);
        $this->assertArrayHasKey($hostingA->id, $options);
        $this->assertArrayHasKey($vpsA->id, $options);
        $this->assertArrayNotHasKey($hostingB->id, $options);
        $this->assertArrayNotHasKey($vpsFree->id, $options);
    }

    private function makeService(string $code, string $name): Service
    {
        return Service::query()->create([
            'service_code' => $code,
            'name' => $name,
            'slug' => Str::slug($code).'-'.uniqid(),
            'status' => 'active',
            'sort_order' => 0,
        ]);
    }
}
