<?php

namespace App\Filament\Widgets;

use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageSubmission;
use App\Models\Marketing\LandingPageView;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardLandingPageWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getStats(): array
    {
        $pageCounts = LandingPage::query()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $totalLP = (int) $pageCounts->sum();
        $publishedLP = (int) $pageCounts->get('published', 0);
        $draftLP = (int) $pageCounts->get('draft', 0);

        $viewStats = LandingPageView::query()->selectRaw('COUNT(*) as total_views, COUNT(DISTINCT session_id) as unique_views')->first();
        $totalViews = (int) ($viewStats?->total_views ?? 0);
        $uniqueViews = (int) ($viewStats?->unique_views ?? 0);

        $submissionStats = LandingPageSubmission::query()
            ->selectRaw('COUNT(*) as total_submissions')
            ->selectRaw('SUM(CASE WHEN submitted_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as today_submissions', [today()->startOfDay(), today()->endOfDay()])
            ->selectRaw("SUM(CASE WHEN submission_type = 'personal' THEN 1 ELSE 0 END) as personal_submissions")
            ->selectRaw("SUM(CASE WHEN submission_type = 'business' THEN 1 ELSE 0 END) as business_submissions")
            ->first();
        $totalSubmissions = (int) ($submissionStats?->total_submissions ?? 0);
        $todaySubmissions = (int) ($submissionStats?->today_submissions ?? 0);

        $conversionRate = $totalViews > 0 ? round(($totalSubmissions / $totalViews) * 100, 1) : 0;
        $personalSubmissions = (int) ($submissionStats?->personal_submissions ?? 0);
        $businessSubmissions = (int) ($submissionStats?->business_submissions ?? 0);

        return [
            Stat::make(__('dashboard.landing_page.total'), number_format($totalLP))
                ->description(__('dashboard.landing_page.published').': '.number_format($publishedLP).' / '.__('dashboard.landing_page.draft').': '.number_format($draftLP))
                ->icon('heroicon-o-globe-alt')
                ->color('primary'),
            Stat::make(__('dashboard.landing_page.views'), number_format($totalViews))
                ->description(__('dashboard.landing_page.unique_views').': '.number_format($uniqueViews))
                ->icon('heroicon-o-eye')
                ->color('info'),
            Stat::make(__('dashboard.landing_page.submissions'), number_format($totalSubmissions))
                ->description(__('dashboard.landing_page.today').': '.number_format($todaySubmissions))
                ->icon('heroicon-o-document-arrow-down')
                ->color('success'),
            Stat::make(__('dashboard.landing_page.conversion_rate'), "{$conversionRate}%")
                ->description(__('dashboard.landing_page.personal').': '.number_format($personalSubmissions).' / '.__('dashboard.landing_page.business').': '.number_format($businessSubmissions))
                ->icon('heroicon-o-arrow-trending-up')
                ->color('warning'),
        ];
    }
}
