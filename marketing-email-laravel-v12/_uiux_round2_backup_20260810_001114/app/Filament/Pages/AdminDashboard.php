<?php

namespace App\Filament\Pages;

use App\Filament\Resources\CampaignResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\MarketingCampaignResource;
use App\Filament\Resources\Sales\OpportunityResource;
use App\Filament\Resources\Sales\PaymentTrackingResource;
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

    public function getColumns(): int|array
    {
        return 3;
    }

    public function mount(): void
    {
        $user = auth()->user();

        if (! $user || $user->isAdmin() || $user->canReadAcrossBusiness()) {
            return;
        }

        if ($user->isFinanceStaff() && PaymentTrackingResource::canViewAny()) {
            $this->redirect(PaymentTrackingResource::getUrl());
            return;
        }

        if (($user->isSalesManager() || $user->isSalesStaff()) && OpportunityResource::canViewAny()) {
            $this->redirect(OpportunityResource::getUrl());
            return;
        }

        if (($user->isCustomerServiceManager() || $user->isCustomerServiceStaff()) && LeadResource::canViewAny()) {
            $this->redirect(LeadResource::getUrl());
            return;
        }

        if ($user->isMarketingManager() || $user->isMarketingStaff()) {
            if (MarketingCampaignResource::canViewAny()) {
                $this->redirect(MarketingCampaignResource::getUrl());
                return;
            }

            if (CampaignResource::canViewAny()) {
                $this->redirect(CampaignResource::getUrl());
                return;
            }
        }

        if (StaffDashboard::canAccess()) {
            $this->redirect(StaffDashboard::getUrl());
        }
    }

    public static function canAccess(): bool
    {
        return auth()->user() !== null;
    }

    public function getTabs(): array
    {
        return [
            'email' => __('dashboard.tab.email'),
            'marketing' => __('dashboard.tab.marketing'),
            'customers' => __('dashboard.tab.customers'),
            'staff' => __('dashboard.tab.staff'),
        ];
    }
}
