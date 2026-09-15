<?php

namespace Dth\Email\Filament\Widgets\Dashboard;

use Dth\Email\Filament\Resources\SendingAccountResource;
use Dth\Email\Filament\Widgets\Concerns\UsesEmailDashboardFilters;
use Dth\Email\Services\EmailAnalyticsService;
use Dth\Email\Support\UiText;
use Filament\Widgets\Widget;

class SendingAccountPerformanceChart extends Widget
{
    use UsesEmailDashboardFilters;

    protected static bool $isLazy = false;
    protected string $view = 'dth-email::filament.widgets.dashboard.sending-account-performance-table';
    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 4,
    ];

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return [
            'heading' => UiText::get('dashboard.charts.account_performance', 'Sending account performance'),
            'description' => UiText::get(
                'dashboard.charts.account_performance_description',
                'Open and click rates by sending account.'
            ),
            'rows' => array_slice(
                app(EmailAnalyticsService::class)->sendingAccountPerformance($this->analyticsFilters()),
                0,
                5,
            ),
            'indexUrl' => SendingAccountResource::getUrl('index'),
        ];
    }
}
