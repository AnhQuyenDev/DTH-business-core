<?php

namespace Dth\Email\Filament\Resources\EmailCampaignResource\Widgets;

use Dth\Email\Services\CampaignAnalyticsService;
use Dth\Email\Support\UiText;
use Filament\Widgets\ChartWidget;

class CampaignEngagementFunnelChart extends ChartWidget
{
    public int $campaignId;

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
        return UiText::get('reports.engagement_funnel', 'Engagement funnel');
    }

    protected function getData(): array
    {
        $stats = app(CampaignAnalyticsService::class)->forCampaignId($this->campaignId);

        return [
            'datasets' => [
                [
                    'label' => UiText::get('dashboard.charts.audience', 'Audience'),
                    'data' => [
                        $stats->total,
                        $stats->sent,
                        $stats->opened,
                        $stats->clicked,
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
