<?php

namespace Dth\Email\Filament\Widgets\Dashboard;

use Dth\Email\Filament\Widgets\Concerns\UsesEmailDashboardFilters;
use Dth\Email\Services\EmailAnalyticsService;
use Dth\Email\Support\UiText;
use Filament\Widgets\Widget;

class TopLinksChart extends Widget
{
    use UsesEmailDashboardFilters;

    protected static bool $isLazy = false;
    protected string $view = 'dth-email::filament.widgets.dashboard.top-links-table';
    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 4,
    ];

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return [
            'heading' => UiText::get('dashboard.charts.top_links', 'Liên kết được nhấp nhiều'),
            'description' => UiText::get(
                'dashboard.charts.top_links_description',
                'Các liên kết thu hút nhiều lượt nhấp nhất.'
            ),
            'rows' => app(EmailAnalyticsService::class)->topLinks($this->analyticsFilters(), 5),
        ];
    }
}
