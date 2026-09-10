<?php

namespace Dth\Email\Services;

use Dth\Email\DTO\EmailAnalyticsFilters;
use Dth\Email\DTO\EmailInsightItem;
use Dth\Email\DTO\EmailInsightReport;
use Dth\Email\Models\EmailCampaign;
use Dth\Email\Support\UiText;

final class EmailInsightService
{
    public function __construct(private readonly EmailAnalyticsService $analytics) {}

    public function dashboard(EmailAnalyticsFilters $filters): EmailInsightReport
    {
        $overview = $this->analytics->overview($filters);
        $current = $overview->current;
        $items = [];
        $score = 100;

        if ($current->sent === 0) {
            return new EmailInsightReport(
                UiText::get('insights.summary.no_data', 'Not enough sent email data to evaluate this period.'),
                [],
                0,
            );
        }

        $this->rateRisk($items, $score, 'failure_rate', $current->failureRate, 2.0, 5.0,
            UiText::get('insights.failure.warning_title', 'Failure rate needs attention'),
            UiText::get('insights.failure.critical_title', 'Failure rate is high'));

        $this->rateRisk($items, $score, 'unsubscribe_rate', $current->unsubscribeRate, 0.8, 2.0,
            UiText::get('insights.unsubscribe.warning_title', 'Unsubscribe rate is elevated'),
            UiText::get('insights.unsubscribe.critical_title', 'Unsubscribe rate is high'));

        $this->comparisonInsight($items, $score, $overview->deltas['open_rate'] ?? null, 'open_rate');
        $this->comparisonInsight($items, $score, $overview->deltas['click_rate'] ?? null, 'click_rate');

        if ($current->clickToOpenRate >= 25.0) {
            $items[] = new EmailInsightItem(
                'positive',
                UiText::get('insights.ctor.strong_title', 'Strong post-open engagement'),
                UiText::get('insights.ctor.strong_body', 'CTOR is :rate%, indicating that opened emails are converting into clicks effectively.', ['rate' => number_format($current->clickToOpenRate, 1)]),
                'click_to_open_rate',
            );
        } elseif ($current->uniqueOpened >= 10 && $current->clickToOpenRate < 10.0) {
            $items[] = new EmailInsightItem(
                'warning',
                UiText::get('insights.ctor.low_title', 'Open-to-click conversion is weak'),
                UiText::get('insights.ctor.low_body', 'CTOR is :rate%. Review CTA clarity, placement and offer relevance.', ['rate' => number_format($current->clickToOpenRate, 1)]),
                'click_to_open_rate',
            );
            $score -= 10;
        }

        $topLinks = $this->analytics->topLinks($filters, 3);
        if (($topLinks[0]->clickShare ?? 0) >= 70.0 && ($topLinks[0]->totalClicks ?? 0) >= 5) {
            $items[] = new EmailInsightItem(
                'neutral',
                UiText::get('insights.links.concentrated_title', 'Clicks are concentrated on one destination'),
                UiText::get('insights.links.concentrated_body', ':share% of tracked clicks go to the top link. This identifies the strongest CTA in the selected period.', ['share' => number_format($topLinks[0]->clickShare, 1)]),
                'top_links',
            );
        }

        $score = max(0, min(100, $score));

        return new EmailInsightReport(
            $this->summaryForScore($score),
            array_slice($items, 0, 6),
            $score,
        );
    }

    public function campaign(int|EmailCampaign $campaign): EmailInsightReport
    {
        $campaignModel = $campaign instanceof EmailCampaign
            ? $campaign
            : EmailCampaign::query()->findOrFail($campaign);
        $metrics = $this->analytics->campaignPerformance($campaignModel);
        $items = [];
        $score = 100;

        if ($metrics->sent === 0) {
            return new EmailInsightReport(
                UiText::get('insights.summary.no_data', 'Not enough sent email data to evaluate this period.'),
                [],
                0,
            );
        }

        $this->rateRisk($items, $score, 'failure_rate', $metrics->failureRate, 2.0, 5.0,
            UiText::get('insights.failure.warning_title', 'Failure rate needs attention'),
            UiText::get('insights.failure.critical_title', 'Failure rate is high'));
        $this->rateRisk($items, $score, 'unsubscribe_rate', $metrics->unsubscribeRate, 0.8, 2.0,
            UiText::get('insights.unsubscribe.warning_title', 'Unsubscribe rate is elevated'),
            UiText::get('insights.unsubscribe.critical_title', 'Unsubscribe rate is high'));

        if ($metrics->openRate >= 35.0) {
            $items[] = new EmailInsightItem(
                'positive',
                UiText::get('insights.open.strong_title', 'Healthy open engagement'),
                UiText::get('insights.open.strong_body', 'Unique open rate is :rate%. Subject and sender recognition are performing well for this campaign.', ['rate' => number_format($metrics->openRate, 1)]),
                'open_rate',
            );
        } elseif ($metrics->openRate < 15.0 && $metrics->sent >= 10) {
            $items[] = new EmailInsightItem(
                'warning',
                UiText::get('insights.open.low_title', 'Open engagement is low'),
                UiText::get('insights.open.low_body', 'Unique open rate is :rate%. Review subject line, sender identity and audience relevance.', ['rate' => number_format($metrics->openRate, 1)]),
                'open_rate',
            );
            $score -= 10;
        }

        if ($metrics->clickToOpenRate >= 25.0) {
            $items[] = new EmailInsightItem(
                'positive',
                UiText::get('insights.ctor.strong_title', 'Strong post-open engagement'),
                UiText::get('insights.ctor.strong_body', 'CTOR is :rate%, indicating that opened emails are converting into clicks effectively.', ['rate' => number_format($metrics->clickToOpenRate, 1)]),
                'click_to_open_rate',
            );
        } elseif ($metrics->opened >= 10 && $metrics->clickToOpenRate < 10.0) {
            $items[] = new EmailInsightItem(
                'warning',
                UiText::get('insights.ctor.low_title', 'Open-to-click conversion is weak'),
                UiText::get('insights.ctor.low_body', 'CTOR is :rate%. Review CTA clarity, placement and offer relevance.', ['rate' => number_format($metrics->clickToOpenRate, 1)]),
                'click_to_open_rate',
            );
            $score -= 10;
        }

        $links = $this->analytics->campaignTopLinks($campaignModel, 3);
        if (($links[0]->clickShare ?? 0) >= 70.0 && ($links[0]->totalClicks ?? 0) >= 3) {
            $items[] = new EmailInsightItem(
                'neutral',
                UiText::get('insights.links.concentrated_title', 'Clicks are concentrated on one destination'),
                UiText::get('insights.links.concentrated_body', ':share% of tracked clicks go to the top link. This identifies the strongest CTA in the selected period.', ['share' => number_format($links[0]->clickShare, 1)]),
                'top_links',
            );
        }

        $score = max(0, min(100, $score));

        return new EmailInsightReport($this->summaryForScore($score), array_slice($items, 0, 6), $score);
    }

    private function rateRisk(array &$items, int &$score, string $metric, float $rate, float $warning, float $critical, string $warningTitle, string $criticalTitle): void
    {
        if ($rate >= $critical) {
            $items[] = new EmailInsightItem(
                'critical',
                $criticalTitle,
                UiText::get('insights.rate.critical_body', ':metric is :rate%, above the critical threshold of :threshold%.', [
                    'metric' => UiText::get("dashboard.metrics.{$metric}", str($metric)->replace('_', ' ')->title()->toString()),
                    'rate' => number_format($rate, 1),
                    'threshold' => number_format($critical, 1),
                ]),
                $metric,
            );
            $score -= 25;
        } elseif ($rate >= $warning) {
            $items[] = new EmailInsightItem(
                'warning',
                $warningTitle,
                UiText::get('insights.rate.warning_body', ':metric is :rate%, above the review threshold of :threshold%.', [
                    'metric' => UiText::get("dashboard.metrics.{$metric}", str($metric)->replace('_', ' ')->title()->toString()),
                    'rate' => number_format($rate, 1),
                    'threshold' => number_format($warning, 1),
                ]),
                $metric,
            );
            $score -= 12;
        }
    }

    private function comparisonInsight(array &$items, int &$score, mixed $delta, string $metric): void
    {
        if (! $delta || $delta->absoluteChange === null || abs($delta->absoluteChange) < 2.0) {
            return;
        }

        $label = UiText::get("dashboard.metrics.{$metric}", str($metric)->replace('_', ' ')->title()->toString());
        $positive = $delta->absoluteChange > 0;

        if (! $positive) {
            $score -= 8;
        }

        $items[] = new EmailInsightItem(
            $positive ? 'positive' : 'warning',
            $positive
                ? UiText::get('insights.comparison.improved_title', ':metric improved', ['metric' => $label])
                : UiText::get('insights.comparison.declined_title', ':metric declined', ['metric' => $label]),
            UiText::get('insights.comparison.body', ':metric changed by :change percentage points compared with the previous period.', [
                'metric' => $label,
                'change' => ($delta->absoluteChange > 0 ? '+' : '').number_format($delta->absoluteChange, 1),
            ]),
            $metric,
        );
    }

    private function summaryForScore(int $score): string
    {
        return match (true) {
            $score >= 85 => UiText::get('insights.summary.strong', 'Email performance is healthy with no major statistical warning.'),
            $score >= 65 => UiText::get('insights.summary.watch', 'Performance is generally stable, with several metrics worth monitoring.'),
            default => UiText::get('insights.summary.risk', 'Several email metrics require operational or campaign-level review.'),
        };
    }
}
