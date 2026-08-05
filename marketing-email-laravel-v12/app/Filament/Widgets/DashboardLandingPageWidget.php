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
        $totalLP = LandingPage::count();
        $publishedLP = LandingPage::where('status', 'published')->count();
        $draftLP = LandingPage::where('status', 'draft')->count();

        $totalViews = LandingPageView::count();
        $uniqueViews = LandingPageView::distinct('session_id')->count('session_id');

        $totalSubmissions = LandingPageSubmission::count();
        $todaySubmissions = LandingPageSubmission::whereDate('submitted_at', today())->count();

        $conversionRate = $totalViews > 0 ? round(($totalSubmissions / $totalViews) * 100, 1) : 0;
        $personalSubmissions = LandingPageSubmission::where('submission_type', 'personal')->count();
        $businessSubmissions = LandingPageSubmission::where('submission_type', 'business')->count();

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
