<?php

namespace App\Filament\Pages;

use App\Filament\Resources\LandingPageResource;
use App\Filament\Resources\MarketingCampaignResource;
use App\Services\Dashboard\EnterpriseAnalyticsService;
use App\Services\Dashboard\DashboardSnapshotCache;
use App\Services\Dashboard\WorkforceAnalyticsService;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarketingDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?int $navigationSort = -10;
    protected static string $view = 'filament.pages.marketing-dashboard';

    public string $period = '30d';

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.marketing');
    }

    public static function getNavigationLabel(): string
    {
        return __('uiux.dashboard.marketing.title');
    }

    public function getTitle(): string
    {
        return auth()->user()?->isMarketingManager()
            ? __('uiux.dashboard.marketing.title')
            : __('uiux.dashboard.marketing.my_work');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('marketing.view-reports');
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $cache = app(DashboardSnapshotCache::class);

        return [
            'isManager' => $user->isMarketingManager(),
            'analytics' => $cache->remember($user, 'marketing', $this->period, fn (): array => app(EnterpriseAnalyticsService::class)->marketing($user, $this->period)),
            'workforce' => $user->isMarketingManager()
                ? $cache->remember($user, 'workforce-marketing', $this->period, fn (): array => app(WorkforceAnalyticsService::class)->report($user, $this->period, \App\Enums\Crm\DepartmentFunction::Marketing))
                : null,
            'links' => [
                'campaigns' => MarketingCampaignResource::canViewAny() ? MarketingCampaignResource::getUrl() : null,
                'landing_pages' => LandingPageResource::canViewAny() ? LandingPageResource::getUrl() : null,
                'campaign_analysis' => CampaignAnalyticsPage::canAccess() ? CampaignAnalyticsPage::getUrl() : null,
                'revenue' => RevenueReportPage::canAccess() ? RevenueReportPage::getUrl() : null,
                'workforce' => WorkforceAnalyticsPage::canAccess() ? WorkforceAnalyticsPage::getUrl() : null,
            ],
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $user = auth()->user();
        $data = app(DashboardSnapshotCache::class)->remember($user, 'marketing', $this->period, fn (): array => app(EnterpriseAnalyticsService::class)->marketing($user, $this->period));
        $filename = 'marketing-analytics-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [__('uiux.dashboard.marketing.title')]);
            foreach (['views', 'submissions', 'leads', 'paid_customers', 'net_revenue', 'budget', 'roas'] as $key) {
                fputcsv($out, [__('analytics.'.$key), $data['summary'][$key] ?? 0]);
            }
            fputcsv($out, []);
            fputcsv($out, [__('analytics.marketing_campaigns')]);
            fputcsv($out, [__('field.name'), __('analytics.leads'), __('analytics.paid_customers'), __('analytics.net_revenue'), 'ROAS']);
            foreach ($data['campaigns'] as $row) {
                fputcsv($out, [$row['name'], $row['leads'], $row['paid_customers'], $row['net_revenue'], $row['roas']]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
