<?php

namespace Dth\Email\Filament\Resources\EmailCampaignResource\Widgets;

use Dth\Email\Services\CampaignAnalyticsService;
use Dth\Email\Support\UiText;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CampaignAnalyticsWidget extends StatsOverviewWidget
{
    public int $campaignId;

    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;
    protected int|string|array $columnSpan = 'full';

    protected function getColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 4,
        ];
    }

    protected function getStats(): array
    {
        $stats = app(CampaignAnalyticsService::class)->forCampaignId($this->campaignId);

        return [
            Stat::make(
                UiText::get('analytics.recipients', 'Recipients'),
                number_format($stats->total)
            )
                ->description(UiText::get(
                    'analytics.recipients_description',
                    'Pending :pending · Queued :queued',
                    ['pending' => $stats->pending, 'queued' => $stats->queued]
                ))
                ->descriptionIcon('heroicon-o-users'),

            Stat::make(
                UiText::get('analytics.sent', 'Sent'),
                number_format($stats->sent)
            )
                ->description(UiText::get(
                    'analytics.sent_description',
                    'Failed :failed · Suppressed :suppressed',
                    ['failed' => $stats->failed, 'suppressed' => $stats->suppressed]
                ))
                ->descriptionIcon('heroicon-o-paper-airplane'),

            Stat::make(
                UiText::get('reports.unique_opens', 'Unique opens'),
                number_format($stats->opened)
            )
                ->description(UiText::get(
                    'reports.total_opens_description',
                    ':total total opens',
                    ['total' => number_format($stats->totalOpens)]
                ))
                ->descriptionIcon('heroicon-o-envelope-open'),

            Stat::make(
                UiText::get('dashboard.metrics.open_rate', 'Open rate'),
                number_format($stats->openRate, 1).'%'
            )
                ->description(UiText::get('reports.rate_of_sent', 'Unique opens / sent'))
                ->descriptionIcon('heroicon-o-chart-bar'),

            Stat::make(
                UiText::get('reports.unique_clicks', 'Unique clicks'),
                number_format($stats->clicked)
            )
                ->description(UiText::get(
                    'reports.total_clicks_description',
                    ':total total clicks',
                    ['total' => number_format($stats->totalClicks)]
                ))
                ->descriptionIcon('heroicon-o-cursor-arrow-rays'),

            Stat::make(
                UiText::get('dashboard.metrics.click_rate', 'Click rate'),
                number_format($stats->clickRate, 1).'%'
            )
                ->description(UiText::get('reports.click_rate_basis', 'Unique clicks / sent'))
                ->descriptionIcon('heroicon-o-arrow-trending-up'),

            Stat::make(
                UiText::get('dashboard.metrics.ctor', 'Click-to-open rate'),
                number_format($stats->clickToOpenRate, 1).'%'
            )
                ->description(UiText::get('reports.ctor_basis', 'Unique clicks / unique opens'))
                ->descriptionIcon('heroicon-o-bolt'),

            Stat::make(
                UiText::get('analytics.unsubscribed', 'Unsubscribed from campaign'),
                number_format($stats->unsubscribed)
            )
                ->description(UiText::get(
                    'analytics.unsubscribe_rate',
                    ':rate% historical unsubscribe rate',
                    ['rate' => $stats->unsubscribeRate]
                ))
                ->descriptionIcon('heroicon-o-user-minus'),
        ];
    }
}
