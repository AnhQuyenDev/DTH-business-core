<?php

namespace App\Filament\Widgets;

use App\Models\Marketing\Campaign;
use App\Models\Marketing\CampaignRecipient;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardCampaignWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getStats(): array
    {
        $campaignCounts = Campaign::query()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $totalCampaigns = (int) $campaignCounts->sum();
        $draftCampaigns = (int) $campaignCounts->get('draft', 0);
        $sending = (int) $campaignCounts->get('sending', 0) + (int) $campaignCounts->get('scheduled', 0);
        $sent = (int) $campaignCounts->get('sent', 0);

        $recipientCounts = CampaignRecipient::query()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $totalRecipients = (int) $recipientCounts->sum();
        $sentCount = collect(['sent', 'delivered', 'opened', 'clicked'])->sum(fn (string $status): int => (int) $recipientCounts->get($status, 0));
        $opened = (int) $recipientCounts->get('opened', 0);
        $clicked = (int) $recipientCounts->get('clicked', 0);
        $bounced = (int) $recipientCounts->get('bounced', 0) + (int) $recipientCounts->get('failed', 0);
        $unsubscribed = (int) $recipientCounts->get('unsubscribed', 0);

        $openRate = $sentCount > 0 ? round(($opened / $sentCount) * 100, 1) : 0;
        $clickRate = $opened > 0 ? round(($clicked / $opened) * 100, 1) : 0;

        return [
            Stat::make(__('dashboard.campaign.total_campaigns'), number_format($totalCampaigns))
                ->description(__('dashboard.campaign.draft').': '.number_format($draftCampaigns).', '.__('dashboard.campaign.sending').': '.number_format($sending))
                ->icon('heroicon-o-envelope')
                ->color('primary'),
            Stat::make(__('dashboard.campaign.sent'), number_format($sent))
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make(__('dashboard.campaign.total_recipients'), number_format($totalRecipients))
                ->icon('heroicon-o-users')
                ->color('info'),
            Stat::make(__('dashboard.campaign.open_rate'), "{$openRate}%")
                ->description(__('dashboard.campaign.opened').': '.number_format($opened))
                ->icon('heroicon-o-eye')
                ->color('warning'),
            Stat::make(__('dashboard.campaign.click_rate'), "{$clickRate}%")
                ->description(__('dashboard.campaign.clicked').': '.number_format($clicked))
                ->icon('heroicon-o-cursor-arrow-rays')
                ->color('success'),
            Stat::make(__('dashboard.campaign.bounce_unsub'), number_format($bounced).' / '.number_format($unsubscribed))
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
