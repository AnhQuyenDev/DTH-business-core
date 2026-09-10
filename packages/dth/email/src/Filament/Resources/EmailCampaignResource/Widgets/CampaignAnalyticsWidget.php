<?php

namespace Dth\Email\Filament\Resources\EmailCampaignResource\Widgets;

use Dth\Email\Services\CampaignAnalyticsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CampaignAnalyticsWidget extends StatsOverviewWidget
{
    public int $campaignId;

    /**
     * Render analytics with the parent page instead of triggering an
     * immediate lazy Livewire request. This avoids the page-expired (419)
     * loop that can occur on Filament 4 resource pages when the session/CSRF
     * token changes between the initial page response and lazy hydration.
     */
    protected static bool $isLazy = false;

    protected ?string $pollingInterval = null;

    protected function getStats(): array
    {
        $stats = app(
            CampaignAnalyticsService::class
        )->forCampaignId(
            $this->campaignId
        );

        return [
            Stat::make(
                'Recipients',
                number_format($stats->total)
            )
                ->description(
                    "Pending {$stats->pending} · Queued {$stats->queued}"
                )
                ->descriptionIcon(
                    'heroicon-o-users'
                ),

            Stat::make(
                'Sent',
                number_format($stats->sent)
            )
                ->description(
                    "Failed {$stats->failed} · Suppressed {$stats->suppressed}"
                )
                ->descriptionIcon(
                    'heroicon-o-paper-airplane'
                ),

            Stat::make(
                'Delivered',
                number_format($stats->delivered)
            )
                ->description(
                    "{$stats->deliveryRate}% of sent"
                )
                ->descriptionIcon(
                    'heroicon-o-check-circle'
                ),

            Stat::make(
                'Opened',
                number_format($stats->opened)
            )
                ->description(
                    "{$stats->openRate}% open rate"
                )
                ->descriptionIcon(
                    'heroicon-o-envelope-open'
                ),

            Stat::make(
                'Clicked',
                number_format($stats->clicked)
            )
                ->description(
                    "{$stats->clickRate}% click rate"
                )
                ->descriptionIcon(
                    'heroicon-o-cursor-arrow-rays'
                ),

            Stat::make(
                'Unsubscribed from campaign',
                number_format($stats->unsubscribed)
            )
                ->description(
                    "{$stats->unsubscribeRate}% historical unsubscribe rate"
                )
                ->descriptionIcon(
                    'heroicon-o-user-minus'
                ),
        ];
    }
}