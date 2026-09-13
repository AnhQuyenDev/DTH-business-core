<?php

namespace Dth\Marketing\Tests\Feature;

use Dth\Marketing\Contracts\RevenueProvider;
use Dth\Marketing\DTO\MarketingAnalyticsFilter;
use Dth\Marketing\DTO\RevenueSummary;
use Dth\Marketing\Services\MarketingAnalyticsService;
use Dth\Marketing\Services\MarketingReportExportService;
use Dth\Marketing\Tests\Fakes\FakeRevenueProvider;
use Dth\Marketing\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MarketingAnalyticsHardeningTest extends TestCase
{
    public function test_dashboard_metrics_and_attribution_are_deterministic(): void
    {
        [$campaignId, $pageId] = $this->seedAnalyticsData();

        $filter = MarketingAnalyticsFilter::fromArray(['period' => '7d']);
        $data = app(MarketingAnalyticsService::class)->dashboard($filter);

        $this->assertSame(3, $data['summary']['views']);
        $this->assertSame(2, $data['summary']['submissions']);
        $this->assertSame(66.67, $data['summary']['view_to_submission']);
        $this->assertFalse($data['summary']['financial_available']);
        $this->assertNull($data['summary']['revenue']);

        $google = collect($data['sources'])->firstWhere('source', 'google');
        $this->assertSame(2, $google['views']);
        $this->assertSame(1, $google['submissions']);

        $cpc = collect($data['utm_mediums'])->firstWhere('medium', 'cpc');
        $this->assertSame(2, $cpc['views']);
        $this->assertSame(1, $cpc['submissions']);

        $launch = collect($data['utm_campaigns'])->firstWhere('campaign', 'launch-2026');
        $this->assertSame(2, $launch['views']);
        $this->assertSame(1, $launch['submissions']);

        $campaign = collect($data['campaigns'])->firstWhere('id', $campaignId);
        $this->assertSame(3, $campaign['views']);
        $this->assertSame(2, $campaign['submissions']);
        $this->assertSame($pageId, collect($data['landing_pages'])->first()['id']);
    }

    public function test_financial_metrics_are_only_exposed_when_revenue_provider_is_authoritative(): void
    {
        [$campaignId] = $this->seedAnalyticsData();

        $this->app->instance(RevenueProvider::class, new FakeRevenueProvider([
            (string) $campaignId => new RevenueSummary('2500000', 'VND', 2),
        ]));
        $this->app->forgetInstance(MarketingAnalyticsService::class);

        $data = app(MarketingAnalyticsService::class)->dashboard(
            MarketingAnalyticsFilter::fromArray(['period' => '7d']),
        );

        $this->assertTrue($data['summary']['financial_available']);
        $this->assertSame(2500000.0, $data['summary']['revenue']);
        $this->assertSame(2, $data['summary']['customers']);
        $this->assertSame(2.5, $data['summary']['roas']);
    }

    public function test_reports_generate_csv_xlsx_and_pdf_without_extra_package_dependencies(): void
    {
        $this->seedAnalyticsData();
        $data = app(MarketingAnalyticsService::class)->dashboard(
            MarketingAnalyticsFilter::fromArray(['period' => '7d']),
        );
        $exports = app(MarketingReportExportService::class);

        $csv = $exports->dashboard('csv', $data);
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv['content']);
        $this->assertStringContainsString('UTM Medium', $csv['content']);

        $xlsx = $exports->dashboard('xlsx', $data);
        $this->assertStringStartsWith('PK', $xlsx['content']);
        $this->assertGreaterThan(1000, strlen($xlsx['content']));

        $pdf = $exports->dashboard('pdf', $data);
        $this->assertStringStartsWith('%PDF-1.4', $pdf['content']);
        $this->assertStringContainsString('%%EOF', $pdf['content']);
    }

    public function test_hardening_schema_and_audit_table_exist(): void
    {
        $this->assertTrue(Schema::hasTable('marketing_audit_logs'));
        $this->assertTrue(Schema::hasColumn('marketing_landing_page_views', 'source'));
        $this->assertTrue(Schema::hasColumns('marketing_audit_logs', [
            'action', 'auditable_type', 'auditable_id', 'old_values', 'new_values', 'metadata', 'ip_hash', 'created_at',
        ]));
    }

    /** @return array{int,int} */
    private function seedAnalyticsData(): array
    {
        $now = now();
        $campaignId = DB::table('marketing_campaigns')->insertGetId([
            'name' => 'Analytics campaign',
            'slug' => 'analytics-campaign',
            'status' => 'active',
            'budget' => 1000000,
            'currency' => 'VND',
            'start_date' => $now->copy()->subDay()->toDateString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $pageId = DB::table('marketing_landing_pages')->insertGetId([
            'marketing_campaign_id' => $campaignId,
            'name' => 'Analytics landing',
            'slug' => 'analytics-landing',
            'status' => 'published',
            'published_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('marketing_landing_page_views')->insert([
            $this->viewRow($pageId, $campaignId, 'google', 'cpc', 'launch-2026', $now),
            $this->viewRow($pageId, $campaignId, 'google', 'cpc', 'launch-2026', $now),
            $this->viewRow($pageId, $campaignId, null, null, null, $now),
        ]);

        DB::table('marketing_landing_page_submissions')->insert([
            $this->submissionRow($pageId, $campaignId, 'google', 'cpc', 'launch-2026', 'one', $now),
            $this->submissionRow($pageId, $campaignId, 'direct', null, null, 'two', $now),
        ]);

        return [$campaignId, $pageId];
    }

    /** @return array<string,mixed> */
    private function viewRow(int $pageId, int $campaignId, ?string $source, ?string $medium, ?string $utmCampaign, $now): array
    {
        return [
            'landing_page_id' => $pageId,
            'marketing_campaign_id' => $campaignId,
            'source' => $source ?: 'direct',
            'utm_source' => $source,
            'utm_medium' => $medium,
            'utm_campaign' => $utmCampaign,
            'viewed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /** @return array<string,mixed> */
    private function submissionRow(int $pageId, int $campaignId, ?string $source, ?string $medium, ?string $utmCampaign, string $fingerprint, $now): array
    {
        return [
            'landing_page_id' => $pageId,
            'marketing_campaign_id' => $campaignId,
            'payload_fingerprint' => hash('sha256', $fingerprint),
            'member_key' => hash('sha256', 'member-'.$fingerprint),
            'submission_type' => 'personal',
            'data' => json_encode(['name' => $fingerprint]),
            'status' => 'processed',
            'contact_action' => 'skipped',
            'source' => $source,
            'utm_source' => $source === 'direct' ? null : $source,
            'utm_medium' => $medium,
            'utm_campaign' => $utmCampaign,
            'submitted_at' => $now,
            'processed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
