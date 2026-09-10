<?php

namespace Dth\Email\Tests\Unit;

use Carbon\CarbonImmutable;
use Dth\Email\Services\EmailDashboardFilterResolver;
use Dth\Email\Tests\TestCase;
use Illuminate\Http\Request;

class EmailDashboardFilterResolverTest extends TestCase
{
    public function test_missing_filter_state_uses_default_range(): void
    {
        CarbonImmutable::setTestNow('2026-09-10 10:00:00');
        config()->set('dth-email.analytics.default_range_days', 30);

        $filters = app(EmailDashboardFilterResolver::class)->resolve(null);

        $this->assertSame('2026-08-12', $filters->range->start->format('Y-m-d'));
        $this->assertSame('2026-09-10', $filters->range->end->format('Y-m-d'));
        $this->assertTrue($filters->comparePrevious);

        CarbonImmutable::setTestNow();
    }

    public function test_valid_dashboard_state_is_normalized(): void
    {
        $filters = app(EmailDashboardFilterResolver::class)->resolve([
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-10',
            'sending_account_id' => '3',
            'campaign_status' => 'completed',
            'message_status' => 'sent',
            'compare_previous' => false,
        ]);

        $this->assertSame('2026-09-01', $filters->range->start->format('Y-m-d'));
        $this->assertSame('2026-09-10', $filters->range->end->format('Y-m-d'));
        $this->assertSame(3, $filters->sendingAccountId);
        $this->assertSame('completed', $filters->campaignStatusValue());
        $this->assertSame('sent', $filters->messageStatusValue());
        $this->assertFalse($filters->comparePrevious);
        $this->assertNull($filters->range->previous());
    }

    public function test_invalid_stale_filter_values_fail_safe(): void
    {
        CarbonImmutable::setTestNow('2026-09-10 10:00:00');

        $filters = app(EmailDashboardFilterResolver::class)->resolve([
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-01',
            'sending_account_id' => '-4',
            'campaign_status' => 'unknown-status',
        ]);

        $this->assertNull($filters->sendingAccountId);
        $this->assertNull($filters->campaignStatusValue());
        $this->assertSame(30, $filters->range->days());

        CarbonImmutable::setTestNow();
    }
    public function test_request_query_is_resolved_without_livewire_filter_state(): void
    {
        $request = Request::create('/admin/email-dashboard', 'GET', [
            'start_date' => '2026-09-05',
            'end_date' => '2026-09-10',
            'sending_account_id' => '2',
            'campaign_status' => 'completed',
            'compare_previous' => '0',
        ]);

        $filters = app(EmailDashboardFilterResolver::class)->resolveRequest($request);

        $this->assertSame('2026-09-05', $filters->range->start->format('Y-m-d'));
        $this->assertSame('2026-09-10', $filters->range->end->format('Y-m-d'));
        $this->assertSame(2, $filters->sendingAccountId);
        $this->assertSame('completed', $filters->campaignStatusValue());
        $this->assertFalse($filters->comparePrevious);
    }

}
