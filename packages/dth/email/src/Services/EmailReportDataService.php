<?php

namespace Dth\Email\Services;

use Carbon\CarbonImmutable;
use Dth\Email\DTO\AnalyticsRange;
use Dth\Email\DTO\EmailAnalyticsFilters;
use Dth\Email\DTO\EmailEngagementFunnel;
use Dth\Email\Enums\AnalyticsGranularity;
use Dth\Email\Models\EmailCampaign;

class EmailReportDataService
{
    public function __construct(
        private readonly EmailAnalyticsService $analytics,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboard(EmailAnalyticsFilters $filters): array
    {
        $granularity = $filters->range->days() <= 2
            ? AnalyticsGranularity::Hour
            : AnalyticsGranularity::Day;

        return [
            'filters' => $filters,
            'overview' => $this->analytics->overview($filters),
            'trend' => $this->analytics->trend($filters, $granularity),
            'granularity' => $granularity,
            'funnel' => $this->analytics->engagementFunnel($filters),
            'topCampaigns' => $this->analytics->topCampaigns($filters, 10),
            'topLinks' => $this->analytics->topLinks($filters, 10),
            'sendingAccounts' => $this->analytics->sendingAccountPerformance($filters),
            'health' => $this->analytics->systemHealth(),
            'generatedAt' => CarbonImmutable::now(),
        ];
    }

    /**
     * Campaign report metrics are all-time. The activity chart is constrained to
     * the analytics maximum range so a very old campaign cannot create an
     * unbounded time-series query.
     *
     * @return array<string, mixed>
     */
    public function campaign(EmailCampaign $campaign): array
    {
        $campaign->loadMissing(['sendingAccount', 'template']);

        $metrics = $this->analytics->campaignPerformance($campaign);
        $trendContext = $this->campaignTrend($campaign);

        return [
            'campaign' => $campaign,
            'metrics' => $metrics,
            ...$trendContext,
            'funnel' => new EmailEngagementFunnel(
                recipients: $metrics->total,
                sent: $metrics->sent,
                opened: $metrics->opened,
                clicked: $metrics->clicked,
                unsubscribed: $metrics->unsubscribed,
            ),
            'topLinks' => $this->analytics->campaignTopLinks($campaign, 10),
            'generatedAt' => CarbonImmutable::now(),
        ];
    }

    /**
     * @return array{trend: array, trendRange: AnalyticsRange, trendGranularity: AnalyticsGranularity, trendTruncated: bool}
     */
    public function campaignTrend(EmailCampaign $campaign): array
    {
        $maxDays = max(1, (int) config('dth-email.analytics.max_range_days', 366));
        $end = CarbonImmutable::now()->endOfDay();
        $naturalStart = CarbonImmutable::parse(
            $campaign->started_at
                ?? $campaign->created_at
                ?? $end,
        )->startOfDay();

        $minimumStart = $end->subDays($maxDays - 1)->startOfDay();
        $trendTruncated = $naturalStart->lt($minimumStart);
        $trendStart = $trendTruncated ? $minimumStart : $naturalStart;

        if ($trendStart->gt($end)) {
            $trendStart = $end->startOfDay();
        }

        $range = AnalyticsRange::custom(
            start: $trendStart,
            end: $end,
            withComparison: false,
        );
        $filters = new EmailAnalyticsFilters(
            range: $range,
            campaignId: (int) $campaign->getKey(),
            comparePrevious: false,
        );
        $granularity = $range->days() <= 2
            ? AnalyticsGranularity::Hour
            : AnalyticsGranularity::Day;

        return [
            'trend' => $this->analytics->trend($filters, $granularity),
            'trendRange' => $range,
            'trendGranularity' => $granularity,
            'trendTruncated' => $trendTruncated,
        ];
    }
}
