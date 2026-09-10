<?php

namespace Dth\Email\Filament\Widgets\Dashboard;

use Dth\Email\Filament\Resources\EmailCampaignResource;
use Dth\Email\Filament\Resources\EmailDeliveryLogResource;
use Dth\Email\Filament\Widgets\Concerns\UsesEmailDashboardFilters;
use Dth\Email\Filament\Widgets\Dashboard\Concerns\FormatsDashboardMetrics;
use Dth\Email\Services\EmailAnalyticsService;
use Dth\Email\Support\UiText;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EmailOverviewStats extends StatsOverviewWidget
{
    use FormatsDashboardMetrics;
    use UsesEmailDashboardFilters;

    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;
    protected int|array|null $columns = [
        'md' => 2,
        'xl' => 4,
    ];

    protected function getStats(): array
    {
        $service = app(EmailAnalyticsService::class);
        $filters = $this->analyticsFilters();
        $overview = $service->overview($filters);
        $trend = $service->trend($filters);
        $snapshot = $overview->current;

        $campaignMeta = $this->deltaMeta($overview->deltas['campaigns'] ?? null);
        $recipientMeta = $this->deltaMeta($overview->deltas['recipients'] ?? null);
        $sentMeta = $this->deltaMeta($overview->deltas['sent'] ?? null);
        $openMeta = $this->deltaMeta($overview->deltas['open_rate'] ?? null);
        $clickMeta = $this->deltaMeta($overview->deltas['click_rate'] ?? null);
        $ctorMeta = $this->deltaMeta($overview->deltas['click_to_open_rate'] ?? null);
        $unsubscribeMeta = $this->deltaMeta($overview->deltas['unsubscribe_rate'] ?? null, lowerIsBetter: true);
        $failureMeta = $this->deltaMeta($overview->deltas['failure_rate'] ?? null, lowerIsBetter: true);

        $sentTrend = array_map(static fn ($point): int => $point->sent, $trend);
        $openTrend = array_map(static fn ($point): int => $point->uniqueOpened, $trend);
        $clickTrend = array_map(static fn ($point): int => $point->uniqueClicked, $trend);
        $unsubscribeTrend = array_map(static fn ($point): int => $point->unsubscribed, $trend);
        $failureTrend = array_map(static fn ($point): int => $point->failed, $trend);

        return [
            Stat::make(UiText::get('dashboard.metrics.campaigns', 'Campaigns'), $this->number($snapshot->campaigns))
                ->icon('heroicon-o-megaphone')
                ->description($campaignMeta['description'])
                ->descriptionIcon($campaignMeta['icon'])
                ->descriptionColor($campaignMeta['color'])
                ->color('primary')
                ->url(EmailCampaignResource::getUrl('index')),

            Stat::make(UiText::get('dashboard.metrics.recipients', 'Recipients'), $this->number($snapshot->recipients))
                ->icon('heroicon-o-users')
                ->description($recipientMeta['description'])
                ->descriptionIcon($recipientMeta['icon'])
                ->descriptionColor($recipientMeta['color'])
                ->color('info')
                ->url(EmailCampaignResource::getUrl('index')),

            Stat::make(UiText::get('dashboard.metrics.sent', 'Sent'), $this->number($snapshot->sent))
                ->icon('heroicon-o-paper-airplane')
                ->description($sentMeta['description'])
                ->descriptionIcon($sentMeta['icon'])
                ->descriptionColor($sentMeta['color'])
                ->color('success')
                ->chart($sentTrend)
                ->url(EmailDeliveryLogResource::getUrl('index')),

            Stat::make(UiText::get('dashboard.metrics.open_rate', 'Open rate'), $this->percent($snapshot->openRate))
                ->icon('heroicon-o-envelope-open')
                ->description($openMeta['description'])
                ->descriptionIcon($openMeta['icon'])
                ->descriptionColor($openMeta['color'])
                ->color('primary')
                ->chart($openTrend),

            Stat::make(UiText::get('dashboard.metrics.click_rate', 'Click rate'), $this->percent($snapshot->clickRate))
                ->icon('heroicon-o-cursor-arrow-rays')
                ->description($clickMeta['description'])
                ->descriptionIcon($clickMeta['icon'])
                ->descriptionColor($clickMeta['color'])
                ->color('success')
                ->chart($clickTrend),

            Stat::make(UiText::get('dashboard.metrics.ctor', 'Click-to-open rate'), $this->percent($snapshot->clickToOpenRate))
                ->icon('heroicon-o-bolt')
                ->description($ctorMeta['description'])
                ->descriptionIcon($ctorMeta['icon'])
                ->descriptionColor($ctorMeta['color'])
                ->color('warning')
                ->chart($clickTrend),

            Stat::make(UiText::get('dashboard.metrics.unsubscribe_rate', 'Unsubscribe rate'), $this->percent($snapshot->unsubscribeRate))
                ->icon('heroicon-o-user-minus')
                ->description($unsubscribeMeta['description'])
                ->descriptionIcon($unsubscribeMeta['icon'])
                ->descriptionColor($unsubscribeMeta['color'])
                ->color($snapshot->unsubscribeRate > 1 ? 'danger' : 'gray')
                ->chart($unsubscribeTrend),

            Stat::make(UiText::get('dashboard.metrics.failure_rate', 'Failure rate'), $this->percent($snapshot->failureRate))
                ->icon('heroicon-o-exclamation-triangle')
                ->description($failureMeta['description'])
                ->descriptionIcon($failureMeta['icon'])
                ->descriptionColor($failureMeta['color'])
                ->color($snapshot->failureRate > 0 ? 'danger' : 'success')
                ->chart($failureTrend),
        ];
    }
}
