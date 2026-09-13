<?php

namespace Dth\Marketing\Tests\Feature;

use Dth\Marketing\Models\LandingPage;
use Dth\Marketing\Models\MarketingCampaign;
use Dth\Marketing\Models\Segment;
use Dth\Marketing\Services\EmailMarketingLinkService;
use Dth\Marketing\Services\LandingPageUtmService;
use Dth\Marketing\Services\SegmentQueryService;
use Dth\Marketing\Tests\TestCase;
use Illuminate\Support\Facades\DB;

class AudienceSegmentUtmTest extends TestCase
{
    public function test_segment_rules_preview_processed_submission_population(): void
    {
        [$campaignId, $pageId] = $this->seedCampaignAndLandingPage();
        $now = now();

        DB::table('marketing_landing_page_submissions')->insert([
            [
                'landing_page_id' => $pageId,
                'marketing_campaign_id' => $campaignId,
                'payload_fingerprint' => hash('sha256', 'one'),
                'member_key' => hash('sha256', 'member-one'),
                'submission_type' => 'personal',
                'data' => json_encode(['name' => 'A']),
                'normalized_email' => 'a@example.com',
                'status' => 'processed',
                'contact_action' => 'skipped',
                'source' => 'google',
                'utm_source' => 'google',
                'submitted_at' => $now,
                'processed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'landing_page_id' => $pageId,
                'marketing_campaign_id' => $campaignId,
                'payload_fingerprint' => hash('sha256', 'two'),
                'member_key' => hash('sha256', 'member-two'),
                'submission_type' => 'business',
                'data' => json_encode(['name' => 'B']),
                'normalized_email' => 'b@example.com',
                'status' => 'processed',
                'contact_action' => 'skipped',
                'source' => 'facebook',
                'utm_source' => 'facebook',
                'submitted_at' => $now,
                'processed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $segment = Segment::query()->create([
            'name' => 'Google leads',
            'slug' => 'google-leads',
            'status' => 'active',
            'rules' => [
                'conditions' => [[
                    'field' => 'utm_source',
                    'operator' => 'equals',
                    'value' => 'google',
                ]],
            ],
        ]);

        $service = app(SegmentQueryService::class);

        $this->assertSame(1, $service->countForSegment($segment));
        $this->assertSame('a@example.com', $service->sampleForSegment($segment, 5)[0]['email']);
    }

    public function test_email_campaign_reference_can_be_linked_without_email_module_write_dependency(): void
    {
        [$campaignId] = $this->seedCampaignAndLandingPage();
        $campaign = MarketingCampaign::query()->findOrFail($campaignId);

        $link = app(EmailMarketingLinkService::class)->createManualLink($campaign, [
            'email_campaign_reference' => 'email-campaign-42',
            'display_name' => 'Launch Email',
            'status_snapshot' => 'draft',
            'admin_url_snapshot' => 'https://example.test/admin/email-campaigns/42',
        ]);

        $this->assertSame('email-campaign-42', $link->email_campaign_reference);
        $this->assertDatabaseHas('marketing_campaign_email_links', [
            'marketing_campaign_id' => $campaignId,
            'email_campaign_reference' => 'email-campaign-42',
            'display_name' => 'Launch Email',
        ]);
    }

    public function test_utm_generator_persists_campaign_link(): void
    {
        [$campaignId, $pageId] = $this->seedCampaignAndLandingPage();
        $page = LandingPage::query()->findOrFail($pageId);

        $utm = app(LandingPageUtmService::class)->create($page, [
            'name' => 'Google launch',
            'source' => 'google',
            'medium' => 'cpc',
            'campaign' => 'launch-2026',
            'content' => 'hero',
        ]);

        $this->assertSame($campaignId, $utm->marketing_campaign_id);
        $this->assertStringContainsString('utm_source=google', $utm->url);
        $this->assertStringContainsString('utm_medium=cpc', $utm->url);
        $this->assertStringContainsString('utm_campaign=launch-2026', $utm->url);
        $this->assertStringContainsString('utm_content=hero', $utm->url);

        $this->assertDatabaseHas('marketing_landing_page_utm_urls', [
            'landing_page_id' => $pageId,
            'marketing_campaign_id' => $campaignId,
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
        ]);
    }

    /** @return array{int,int} */
    private function seedCampaignAndLandingPage(): array
    {
        $now = now();

        $campaignId = DB::table('marketing_campaigns')->insertGetId([
            'name' => 'Launch 2026',
            'slug' => 'launch-2026',
            'status' => 'active',
            'currency' => 'VND',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $pageId = DB::table('marketing_landing_pages')->insertGetId([
            'marketing_campaign_id' => $campaignId,
            'name' => 'Launch landing',
            'slug' => 'launch-landing',
            'status' => 'published',
            'published_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [$campaignId, $pageId];
    }
}
