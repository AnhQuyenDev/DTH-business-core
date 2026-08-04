<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardCampaignWidget;
use App\Filament\Widgets\DashboardStaffDetailTableWidget;
use App\Filament\Widgets\DashboardStaffWorkloadWidget;
use App\Filament\Widgets\DashboardSystemAlertsWidget;
use App\Filament\Widgets\DashboardUpcomingScheduleWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class AdminDashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.admin-dashboard';

    protected static ?int $navigationSort = -2;

    public static function getNavigationLabel(): string
    {
        return __('filament-panels::pages/dashboard.title');
    }

    public function getTitle(): string
    {
        return __('filament-panels::pages/dashboard.title');
    }

    public function getWidgets(): array
    {
        return [
            DashboardSystemAlertsWidget::class,
            DashboardStaffWorkloadWidget::class,
            DashboardUpcomingScheduleWidget::class,
            DashboardStaffDetailTableWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 3;
    }

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user?->isAdmin()) {
            foreach ([
                StaffDashboard::class,
                SalesDashboard::class,
                CustomerCarePage::class,
                CampaignReportPage::class,
            ] as $page) {
                if ($page::canAccess()) {
                    $this->redirect($page::getUrl());
                    return;
                }
            }
        }
    }

    public static function canAccess(): bool
    {
        return auth()->user() !== null;
    }

    public function getTabs(): array
    {
        return [
            'email'     => __('dashboard.tab.email'),
            'marketing' => __('dashboard.tab.marketing'),
            'customers' => __('dashboard.tab.customers'),
            'staff'     => __('dashboard.tab.staff'),
        ];
    }
}
