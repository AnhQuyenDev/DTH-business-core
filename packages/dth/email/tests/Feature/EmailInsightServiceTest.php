<?php

namespace Dth\Email\Tests\Feature;

use Dth\Email\DTO\AnalyticsRange;
use Dth\Email\DTO\EmailAnalyticsFilters;
use Dth\Email\Services\EmailInsightService;
use Dth\Email\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EmailInsightServiceTest extends TestCase
{
    public function test_dashboard_insights_flag_high_failure_rate(): void
    {
        config()->set('dth-email.analytics.cache_ttl_seconds', 0);
        $this->seedCampaign();

        $report = app(EmailInsightService::class)->dashboard(new EmailAnalyticsFilters(
            AnalyticsRange::custom('2026-09-10', '2026-09-10'),
            comparePrevious: false,
        ));

        $this->assertLessThan(100, $report->score);
        $this->assertTrue(collect($report->items)->contains(fn ($item) => $item->metric === 'failure_rate'));
    }

    private function seedCampaign(): void
    {
        DB::table('email_sending_accounts')->insert([
            'id' => 1,
            'name' => 'SMTP',
            'provider' => 'smtp',
            'from_email' => 'sender@example.com',
            'encrypted_config' => 'encrypted-placeholder',
            'status' => 'active',
            'created_at' => '2026-09-10 08:00:00',
            'updated_at' => '2026-09-10 08:00:00',
        ]);

        DB::table('email_templates')->insert([
            'id' => 1,
            'template_key' => 'insight.test',
            'name' => 'Insight',
            'subject' => 'Test',
            'html_body' => '<p>Test</p>',
            'status' => 'active',
            'created_at' => '2026-09-10 08:00:00',
            'updated_at' => '2026-09-10 08:00:00',
        ]);

        DB::table('email_campaigns')->insert([
            'id' => 1,
            'name' => 'Insight campaign',
            'subject' => 'Test',
            'email_template_id' => 1,
            'sending_account_id' => 1,
            'status' => 'completed',
            'html_body' => '<p>Test</p>',
            'started_at' => '2026-09-10 09:00:00',
            'completed_at' => '2026-09-10 09:10:00',
            'created_at' => '2026-09-10 08:00:00',
            'updated_at' => '2026-09-10 09:10:00',
        ]);

        foreach (range(1, 10) as $id) {
            $failed = $id <= 2;
            DB::table('email_campaign_recipients')->insert([
                'id' => $id,
                'campaign_id' => 1,
                'email' => "user{$id}@example.com",
                'status' => $failed ? 'failed' : 'sent',
                'sent_at' => $failed ? null : '2026-09-10 09:01:00',
                'failed_at' => $failed ? '2026-09-10 09:01:00' : null,
                'created_at' => '2026-09-10 08:30:00',
                'updated_at' => '2026-09-10 09:01:00',
            ]);
            DB::table('email_messages')->insert([
                'id' => $id,
                'uuid' => (string) Str::uuid(),
                'sending_account_id' => 1,
                'template_id' => 1,
                'campaign_recipient_id' => $id,
                'from_email' => 'sender@example.com',
                'recipient_email' => "user{$id}@example.com",
                'subject' => 'Test',
                'status' => $failed ? 'failed' : 'sent',
                'tracking_token' => (string) Str::uuid(),
                'unsubscribe_token' => (string) Str::uuid(),
                'queued_at' => '2026-09-10 09:00:00',
                'sent_at' => $failed ? null : '2026-09-10 09:01:00',
                'failed_at' => $failed ? '2026-09-10 09:01:00' : null,
                'created_at' => '2026-09-10 09:00:00',
                'updated_at' => '2026-09-10 09:01:00',
            ]);
        }
    }
}
