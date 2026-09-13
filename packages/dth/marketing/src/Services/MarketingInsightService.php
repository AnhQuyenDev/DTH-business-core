<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Support\UiText;

final class MarketingInsightService
{
    /** @param array<string, mixed> $analytics @return array<int, array{level:string,title:string,body:string}> */
    public function forDashboard(array $analytics): array
    {
        $summary = (array) ($analytics['summary'] ?? []);
        $sources = (array) ($analytics['sources'] ?? []);
        $landingPages = (array) ($analytics['landing_pages'] ?? []);
        $campaigns = (array) ($analytics['campaigns'] ?? []);
        $insights = [];

        $views = (int) ($summary['views'] ?? 0);
        $submissions = (int) ($summary['submissions'] ?? 0);
        $viewToSubmission = (float) ($summary['view_to_submission'] ?? 0);
        $failureRate = (float) ($summary['failure_rate'] ?? 0);
        $spamRate = (float) ($summary['spam_rate'] ?? 0);

        if ($views >= 50 && $viewToSubmission < 2.0) {
            $insights[] = [
                'level' => 'warning',
                'title' => UiText::get('analytics.insight.low_conversion', 'Low landing conversion'),
                'body' => UiText::get('analytics.insight.low_conversion_body', 'View-to-submission conversion is :rate%. Review CTA clarity, form length and page-message alignment.', ['rate' => $viewToSubmission]),
            ];
        } elseif ($views > 0 && $submissions > 0) {
            $insights[] = [
                'level' => 'success',
                'title' => UiText::get('analytics.insight.converting', 'Acquisition is converting'),
                'body' => UiText::get('analytics.insight.converting_body', 'The selected period converted :rate% of landing views into submissions.', ['rate' => $viewToSubmission]),
            ];
        }

        if ($failureRate >= 5.0) {
            $insights[] = [
                'level' => 'danger',
                'title' => UiText::get('analytics.insight.processing_failures', 'Submission processing failures need attention'),
                'body' => UiText::get('analytics.insight.processing_failures_body', ':rate% of submissions ended in Failed status. Review integration health and failure reasons.', ['rate' => $failureRate]),
            ];
        }

        if ($spamRate >= 10.0) {
            $insights[] = [
                'level' => 'warning',
                'title' => UiText::get('analytics.insight.spam_pressure', 'Spam pressure is elevated'),
                'body' => UiText::get('analytics.insight.spam_pressure_body', ':rate% of submissions were classified as spam in the selected period.', ['rate' => $spamRate]),
            ];
        }

        if ($sources !== []) {
            $top = collect($sources)->sortByDesc('submissions')->first();
            if (is_array($top) && (int) ($top['submissions'] ?? 0) > 0) {
                $rate = $top['conversion_rate'] ?? null;
                $insights[] = [
                    'level' => 'info',
                    'title' => UiText::get('analytics.insight.top_source', 'Top acquisition source'),
                    'body' => UiText::get('analytics.insight.top_source_body', ':source produced :count submissions:conversion', [
                        'source' => (string) ($top['source'] ?? 'direct'),
                        'count' => (int) ($top['submissions'] ?? 0),
                        'conversion' => $rate !== null ? ' at '.(float) $rate.'% view-to-submission conversion.' : '.',
                    ]),
                ];
            }
        }

        if ($landingPages !== []) {
            $best = collect($landingPages)
                ->filter(fn (array $row): bool => (int) ($row['views'] ?? 0) >= 20)
                ->sortByDesc('conversion_rate')
                ->first();
            if (is_array($best)) {
                $insights[] = [
                    'level' => 'info',
                    'title' => UiText::get('analytics.insight.best_landing', 'Best converting Landing Page'),
                    'body' => UiText::get('analytics.insight.best_landing_body', ':name converts :rate% of recorded views into submissions.', ['name' => (string) $best['name'], 'rate' => (float) $best['conversion_rate']]),
                ];
            }
        }

        if (($summary['financial_available'] ?? false) === true) {
            $roas = $summary['roas'] ?? null;
            if ($roas !== null && (float) $roas < 1.0 && (float) ($summary['budget'] ?? 0) > 0) {
                $insights[] = [
                    'level' => 'warning',
                    'title' => UiText::get('analytics.insight.roas_below_break_even', 'ROAS is below break-even'),
                    'body' => UiText::get('analytics.insight.roas_below_break_even_body', 'Attributed revenue is currently :roas x total campaign budget for the selected scope.', ['roas' => number_format((float) $roas, 2)]),
                ];
            }
        } elseif (! empty($summary['financial_reason'])) {
            $insights[] = [
                'level' => 'neutral',
                'title' => UiText::get('analytics.insight.financial_na', 'Financial KPIs are N/A'),
                'body' => (string) $summary['financial_reason'],
            ];
        }

        if ($campaigns !== []) {
            $noSubmission = collect($campaigns)
                ->filter(fn (array $row): bool => (float) ($row['budget'] ?? 0) > 0 && (int) ($row['views'] ?? 0) >= 50 && (int) ($row['submissions'] ?? 0) === 0)
                ->first();
            if (is_array($noSubmission)) {
                $insights[] = [
                    'level' => 'warning',
                    'title' => UiText::get('analytics.insight.traffic_no_submissions', 'Campaign has traffic but no submissions'),
                    'body' => UiText::get('analytics.insight.traffic_no_submissions_body', ':name recorded traffic without a submission in the selected period.', ['name' => (string) $noSubmission['name']]),
                ];
            }
        }

        if ($insights === []) {
            $insights[] = [
                'level' => 'neutral',
                'title' => UiText::get('analytics.insight.not_enough_data', 'Not enough data for a statistical insight'),
                'body' => UiText::get('analytics.insight.not_enough_data_body', 'Collect more views and submissions, or widen the reporting period.'),
            ];
        }

        return array_slice($insights, 0, 6);
    }
}
