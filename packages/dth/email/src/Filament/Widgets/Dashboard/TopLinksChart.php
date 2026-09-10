<?php

namespace Dth\Email\Filament\Widgets\Dashboard;

use Dth\Email\Filament\Widgets\Concerns\UsesEmailDashboardFilters;
use Dth\Email\Services\EmailAnalyticsService;
use Dth\Email\Support\UiText;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Str;

class TopLinksChart extends ChartWidget
{
    use UsesEmailDashboardFilters;

    protected static bool $isLazy = false;
    protected ?string $pollingInterval = null;
    protected ?string $maxHeight = '340px';
    protected bool $isCollapsible = true;
    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];

    public function getHeading(): string
    {
        return UiText::get('dashboard.charts.top_links', 'Top clicked links');
    }

    public function getDescription(): ?string
    {
        return UiText::get(
            'dashboard.charts.top_links_description',
            'Most engaging tracked destinations across the selected period.'
        );
    }

    protected function getData(): array
    {
        $rows = app(EmailAnalyticsService::class)->topLinks($this->analyticsFilters(), 7);

        return [
            'datasets' => [
                [
                    'label' => UiText::get('dashboard.metrics.total_clicks', 'Total clicks'),
                    'data' => array_map(static fn ($row): int => $row->totalClicks, $rows),
                    'backgroundColor' => '#f59e0b',
                    'borderRadius' => 5,
                ],
                [
                    'label' => UiText::get('dashboard.metrics.unique_clickers', 'Unique clickers'),
                    'data' => array_map(static fn ($row): int => $row->uniqueClickers, $rows),
                    'backgroundColor' => '#6366f1',
                    'borderRadius' => 5,
                ],
            ],
            'labels' => array_map(
                static fn ($row): string => Str::limit($row->url, 42),
                $rows,
            ),
        ];
    }

    public function isEmpty(): bool
    {
        return empty($this->getCachedData()['labels'] ?? []);
    }

    public function getEmptyStateHeading(): string
    {
        return UiText::get('dashboard.empty.heading', 'No data for this period');
    }

    public function getEmptyStateDescription(): ?string
    {
        return UiText::get(
            'dashboard.empty.description',
            'Try another date range or remove one of the dashboard filters.'
        );
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => ['position' => 'bottom'],
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
