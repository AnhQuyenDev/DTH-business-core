<?php

namespace Dth\Email\Filament\Widgets\Dashboard;

use Dth\Email\Filament\Widgets\Concerns\UsesEmailDashboardFilters;
use Dth\Email\Services\EmailInsightService;
use Filament\Widgets\Widget;

class EmailInsightsWidget extends Widget
{
    use UsesEmailDashboardFilters;

    protected static bool $isLazy = false;
    protected string $view = 'dth-email::filament.widgets.email-insights';
    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 6,
    ];

    protected function getViewData(): array
    {
        return [
            'report' => app(EmailInsightService::class)->dashboard($this->analyticsFilters()),
            'compact' => true,
        ];
    }
}
