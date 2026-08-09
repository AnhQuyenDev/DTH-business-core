<?php

namespace App\Filament\Widgets;

use App\Enums\Crm\ContactQualificationStatus;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\CustomerInteraction;
use App\Models\Marketing\Campaign;
use App\Models\Marketing\LandingPageSubmission;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardSystemAlertsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getStats(): array
    {
        $overdueTasks = CustomerInteraction::where('status', 'scheduled')
            ->where('next_follow_up_at', '<', now())
            ->count();

        $unassignedLeads = ContactQualification::where('status', ContactQualificationStatus::New->value)
            ->whereNull('assigned_staff_id')
            ->count();

        $unprocessedSubmissions = LandingPageSubmission::where('status', 'received')->count();

        $failedCampaigns = Campaign::where('status', 'failed')->count();

        $todayFollowUps = CustomerInteraction::where('status', 'scheduled')
            ->whereDate('next_follow_up_at', today())
            ->count();

        return [
            Stat::make('Quá hạn chăm sóc', number_format($overdueTasks))
                ->description('Đã quá hạn lịch hẹn')
                ->icon('heroicon-o-exclamation-circle')
                ->color($overdueTasks > 0 ? 'danger' : 'success'),
            Stat::make('Lead chưa phân công', number_format($unassignedLeads))
                ->description('Chưa có nhân viên xử lý')
                ->icon('heroicon-o-inbox')
                ->color($unassignedLeads > 0 ? 'warning' : 'success'),
            Stat::make('Submissions chưa xử lý', number_format($unprocessedSubmissions))
                ->icon('heroicon-o-document-arrow-down')
                ->color($unprocessedSubmissions > 0 ? 'warning' : 'success'),
            Stat::make('Chiến dịch thất bại', number_format($failedCampaigns))
                ->icon('heroicon-o-exclamation-triangle')
                ->color($failedCampaigns > 0 ? 'danger' : 'success'),
            Stat::make('Hôm nay cần chăm sóc', number_format($todayFollowUps))
                ->icon('heroicon-o-calendar')
                ->color($todayFollowUps > 0 ? 'info' : 'success'),
        ];
    }
}
