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
                UiText::get('analytics.delivered', 'Delivered'),
                $stats->capabilities->delivery
                    ? number_format($stats->delivered)
                    : UiText::get('analytics.not_available', 'N/A')
            )
                ->description(
                    $stats->capabilities->delivery
                        ? UiText::get(
                            'analytics.delivery_rate',
                            ':rate% of sent',
                            ['rate' => $stats->deliveryRate]
                        )
                        : UiText::get(
                            'analytics.delivery_unavailable',
                            'Delivery confirmation is unavailable for this transport.'
                        )
                )
                ->descriptionIcon('heroicon-o-check-circle'),

            Stat::make(
                UiText::get('analytics.opened', 'Opened'),
                number_format($stats->opened)
            )
                ->description(UiText::get(
                    'analytics.open_rate',
                    ':rate% open rate',
                    ['rate' => $stats->openRate]
                ))
                ->descriptionIcon('heroicon-o-envelope-open'),

            Stat::make(
                UiText::get('analytics.clicked', 'Clicked'),
                number_format($stats->clicked)
            )
                ->description(UiText::get(
                    'analytics.click_rate',
                    ':rate% click rate',
                    ['rate' => $stats->clickRate]
                ))
                ->descriptionIcon('heroicon-o-cursor-arrow-rays'),

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
