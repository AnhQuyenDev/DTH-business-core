<?php

namespace Dth\Commercial\Services;

use Dth\Commercial\Support\UiText;

final class CommercialInsightService
{
    /** @param array<string, mixed>|null $snapshot @return array<int, array{level:string,title:string,body:string,metric:string}> */
    public function insights(?array $snapshot = null): array
    {
        $snapshot ??= app(CommercialAnalyticsService::class)->snapshot();

        $pipeline = (float) ($snapshot['pipeline_value'] ?? 0);
        $weighted = (float) ($snapshot['weighted_pipeline_value'] ?? 0);
        $winRate = (float) ($snapshot['win_rate'] ?? 0);
        $open = (int) ($snapshot['open_opportunities'] ?? 0);
        $closingCount = (int) ($snapshot['closing_this_month_count'] ?? 0);
        $closingValue = (float) ($snapshot['closing_this_month_value'] ?? 0);
        $activeServices = (int) ($snapshot['active_services'] ?? 0);
        $activeProducts = (int) ($snapshot['active_products'] ?? 0);
        $activeBundles = (int) ($snapshot['active_bundles'] ?? ($snapshot['active_packages'] ?? 0));
        $stages = collect($snapshot['stage_breakdown'] ?? []);
        $topServices = collect($snapshot['top_services'] ?? []);

        $coverage = $pipeline > 0 ? round(($weighted / $pipeline) * 100, 1) : 0.0;
        $closingShare = $pipeline > 0 ? round(($closingValue / $pipeline) * 100, 1) : 0.0;
        $largestStage = $stages->sortByDesc('count')->first();
        $topService = $topServices->first();
        $topServiceShare = ($pipeline > 0 && $topService)
            ? round((((float) ($topService['value'] ?? 0)) / $pipeline) * 100, 1)
            : 0.0;

        $insights = [
            [
                'level' => $coverage >= 65 ? 'positive' : ($coverage >= 40 ? 'info' : 'warning'),
                'title' => UiText::get('insights.weighted_pipeline_title', 'Weighted pipeline confidence'),
                'body' => UiText::get(
                    'insights.weighted_pipeline_body',
                    'Weighted pipeline is :coverage% of the gross pipeline. Review probabilities on early-stage opportunities if this ratio is too low.',
                    ['coverage' => number_format($coverage, 1, ',', '.')],
                ),
                'metric' => number_format($weighted, 0, ',', '.').' ₫',
            ],
            [
                'level' => $winRate >= 50 ? 'positive' : ($winRate >= 30 ? 'info' : 'warning'),
                'title' => UiText::get('insights.win_rate_title', 'Win rate'),
                'body' => UiText::get(
                    'insights.win_rate_body',
                    ':rate% of decided opportunities are won. This indicator only uses opportunities that already have a won or lost outcome.',
                    ['rate' => number_format($winRate, 1, ',', '.')],
                ),
                'metric' => number_format($winRate, 1, ',', '.').'%',
            ],
            [
                'level' => $closingCount > 0 ? 'info' : 'neutral',
                'title' => UiText::get('insights.closing_title', 'Expected closing this month'),
                'body' => UiText::get(
                    'insights.closing_body',
                    ':count open opportunities worth :value are expected to close this month, representing :share% of the current pipeline.',
                    [
                        'count' => number_format($closingCount),
                        'value' => number_format($closingValue, 0, ',', '.').' ₫',
                        'share' => number_format($closingShare, 1, ',', '.'),
                    ],
                ),
                'metric' => number_format($closingCount).' / '.number_format($closingValue, 0, ',', '.').' ₫',
            ],
            [
                'level' => $topServiceShare >= 50 ? 'warning' : 'info',
                'title' => UiText::get('insights.concentration_title', 'Service concentration'),
                'body' => $topService
                    ? UiText::get(
                        'insights.concentration_body',
                        ':service contributes :share% of pipeline value. Use this signal to detect excessive dependence on one offer.',
                        [
                            'service' => (string) ($topService['name'] ?? '—'),
                            'share' => number_format($topServiceShare, 1, ',', '.'),
                        ],
                    )
                    : UiText::get('insights.no_service_pipeline', 'There is not enough service-linked opportunity data to evaluate concentration.'),
                'metric' => $topService ? number_format($topServiceShare, 1, ',', '.').'%' : '—',
            ],
            [
                'level' => 'neutral',
                'title' => UiText::get('insights.catalog_title', 'Commercial catalog coverage'),
                'body' => UiText::get(
                    'insights.catalog_body',
                    'The active catalog currently has :services services, :products products and :bundles bundles available for commercial use.',
                    [
                        'services' => number_format($activeServices),
                        'products' => number_format($activeProducts),
                        'bundles' => number_format($activeBundles),
                    ],
                ),
                'metric' => number_format($activeServices).' / '.number_format($activeProducts).' / '.number_format($activeBundles),
            ],
        ];

        if ($largestStage && $open > 0) {
            $insights[] = [
                'level' => ((int) ($largestStage['count'] ?? 0)) >= max(3, (int) ceil($open * .45)) ? 'warning' : 'info',
                'title' => UiText::get('insights.stage_title', 'Pipeline stage concentration'),
                'body' => UiText::get(
                    'insights.stage_body',
                    'The largest stage is :stage with :count opportunities. A high concentration may indicate a follow-up bottleneck.',
                    [
                        'stage' => (string) ($largestStage['label'] ?? '—'),
                        'count' => number_format((int) ($largestStage['count'] ?? 0)),
                    ],
                ),
                'metric' => number_format((int) ($largestStage['count'] ?? 0)),
            ];
        }

        return $insights;
    }
}
