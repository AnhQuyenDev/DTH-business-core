<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Sales\QuotationApprovalResource;
use App\Filament\Resources\Sales\QuotationResource;
use App\Services\Dashboard\EnterpriseAnalyticsService;
use App\Services\Dashboard\WorkforceAnalyticsService;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static ?int $navigationSort = -10;
    protected static string $view = 'filament.pages.sales-dashboard';

    public string $period = '30d';

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.sales_dashboard');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('sales.view-quotations');
    }

    public function getTitle(): string
    {
        return auth()->user()?->isSalesManager()
            ? __('uiux.dashboard.sales.manager_title')
            : __('uiux.dashboard.sales.staff_title');
    }

    protected function getViewData(): array
    {
        $user = auth()->user();

        return [
            'isManager' => $user->isSalesManager(),
            'analytics' => app(EnterpriseAnalyticsService::class)->sales($user, $this->period),
            'workforce' => $user->isSalesManager() ? app(WorkforceAnalyticsService::class)->report($user, $this->period, \App\Enums\Crm\DepartmentFunction::Sales) : null,
            'links' => [
                'quotations' => QuotationResource::canViewAny() ? QuotationResource::getUrl() : null,
                'approvals' => QuotationApprovalResource::canViewAny() ? QuotationApprovalResource::getUrl() : null,
                'revenue' => RevenueReportPage::canAccess() ? RevenueReportPage::getUrl() : null,
                'workforce' => WorkforceAnalyticsPage::canAccess() ? WorkforceAnalyticsPage::getUrl() : null,
            ],
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $data = app(EnterpriseAnalyticsService::class)->sales(auth()->user(), $this->period);
        $filename = 'sales-performance-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [__('uiux.dashboard.sales.manager_title')]);
            foreach (['sent_quotations', 'viewed_quotations', 'accepted_quotations', 'acceptance_rate', 'paid_customers', 'net_revenue', 'average_payment'] as $key) {
                fputcsv($out, [__('analytics.'.$key), $data['summary'][$key] ?? 0]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
