<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Models\LandingPage;
use Dth\Marketing\Models\LandingPageSubmission;
use Dth\Marketing\Models\LandingPageView;

final class UtmReportService
{
    /**
     * @return array{totals:array{views:int,submissions:int,conversion_rate:float},sources:array<int,array<string,mixed>>,generated_links:int}
     */
    public function forLandingPage(LandingPage $page): array
    {
        $views = LandingPageView::query()
            ->where('landing_page_id', $page->getKey())
            ->get(['utm_source', 'utm_medium', 'utm_campaign']);
        $submissions = LandingPageSubmission::query()
            ->where('landing_page_id', $page->getKey())
            ->get(['utm_source', 'utm_medium', 'utm_campaign']);

        $viewGroups = $views->groupBy(fn (LandingPageView $view): string => $this->sourceKey(
            $view->utm_source,
            $view->utm_medium,
            $view->utm_campaign,
        ));
        $submissionGroups = $submissions->groupBy(fn (LandingPageSubmission $submission): string => $this->sourceKey(
            $submission->utm_source,
            $submission->utm_medium,
            $submission->utm_campaign,
        ));

        $keys = $viewGroups->keys()->merge($submissionGroups->keys())->unique()->sort()->values();
        $rows = $keys->map(function (string $key) use ($viewGroups, $submissionGroups): array {
            [$source, $medium, $campaign] = array_pad(explode('|', $key, 3), 3, '');
            $viewCount = $viewGroups->get($key, collect())->count();
            $submissionCount = $submissionGroups->get($key, collect())->count();

            return [
                'source' => $source !== '' ? $source : 'direct',
                'medium' => $medium !== '' ? $medium : '—',
                'campaign' => $campaign !== '' ? $campaign : '—',
                'views' => $viewCount,
                'submissions' => $submissionCount,
                'conversion_rate' => $viewCount > 0
                    ? round(($submissionCount / $viewCount) * 100, 2)
                    : 0.0,
            ];
        })->values()->all();

        $totalViews = $views->count();
        $totalSubmissions = $submissions->count();

        return [
            'totals' => [
                'views' => $totalViews,
                'submissions' => $totalSubmissions,
                'conversion_rate' => $totalViews > 0
                    ? round(($totalSubmissions / $totalViews) * 100, 2)
                    : 0.0,
            ],
            'sources' => $rows,
            'generated_links' => $page->utmUrls()->count(),
        ];
    }

    private function sourceKey(?string $source, ?string $medium, ?string $campaign): string
    {
        return implode('|', [
            trim((string) $source),
            trim((string) $medium),
            trim((string) $campaign),
        ]);
    }
}
