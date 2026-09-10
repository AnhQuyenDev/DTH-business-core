<?php

namespace Dth\Email\Filament\Widgets\Dashboard;

use Dth\Email\Filament\Widgets\Concerns\UsesEmailDashboardFilters;
use Dth\Email\Services\EmailAnalyticsService;
use Dth\Email\Support\UiText;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Str;

class TopCampaignsChart extends ChartWidget
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
        return UiText::get('dashboard.charts.top_campaigns', 'Top campaigns');
    }

    public function getDescription(): ?string
    {
        return UiText::get(
            'dashboard.charts.top_campaigns_description',
            'Campaigns ranked by click rate, with open rate for context.'
        );
    }

    protected function getData(): array
    {
        $rows = app(EmailAnalyticsService::class)->topCampaigns($this->analyticsFilters(), 7);

        return [
            'datasets' => [
                [
                    'label' => UiText::get('dashboard.metrics.open_rate', 'Open rate'),
                    'data' => array_map(static fn ($row): float => $row->openRate, $rows),
                    'backgroundColor' => '#3b82f6',
                    'borderRadius' => 5,
                ],
                [
                    'label' => UiText::get('dashboard.metrics.click_rate', 'Click rate'),
                    'data' => array_map(static fn ($row): float => $row->clickRate, $rows),
                    'backgroundColor' => '#10b981',
                    'borderRadius' => 5,
                ],
            ],
            'labels' => array_map(
                static fn ($row): string => Str::limit($row->name, 32),
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
                'x' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'title' => [
                        'display' => true,
                        'text' => UiText::get('dashboard.charts.rate_percent', 'Rate (%)'),
                    ],
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
