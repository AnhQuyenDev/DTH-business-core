<?php

namespace Dth\Email\Filament\Widgets\Dashboard;

use Dth\Email\Enums\AnalyticsGranularity;
use Dth\Email\Filament\Widgets\Concerns\UsesEmailDashboardFilters;
use Dth\Email\Services\EmailAnalyticsService;
use Dth\Email\Support\UiText;
use Filament\Widgets\ChartWidget;

class EmailPerformanceTrendChart extends ChartWidget
{
    use UsesEmailDashboardFilters;

    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;
    protected ?string $maxHeight = '228px';
    protected bool $isCollapsible = false;
    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 8,
    ];

    public ?string $filter = 'day';

    public function getHeading(): string
    {
        return UiText::get('dashboard.charts.performance_trend', 'Email performance over time');
    }

    public function getDescription(): ?string
    {
        return UiText::get(
                'dashboard.charts.performance_trend_description',
            'Sent emails, opens, and clicks by day.'
        );
    }

    /** @return array<string, string>|null */
    protected function getFilters(): ?array
    {
        return [
            'day' => UiText::get('common.fields.time', 'Time'),
        ];
    }

    protected function getData(): array
    {
        $filters = $this->analyticsFilters();
        $granularity = $filters->range->days() <= 2
            ? AnalyticsGranularity::Hour
            : AnalyticsGranularity::Day;
        $trend = app(EmailAnalyticsService::class)->trend($filters, $granularity);

        return [
            'datasets' => [
                [
                    'label' => UiText::get('dashboard.metrics.sent', 'Sent'),
                    'data' => array_map(static fn ($point): int => $point->sent, $trend),
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => '#f59e0b',
                    'pointBackgroundColor' => '#f59e0b',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 1.2,
                    'pointRadius' => 3,
                    'pointHoverRadius' => 4,
                    'borderWidth' => 2,
                    'tension' => 0.22,
                    'fill' => false,
                ],
                [
                    'label' => UiText::get('dashboard.metrics.unique_opens', 'Opens'),
                    'data' => array_map(static fn ($point): int => $point->uniqueOpened, $trend),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => '#3b82f6',
                    'pointBackgroundColor' => '#3b82f6',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 1.2,
                    'pointRadius' => 3,
                    'pointHoverRadius' => 4,
                    'borderWidth' => 2,
                    'tension' => 0.22,
                    'fill' => false,
                ],
                [
                    'label' => UiText::get('dashboard.metrics.unique_clicks', 'Clicks'),
                    'data' => array_map(static fn ($point): int => $point->uniqueClicked, $trend),
                    'borderColor' => '#10b981',
                    'backgroundColor' => '#10b981',
                    'pointBackgroundColor' => '#10b981',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 1.2,
                    'pointRadius' => 3,
                    'pointHoverRadius' => 4,
                    'borderWidth' => 2,
                    'tension' => 0.22,
                    'fill' => false,
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
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'layout' => [
                'padding' => [
                    'top' => 0,
                    'right' => 4,
                    'bottom' => 0,
                    'left' => 0,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                    'align' => 'end',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'rectRounded',
                        'boxWidth' => 8,
                        'boxHeight' => 8,
                        'padding' => 16,
                        'color' => '#667085',
                        'font' => [
                            'size' => 10,
                            'weight' => 500,
                        ],
                    ],
                ],
                'tooltip' => [
                    'backgroundColor' => '#111827',
                    'titleColor' => '#ffffff',
                    'bodyColor' => '#e5e7eb',
                    'padding' => 10,
                    'cornerRadius' => 8,
                    'displayColors' => true,
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => true,
                        'color' => 'rgba(148, 163, 184, 0.12)',
                        'drawBorder' => false,
                    ],
                    'border' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'autoSkip' => true,
                        'maxTicksLimit' => 11,
                        'maxRotation' => 0,
                        'minRotation' => 0,
                        'color' => '#7b879b',
                        'font' => [
                            'size' => 10,
                        ],
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grace' => '8%',
                    'grid' => [
                        'color' => 'rgba(148, 163, 184, 0.18)',
                        'drawBorder' => false,
                    ],
                    'border' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'precision' => 0,
                        'color' => '#7b879b',
                        'font' => [
                            'size' => 10,
                        ],
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
