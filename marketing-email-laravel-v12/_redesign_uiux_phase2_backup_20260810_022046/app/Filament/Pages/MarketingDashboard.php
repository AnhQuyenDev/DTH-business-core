<?php

namespace App\Filament\Pages;

use App\Filament\Resources\LandingPageResource;
use App\Filament\Resources\MarketingCampaignResource;
use App\Filament\Resources\LeadResource;
use App\Models\Finance\Payment;
use App\Models\Marketing\LandingPageSubmission;
use App\Services\Dashboard\DashboardScopeService;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class MarketingDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?int $navigationSort = -10;
    protected static string $view = 'filament.pages.marketing-dashboard';

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

        return $user !== null && ($user->isMarketingManager() || $user->isMarketingStaff());
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $scope = app(DashboardScopeService::class);

        $campaigns = $scope->marketingCampaigns($user);
        $landingPages = $scope->landingPages($user);
        $submissions = $scope->submissions($user);
        $leads = $scope->leads($user);
        $payments = $scope->payments($user)->verified();

        $sourceRows = (clone $payments)
            ->with('attribution')
            ->whereBetween('paid_at', [now()->subMonths(5)->startOfMonth(), now()->endOfDay()])
            ->get()
            ->groupBy(fn (Payment $payment): string => $payment->attribution?->utm_source
                ?: $payment->attribution?->acquisition_source
                ?: __('common.not_available'))
            ->map(fn (Collection $rows, string $source): array => [
                'label' => $source,
                'value' => (float) $rows->sum('net_amount'),
                'count' => $rows->count(),
            ])
            ->sortByDesc('value')
            ->values()
            ->take(8)
            ->all();

        $topLandingPages = (clone $submissions)
            ->selectRaw('landing_page_id, count(*) as total')
            ->whereNotNull('landing_page_id')
            ->groupBy('landing_page_id')
            ->with('landingPage:id,name')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(fn (LandingPageSubmission $row): array => [
                'name' => $row->landingPage?->name ?: __('common.not_available'),
                'total' => (int) $row->getAttribute('total'),
            ])
            ->all();

        return [
            'isManager' => $user->isMarketingManager(),
            'summary' => [
                'active_campaigns' => (clone $campaigns)->where('status', 'active')->count(),
                'landing_pages' => (clone $landingPages)->where('status', 'published')->count(),
                'submissions' => (clone $submissions)->whereDate('submitted_at', '>=', now()->startOfMonth())->count(),
                'leads' => (clone $leads)->whereDate('created_at', '>=', now()->startOfMonth())->count(),
                'paid_customers' => (clone $payments)->whereDate('paid_at', '>=', now()->startOfMonth())->distinct('customer_id')->count('customer_id'),
                'revenue' => (float) (clone $payments)->whereDate('paid_at', '>=', now()->startOfMonth())->sum('net_amount'),
            ],
            'sourceRows' => $sourceRows,
            'topLandingPages' => $topLandingPages,
            'links' => [
                'campaigns' => MarketingCampaignResource::canViewAny() ? MarketingCampaignResource::getUrl() : null,
                'landing_pages' => LandingPageResource::canViewAny() ? LandingPageResource::getUrl() : null,
                'leads' => LeadResource::canViewAny() ? LeadResource::getUrl() : null,
                'campaign_report' => CampaignReportPage::canAccess() ? CampaignReportPage::getUrl() : null,
                'revenue_report' => RevenueReportPage::canAccess() ? RevenueReportPage::getUrl() : null,
            ],
        ];
    }


    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = $this->getViewData();
        $filename = 'marketing-dashboard-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [__('uiux.dashboard.marketing.title')]);
            foreach ($data['summary'] as $key => $value) {
                $labelKey = match ($key) {
                    'leads' => 'generated_leads',
                    'revenue' => 'attributed_revenue',
                    default => $key,
                };
                fputcsv($out, [__('uiux.dashboard.marketing.'.$labelKey), $value]);
            }
            fputcsv($out, []);
            fputcsv($out, [__('uiux.dashboard.marketing.source_performance')]);
            fputcsv($out, [__('finance.report.source'), __('uiux.dashboard.common.revenue'), __('uiux.dashboard.common.count')]);
            foreach ($data['sourceRows'] as $row) {
                fputcsv($out, [$row['label'], $row['value'], $row['count']]);
            }
            fputcsv($out, []);
            fputcsv($out, [__('uiux.dashboard.marketing.top_landing_pages')]);
            fputcsv($out, [__('field.name'), __('uiux.dashboard.common.count')]);
            foreach ($data['topLandingPages'] as $row) {
                fputcsv($out, [$row['name'], $row['total']]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

}
