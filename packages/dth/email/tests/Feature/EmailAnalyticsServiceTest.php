<?php

namespace Dth\Email\Tests\Feature;

use Dth\Email\DTO\AnalyticsRange;
use Dth\Email\DTO\EmailAnalyticsFilters;
use Dth\Email\Enums\AnalyticsGranularity;
use Dth\Email\Services\EmailAnalyticsService;
use Dth\Email\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmailAnalyticsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('dth-email.analytics.cache_ttl_seconds', 0);
        $this->seedAnalyticsFixture();
    }

    public function test_overview_uses_coherent_send_cohort_and_comparison_period(): void
    {
        $filters = new EmailAnalyticsFilters(
            AnalyticsRange::custom('2026-09-10', '2026-09-10')
        );

        $overview = app(EmailAnalyticsService::class)->overview($filters);
        $current = $overview->current;

        $this->assertSame(1, $current->campaigns);
        $this->assertSame(3, $current->recipients);
        $this->assertSame(3, $current->messages);
        $this->assertSame(2, $current->sent);
        $this->assertSame(2, $current->uniqueOpened);
        $this->assertSame(3, $current->totalOpens);
        $this->assertSame(1, $current->uniqueClicked);
        $this->assertSame(2, $current->totalClicks);
        $this->assertSame(1, $current->failed);
        $this->assertSame(1, $current->unsubscribed);
        $this->assertSame(100.0, $current->openRate);
        $this->assertSame(50.0, $current->clickRate);
        $this->assertSame(50.0, $current->clickToOpenRate);
        $this->assertSame(33.3, $current->failureRate);
        $this->assertSame(50.0, $current->unsubscribeRate);

        // SMTP does not expose provider-confirmed delivery events, so 0 is not
        // incorrectly presented as a real 0% delivery rate.
        $this->assertNull($current->deliveryRate);
        $this->assertFalse($overview->capabilities->delivery);

        $this->assertNotNull($overview->previous);
        $this->assertSame(1, $overview->previous->sent);
        $this->assertSame(0.0, $overview->previous->openRate);
        $this->assertSame('up', $overview->deltas['open_rate']->direction);
    }

    public function test_trend_top_campaigns_links_and_account_performance_are_available_for_dashboard_layer(): void
    {
        $filters = new EmailAnalyticsFilters(
            AnalyticsRange::custom('2026-09-10', '2026-09-10'),
            comparePrevious: false,
        );

        $analytics = app(EmailAnalyticsService::class);

        $trend = $analytics->trend($filters, AnalyticsGranularity::Day);
        $this->assertCount(1, $trend);
        $this->assertSame(2, $trend[0]->sent);
        $this->assertSame(2, $trend[0]->uniqueOpened);
        $this->assertSame(3, $trend[0]->totalOpens);
        $this->assertSame(1, $trend[0]->uniqueClicked);
        $this->assertSame(2, $trend[0]->totalClicks);
        $this->assertSame(1, $trend[0]->unsubscribed);
        $this->assertSame(1, $trend[0]->failed);

        $funnel = $analytics->engagementFunnel($filters);
        $this->assertSame(3, $funnel->recipients);
        $this->assertSame(2, $funnel->sent);
        $this->assertSame(2, $funnel->opened);
        $this->assertSame(1, $funnel->clicked);

        $topCampaigns = $analytics->topCampaigns($filters);
        $this->assertCount(1, $topCampaigns);
        $this->assertSame('Current campaign', $topCampaigns[0]->name);
        $this->assertSame(50.0, $topCampaigns[0]->clickRate);

        $topLinks = $analytics->topLinks($filters);
        $this->assertCount(1, $topLinks);
        $this->assertSame('https://example.com/pricing', $topLinks[0]->url);
        $this->assertSame(1, $topLinks[0]->uniqueClickers);
        $this->assertSame(2, $topLinks[0]->totalClicks);
        $this->assertSame(100.0, $topLinks[0]->clickShare);

        $accounts = $analytics->sendingAccountPerformance($filters);
        $this->assertCount(1, $accounts);
        $this->assertSame('SMTP primary', $accounts[0]->name);
        $this->assertSame(2, $accounts[0]->sent);
        $this->assertFalse($accounts[0]->capabilities->delivery);
    }

    public function test_campaign_performance_preserves_historical_unsubscribe_and_marks_delivery_unavailable(): void
    {
        $analytics = app(EmailAnalyticsService::class);
        $result = $analytics->campaignPerformance(2);

        $this->assertSame(3, $result->total);
        $this->assertSame(2, $result->sent);
        $this->assertSame(2, $result->opened);
        $this->assertSame(3, $result->totalOpens);
        $this->assertSame(1, $result->clicked);
        $this->assertSame(2, $result->totalClicks);
        $this->assertSame(1, $result->unsubscribed);
        $this->assertSame(50.0, $result->unsubscribeRate);
        $this->assertNull($result->deliveryRate);
        $this->assertFalse($result->capabilities->delivery);
    }

    private function seedAnalyticsFixture(): void
    {
        DB::table('email_sending_accounts')->insert([
            'id' => 1,
            'name' => 'SMTP primary',
            'provider' => 'smtp',
            'from_email' => 'sender@example.com',
            'encrypted_config' => 'encrypted-placeholder',
            'status' => 'active',
            'created_at' => '2026-09-01 00:00:00',
            'updated_at' => '2026-09-01 00:00:00',
        ]);

        DB::table('email_templates')->insert([
            'id' => 1,
            'template_key' => 'analytics.test',
            'name' => 'Analytics test',
            'subject' => 'Test',
            'html_body' => '<p>Test</p>',
            'status' => 'active',
            'created_at' => '2026-09-01 00:00:00',
            'updated_at' => '2026-09-01 00:00:00',
        ]);

        DB::table('email_campaigns')->insert([
            [
                'id' => 1,
                'name' => 'Previous campaign',
                'subject' => 'Previous',
                'email_template_id' => 1,
                'sending_account_id' => 1,
                'status' => 'completed',
                'started_at' => '2026-09-09 09:00:00',
                'completed_at' => '2026-09-09 09:05:00',
                'html_body' => '<p>Previous</p>',
                'created_at' => '2026-09-09 08:00:00',
                'updated_at' => '2026-09-09 09:05:00',
            ],
            [
                'id' => 2,
                'name' => 'Current campaign',
                'subject' => 'Current',
                'email_template_id' => 1,
                'sending_account_id' => 1,
                'status' => 'completed',
                'started_at' => '2026-09-10 09:00:00',
                'completed_at' => '2026-09-10 09:30:00',
                'html_body' => '<p>Current</p>',
                'created_at' => '2026-09-10 08:00:00',
                'updated_at' => '2026-09-10 09:30:00',
            ],
        ]);

        DB::table('email_campaign_recipients')->insert([
            [
                'id' => 1,
                'campaign_id' => 1,
                'email' => 'previous@example.com',
                'status' => 'sent',
                'sent_at' => '2026-09-09 09:01:00',
                'created_at' => '2026-09-09 08:30:00',
                'updated_at' => '2026-09-09 09:01:00',
            ],
            [
                'id' => 2,
                'campaign_id' => 2,
                'email' => 'one@example.com',
                'status' => 'clicked',
                'sent_at' => '2026-09-10 09:01:00',
                'opened_at' => '2026-09-10 10:00:00',
                'clicked_at' => '2026-09-10 11:00:00',
                'created_at' => '2026-09-10 08:30:00',
                'updated_at' => '2026-09-10 11:00:00',
            ],
            [
                'id' => 3,
                'campaign_id' => 2,
                'email' => 'two@example.com',
                'status' => 'unsubscribed',
                'sent_at' => '2026-09-10 09:02:00',
                'opened_at' => '2026-09-10 10:30:00',
                'created_at' => '2026-09-10 08:31:00',
                'updated_at' => '2026-09-10 12:00:00',
            ],
            [
                'id' => 4,
                'campaign_id' => 2,
                'email' => 'failed@example.com',
                'status' => 'failed',
                'failed_at' => '2026-09-10 09:03:00',
                'created_at' => '2026-09-10 08:32:00',
                'updated_at' => '2026-09-10 09:03:00',
            ],
        ]);

        foreach ([
            [1, 1, 'sent', '2026-09-09 09:01:00', null],
            [2, 2, 'sent', '2026-09-10 09:01:00', null],
            [3, 3, 'sent', '2026-09-10 09:02:00', null],
            [4, 4, 'failed', null, '2026-09-10 09:03:00'],
        ] as [$id, $recipientId, $status, $sentAt, $failedAt]) {
            DB::table('email_messages')->insert([
                'id' => $id,
                'uuid' => (string) Str::uuid(),
                'sending_account_id' => 1,
                'template_id' => 1,
                'campaign_recipient_id' => $recipientId,
                'from_email' => 'sender@example.com',
                'recipient_email' => DB::table('email_campaign_recipients')->where('id', $recipientId)->value('email'),
                'subject' => 'Test',
                'status' => $status,
                'tracking_token' => (string) Str::uuid(),
                'unsubscribe_token' => (string) Str::uuid(),
                'queued_at' => $sentAt ?? $failedAt,
                'sent_at' => $sentAt,
                'failed_at' => $failedAt,
                'failure_reason' => $failedAt ? 'SMTP failed' : null,
                'created_at' => $sentAt ?? $failedAt,
                'updated_at' => $sentAt ?? $failedAt,
            ]);
        }

        $events = [
            [2, 'opened', '2026-09-10 10:00:00'],
            [2, 'opened', '2026-09-10 10:05:00'],
            [3, 'opened', '2026-09-10 10:30:00'],
            [2, 'clicked', '2026-09-10 11:00:00'],
            [2, 'clicked', '2026-09-10 11:05:00'],
            [3, 'unsubscribed', '2026-09-10 12:00:00'],
        ];

        foreach ($events as [$messageId, $type, $occurredAt]) {
            DB::table('email_events')->insert([
                'message_id' => $messageId,
                'event_type' => $type,
                'occurred_at' => $occurredAt,
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
            ]);
        }

        DB::table('email_tracked_links')->insert([
            'message_id' => 2,
            'original_url' => 'https://example.com/pricing',
            'tracking_token' => (string) Str::uuid(),
            'click_count' => 2,
            'last_clicked_at' => '2026-09-10 11:05:00',
            'created_at' => '2026-09-10 09:00:00',
            'updated_at' => '2026-09-10 11:05:00',
        ]);
    }
}
