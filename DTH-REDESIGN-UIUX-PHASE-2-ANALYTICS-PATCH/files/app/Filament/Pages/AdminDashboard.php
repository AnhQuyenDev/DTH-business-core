<?php

namespace App\Filament\Pages;

use App\Filament\Resources\AuditLogResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\Sales\PaymentTrackingResource;
use App\Services\Dashboard\EnterpriseAnalyticsService;
use App\Services\Dashboard\WorkforceAnalyticsService;
use Filament\Pages\Dashboard as BaseDashboard;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminDashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'filament.pages.admin-dashboard';
    protected static ?int $navigationSort = -100;

    public string $period = '30d';

    public static function getNavigationLabel(): string
    {
        return __('filament-panels::pages/dashboard.title');
    }

    public function getTitle(): string
    {
        return __('analytics.executive_dashboard');
    }

    public static function canAccess(): bool
    {
        return auth()->user() !== null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->isAdmin() || $user->canReadAcrossBusiness());
    }

    public function mount(): void
    {
        $user = auth()->user();

        if (! $user || $user->isAdmin() || $user->canReadAcrossBusiness()) {
            return;
        }

        $target = match (true) {
            $user->isFinanceStaff() => FinanceDashboard::class,
            $user->isSalesManager(), $user->isSalesStaff() => SalesDashboard::class,
            $user->isCustomerServiceManager(), $user->isCustomerServiceStaff() => CustomerServiceDashboard::class,
            $user->isMarketingManager(), $user->isMarketingStaff() => MarketingDashboard::class,
            default => StaffDashboard::class,
        };

        if ($target::canAccess()) {
            $this->redirect($target::getUrl());
        }
    }

    public function getWidgets(): array
    {
        return [];
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $analytics = app(EnterpriseAnalyticsService::class)->overview($user, $this->period);
        $workforce = app(WorkforceAnalyticsService::class)->report($user, $this->period);

        return [
            'analytics' => $analytics,
            'workforce' => $workforce,
            'links' => [
                'leads' => LeadResource::canViewAny() ? LeadResource::getUrl() : null,
                'payments' => PaymentTrackingResource::canViewAny() ? PaymentTrackingResource::getUrl() : null,
                'audit' => AuditLogResource::canViewAny() ? AuditLogResource::getUrl() : null,
                'revenue' => RevenueReportPage::canAccess() ? RevenueReportPage::getUrl() : null,
                'campaigns' => CampaignAnalyticsPage::canAccess() ? CampaignAnalyticsPage::getUrl() : null,
                'workforce' => WorkforceAnalyticsPage::canAccess() ? WorkforceAnalyticsPage::getUrl() : null,
            ],
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $data = $this->getViewData();
        $filename = 'executive-analytics-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [__('analytics.executive_dashboard')]);
            fputcsv($out, [__('analytics.metric'), __('analytics.current_period'), __('analytics.previous_period')]);
            foreach (['gross_collected', 'net_revenue', 'paid_customers', 'new_leads', 'customer_interactions'] as $key) {
                fputcsv($out, [__('analytics.'.$key), $data['analytics']['current'][$key] ?? 0, $data['analytics']['previous'][$key] ?? 0]);
            }
            fputcsv($out, []);
            fputcsv($out, [__('analytics.workforce')]);
            fputcsv($out, [__('field.staff'), __('field.department'), __('analytics.activity_index'), __('analytics.activity'), __('analytics.impact')]);
            foreach ($data['workforce']['staff'] as $row) {
                fputcsv($out, [$row['name'], $row['department'], $row['activity_index'], $row['activity_count'], $row['impact_value']]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
