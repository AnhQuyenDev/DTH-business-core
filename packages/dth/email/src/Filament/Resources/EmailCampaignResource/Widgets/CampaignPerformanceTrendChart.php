<?php

namespace Dth\Email\Filament\Resources\EmailCampaignResource\Widgets;

use Dth\Email\Enums\AnalyticsGranularity;
use Dth\Email\Models\EmailCampaign;
use Dth\Email\Services\EmailReportDataService;
use Dth\Email\Support\UiText;
use Filament\Widgets\ChartWidget;

class CampaignPerformanceTrendChart extends ChartWidget
{
    public int $campaignId;

    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;
    protected ?string $maxHeight = '360px';
    protected bool $isCollapsible = true;
    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 8,
    ];

    public function getHeading(): string
    {
        return UiText::get('reports.performance_trend', 'Performance trend');
    }

    protected function getData(): array
    {
        $campaign = EmailCampaign::query()->findOrFail($this->campaignId);
        $report = app(EmailReportDataService::class)->campaignTrend($campaign);
        $granularity = $report['trendGranularity'];
        $trend = $report['trend'];

        return [
            'datasets' => [
                [
                    'label' => UiText::get('dashboard.metrics.sent', 'Sent'),
                    'data' => array_map(static fn ($point): int => $point->sent, $trend),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.12)',
                    'tension' => 0.3,
                    'fill' => true,
                ],
                [
                    'label' => UiText::get('dashboard.metrics.unique_opens', 'Unique opens'),
                    'data' => array_map(static fn ($point): int => $point->uniqueOpened, $trend),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.08)',
                    'tension' => 0.3,
                ],
                [
                    'label' => UiText::get('dashboard.metrics.unique_clicks', 'Unique clicks'),
                    'data' => array_map(static fn ($point): int => $point->uniqueClicked, $trend),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.08)',
                    'tension' => 0.3,
                ],
            ],
            'labels' => array_map(
                static fn ($point): string => $granularity === AnalyticsGranularity::Hour
                    ? $point->bucket->format('d/m H:i')
                    : $point->bucket->format('d/m'),
                $trend,
            ),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
