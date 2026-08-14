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
        $interactionStats = CustomerInteraction::query()
            ->where('status', 'scheduled')
            ->selectRaw('SUM(CASE WHEN next_follow_up_at < ? THEN 1 ELSE 0 END) as overdue_tasks', [now()])
            ->selectRaw('SUM(CASE WHEN next_follow_up_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as today_follow_ups', [today()->startOfDay(), today()->endOfDay()])
            ->first();
        $overdueTasks = (int) ($interactionStats?->overdue_tasks ?? 0);

        $unassignedLeads = ContactQualification::where('status', ContactQualificationStatus::New->value)
            ->whereNull('assigned_staff_id')
            ->count();

        $unprocessedSubmissions = LandingPageSubmission::where('status', 'received')->count();

        $failedCampaigns = Campaign::where('status', 'failed')->count();

        $todayFollowUps = (int) ($interactionStats?->today_follow_ups ?? 0);

        return [
            Stat::make(__('dashboard.system_alerts.overdue_follow_up'), number_format($overdueTasks))
                ->description(__('dashboard.system_alerts.overdue_follow_up_desc'))
                ->icon('heroicon-o-exclamation-circle')
                ->color($overdueTasks > 0 ? 'danger' : 'success'),
            Stat::make(__('dashboard.system_alerts.unassigned_leads'), number_format($unassignedLeads))
                ->description(__('dashboard.system_alerts.unassigned_leads_desc'))
                ->icon('heroicon-o-inbox')
                ->color($unassignedLeads > 0 ? 'warning' : 'success'),
            Stat::make(__('dashboard.system_alerts.pending_submissions'), number_format($unprocessedSubmissions))
                ->icon('heroicon-o-document-arrow-down')
                ->color($unprocessedSubmissions > 0 ? 'warning' : 'success'),
            Stat::make(__('dashboard.system_alerts.failed_campaigns'), number_format($failedCampaigns))
                ->icon('heroicon-o-exclamation-triangle')
                ->color($failedCampaigns > 0 ? 'danger' : 'success'),
            Stat::make(__('dashboard.system_alerts.today_follow_up'), number_format($todayFollowUps))
                ->icon('heroicon-o-calendar')
                ->color($todayFollowUps > 0 ? 'info' : 'success'),
        ];
    }
}
