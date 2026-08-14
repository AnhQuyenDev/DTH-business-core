<?php

namespace App\Filament\Pages;

use App\Services\Dashboard\EnterpriseAnalyticsService;
use App\Services\Dashboard\DashboardSnapshotCache;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CampaignAnalyticsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.campaign-analytics';

    public string $period = '30d';

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.marketing');
    }

    public static function getNavigationLabel(): string
    {
        return __('analytics.campaign_analysis');
    }

    public function getTitle(): string
    {
        return __('analytics.campaign_analysis');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('marketing.view-reports');
    }

    protected function getViewData(): array
    {
        $user = auth()->user();

        return [
            'analytics' => app(DashboardSnapshotCache::class)->remember($user, 'campaign-analysis', $this->period, fn (): array => app(EnterpriseAnalyticsService::class)->campaignAnalysis($user, $this->period)),
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $user = auth()->user();
        $data = app(DashboardSnapshotCache::class)->remember($user, 'campaign-analysis', $this->period, fn (): array => app(EnterpriseAnalyticsService::class)->campaignAnalysis($user, $this->period));
        $filename = 'campaign-analytics-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [__('analytics.marketing_campaigns')]);
            fputcsv($out, [__('field.name'), __('analytics.leads'), __('analytics.paid_customers'), __('analytics.net_revenue'), 'ROAS']);
            foreach ($data['marketing_campaigns'] as $row) {
                fputcsv($out, [$row['name'], $row['leads'], $row['paid_customers'], $row['net_revenue'], $row['roas']]);
            }
            fputcsv($out, []);
            fputcsv($out, [__('analytics.email_campaigns')]);
            fputcsv($out, [__('field.name'), __('analytics.sent'), __('analytics.open_rate'), __('analytics.click_rate'), __('analytics.paid_customers'), __('analytics.net_revenue')]);
            foreach ($data['email_campaigns'] as $row) {
                fputcsv($out, [$row['name'], $row['sent'], $row['open_rate'], $row['click_rate'], $row['paid_customers'], $row['net_revenue']]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
