<?php

namespace Tests\Unit\Marketing;

use App\Models\Marketing\Campaign;
use App\Models\Marketing\LandingPage;
use App\Services\Marketing\CampaignLandingPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignLandingPageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_link_contains_email_utm_and_campaign_context(): void
    {
        $landingPage = LandingPage::query()->create([
            'name' => 'LP Email',
            'slug' => 'lp-email',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $campaign = Campaign::query()->create([
            'name' => 'Summer Sale 2026',
            'subject' => 'Subject',
            'audience_type' => 'all',
            'status' => 'draft',
            'landing_page_id' => $landingPage->id,
        ]);

        $service = app(CampaignLandingPageService::class);
        $url = $service->getCampaignLink($campaign);

        $this->assertStringContainsString('/lp/lp-email', $url);
        $this->assertStringContainsString('cid=' . $campaign->id, $url);
        $this->assertStringContainsString('utm_source=email', $url);
        $this->assertStringContainsString('utm_medium=email', $url);
        $this->assertStringContainsString('utm_campaign=Summer%20Sale%202026', $url);
    }
}
