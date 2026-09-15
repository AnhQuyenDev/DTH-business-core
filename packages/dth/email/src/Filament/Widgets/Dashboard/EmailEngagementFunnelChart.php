<?php

namespace Dth\Email\Filament\Widgets\Dashboard;

use Dth\Email\Filament\Widgets\Concerns\UsesEmailDashboardFilters;
use Dth\Email\Services\EmailAnalyticsService;
use Dth\Email\Support\UiText;
use Filament\Widgets\Widget;

class EmailEngagementFunnelChart extends Widget
{
    use UsesEmailDashboardFilters;

    protected static bool $isLazy = false;
    protected string $view = 'dth-email::filament.widgets.dashboard.email-engagement-funnel';
    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 4,
    ];

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $funnel = app(EmailAnalyticsService::class)->engagementFunnel($this->analyticsFilters());
        $base = max(1, $funnel->sent);

        return [
            'heading' => UiText::get('dashboard.charts.engagement_funnel', 'Engagement funnel'),
            'description' => UiText::get(
                'dashboard.charts.engagement_funnel_description',
                'Recipient journey from sending to engagement.'
            ),
            'steps' => [
                [
                    'label' => UiText::get('dashboard.metrics.sent', 'Sent'),
                    'value' => $funnel->sent,
                    'percent' => $funnel->sent > 0 ? 100.0 : 0.0,
                    'tone' => 'navy',
                ],
                [
                    'label' => UiText::get('dashboard.metrics.opened', 'Opened'),
                    'value' => $funnel->opened,
                    'percent' => ($funnel->opened / $base) * 100,
                    'tone' => 'blue',
                ],
                [
                    'label' => UiText::get('dashboard.metrics.clicked', 'Clicked'),
                    'value' => $funnel->clicked,
                    'percent' => ($funnel->clicked / $base) * 100,
                    'tone' => 'green',
                ],
                [
                    'label' => UiText::get('analytics.unsubscribed', 'Unsubscribed'),
                    'value' => $funnel->unsubscribed,
                    'percent' => ($funnel->unsubscribed / $base) * 100,
                    'tone' => 'mint',
                ],
            ],
        ];
    }
}
