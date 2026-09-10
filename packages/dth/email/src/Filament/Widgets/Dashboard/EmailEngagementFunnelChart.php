<?php

namespace Dth\Email\Filament\Widgets\Dashboard;

use Dth\Email\Filament\Widgets\Concerns\UsesEmailDashboardFilters;
use Dth\Email\Services\EmailAnalyticsService;
use Dth\Email\Support\UiText;
use Filament\Widgets\ChartWidget;

class EmailEngagementFunnelChart extends ChartWidget
{
    use UsesEmailDashboardFilters;

    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;
    protected ?string $maxHeight = '360px';
    protected bool $isCollapsible = true;
    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 4,
    ];

    public function getHeading(): string
    {
        return UiText::get('dashboard.charts.engagement_funnel', 'Engagement funnel');
    }

    public function getDescription(): ?string
    {
        return UiText::get(
            'dashboard.charts.engagement_funnel_description',
            'Recipients progressing from send to meaningful engagement.'
        );
    }

    protected function getData(): array
    {
        $funnel = app(EmailAnalyticsService::class)->engagementFunnel($this->analyticsFilters());

        return [
            'datasets' => [
                [
                    'label' => UiText::get('dashboard.charts.audience', 'Audience'),
                    'data' => [
                        $funnel->recipients,
                        $funnel->sent,
                        $funnel->opened,
                        $funnel->clicked,
                    ],
                    'backgroundColor' => [
                        '#64748b',
                        '#f59e0b',
                        '#3b82f6',
                        '#10b981',
                    ],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => [
                UiText::get('dashboard.metrics.recipients', 'Recipients'),
                UiText::get('dashboard.metrics.sent', 'Sent'),
                UiText::get('dashboard.metrics.opened', 'Opened'),
                UiText::get('dashboard.metrics.clicked', 'Clicked'),
            ],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'x' => ['beginAtZero' => true],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
