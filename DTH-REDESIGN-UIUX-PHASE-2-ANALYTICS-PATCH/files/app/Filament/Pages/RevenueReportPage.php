<?php

namespace App\Filament\Pages;

use App\Models\Crm\Staff;
use App\Models\Marketing\Campaign;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\MarketingCampaign;
use App\Models\Sales\Service;
use App\Models\Sales\ServicePackage;
use App\Services\Dashboard\AnalyticsPeriodService;
use App\Services\Finance\RevenueReportService;
use Carbon\Carbon;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RevenueReportPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?int $navigationSort = 30;
    protected static bool $shouldRegisterNavigation = false;
    protected static string $view = 'filament.pages.revenue-report';

    public string $startDate = '';
    public string $endDate = '';
    public ?int $marketingCampaignId = null;
    public ?int $emailCampaignId = null;
    public ?int $landingPageId = null;
    public string $utmSource = '';
    public string $utmMedium = '';
    public string $utmCampaign = '';
    public string $utmContent = '';
    public ?int $serviceId = null;
    public ?int $servicePackageId = null;
    public ?int $salesStaffId = null;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
    }

    public static function getNavigationGroup(): string { return __('navigation.group.finance'); }
    public static function getNavigationLabel(): string { return __('analytics.revenue_analysis'); }
    public function getTitle(): string { return __('analytics.revenue_analysis'); }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && (
            $user->isAdmin()
            || $user->canReadAcrossBusiness()
            || $user->isFinanceStaff()
            || $user->isSalesManager()
            || $user->isMarketingManager()
        );
    }

    public function resetFilters(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
        $this->marketingCampaignId = null;
        $this->emailCampaignId = null;
        $this->landingPageId = null;
        $this->utmSource = '';
        $this->utmMedium = '';
        $this->utmCampaign = '';
        $this->utmContent = '';
        $this->serviceId = null;
        $this->servicePackageId = null;
        $this->salesStaffId = null;
    }

    public function getFiltersProperty(): array
    {
        return [
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'marketing_campaign_id' => $this->marketingCampaignId,
            'email_campaign_id' => $this->emailCampaignId,
            'landing_page_id' => $this->landingPageId,
            'utm_source' => $this->utmSource,
            'utm_medium' => $this->utmMedium,
            'utm_campaign' => $this->utmCampaign,
            'utm_content' => $this->utmContent,
            'service_id' => $this->serviceId,
            'service_package_id' => $this->servicePackageId,
            'sales_staff_id' => $this->salesStaffId,
        ];
    }

    public function getReportProperty(): array
    {
        $service = app(RevenueReportService::class);
        $viewer = auth()->user();
        $filters = $this->filters;
        [$start, $end, $previousStart, $previousEnd] = $this->comparisonRange();
        $previousFilters = array_merge($filters, [
            'start_date' => $previousStart->toDateString(),
            'end_date' => $previousEnd->toDateString(),
        ]);
        $summary = $service->summary($filters, $viewer);
        $previousSummary = $service->summary($previousFilters, $viewer);
        $periods = app(AnalyticsPeriodService::class);
        $currentBuckets = $periods->buckets($start, $end);
        $previousBuckets = $periods->buckets($previousStart, $previousEnd, count($currentBuckets));
        $trendCurrent = [];
        $trendPrevious = [];

        foreach ($currentBuckets as $bucket) {
            $bucketFilters = array_merge($filters, [
                'start_date' => $bucket['start']->toDateString(),
                'end_date' => $bucket['end']->toDateString(),
            ]);
            $trendCurrent[] = [
                'label' => $bucket['label'],
                'value' => (float) $service->summary($bucketFilters, $viewer)['net_revenue'],
            ];
        }
        foreach ($previousBuckets as $index => $bucket) {
            $bucketFilters = array_merge($filters, [
                'start_date' => $bucket['start']->toDateString(),
                'end_date' => $bucket['end']->toDateString(),
            ]);
            $trendPrevious[] = [
                'label' => $trendCurrent[$index]['label'] ?? $bucket['label'],
                'value' => (float) $service->summary($bucketFilters, $viewer)['net_revenue'],
            ];
        }

        return [
            'summary' => $summary,
            'previous_summary' => $previousSummary,
            'deltas' => [
                'gross_collected' => $periods->delta($summary['gross_collected'], $previousSummary['gross_collected']),
                'net_revenue' => $periods->delta($summary['net_revenue'], $previousSummary['net_revenue']),
                'payments' => $periods->delta($summary['payments'], $previousSummary['payments']),
                'customers' => $periods->delta($summary['customers'], $previousSummary['customers']),
            ],
            'trend' => ['current' => $trendCurrent, 'previous' => $trendPrevious],
            'marketing_campaigns' => $service->marketingCampaigns($filters, $viewer),
            'email_campaigns' => $service->emailCampaigns($filters, $viewer),
            'sources' => $service->sources($filters, $viewer),
            'utm_campaigns' => $service->utmCampaigns($filters, $viewer),
            'landing_pages' => $service->landingPages($filters, $viewer),
            'services' => $service->services($filters, $viewer),
            'packages' => $service->packages($filters, $viewer),
            'sales' => $service->sales($filters, $viewer),
            'customers' => $service->customers($filters, $viewer),
        ];
    }

    /** @return array{Carbon, Carbon, Carbon, Carbon} */
    private function comparisonRange(): array
    {
        $start = Carbon::parse($this->startDate ?: now()->startOfMonth()->toDateString())->startOfDay();
        $end = Carbon::parse($this->endDate ?: now()->toDateString())->endOfDay();
        $days = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1);
        $previousEnd = $start->copy()->subSecond();
        $previousStart = $previousEnd->copy()->subDays($days - 1)->startOfDay();

        return [$start, $end, $previousStart, $previousEnd];
    }

    public function exportCsv(): StreamedResponse
    {
        $report = $this->report;
        $filename = 'revenue-analysis-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($report): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [__('analytics.revenue_analysis')]);
            fputcsv($out, [__('analytics.metric'), __('analytics.current_period'), __('analytics.previous_period')]);
            foreach (['gross_collected', 'net_revenue', 'payments', 'customers'] as $key) {
                fputcsv($out, [__('analytics.'.$key), $report['summary'][$key] ?? 0, $report['previous_summary'][$key] ?? 0]);
            }
            fputcsv($out, []);
            foreach ([
                'marketing_campaigns' => __('analytics.marketing_campaigns'),
                'sources' => __('analytics.revenue_by_source'),
                'services' => __('analytics.revenue_by_service'),
                'sales' => __('analytics.sales_team_performance'),
            ] as $key => $heading) {
                fputcsv($out, [$heading]);
                fputcsv($out, [__('field.name'), __('analytics.payments'), __('analytics.paid_customers'), __('analytics.net_revenue')]);
                foreach (($report[$key] ?? []) as $row) {
                    fputcsv($out, [$row['name'] ?? '', $row['payments'] ?? '', $row['paid_customers'] ?? '', $row['net_revenue'] ?? '']);
                }
                fputcsv($out, []);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function getOptionsProperty(): array
    {
        return [
            'marketing_campaigns' => MarketingCampaign::query()->orderBy('name')->pluck('name', 'id')->all(),
            'email_campaigns' => Campaign::query()->orderByDesc('created_at')->pluck('name', 'id')->all(),
            'landing_pages' => LandingPage::query()->orderBy('name')->pluck('name', 'id')->all(),
            'services' => Service::query()->orderBy('name')->pluck('name', 'id')->all(),
            'packages' => ServicePackage::query()->orderBy('name')->pluck('name', 'id')->all(),
            'sales_staff' => Staff::query()->whereHas('department', fn ($q) => $q->where('function_key', 'sales'))->orderBy('full_name')->pluck('full_name', 'id')->all(),
        ];
    }
}
