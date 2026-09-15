<?php

namespace Dth\Email\Filament\Widgets\Dashboard;

use Dth\Email\Filament\Resources\EmailCampaignResource;
use Dth\Email\Filament\Widgets\Concerns\UsesEmailDashboardFilters;
use Dth\Email\Services\EmailAnalyticsService;
use Dth\Email\Support\UiText;
use Filament\Widgets\Widget;

class TopCampaignsChart extends Widget
{
    use UsesEmailDashboardFilters;

    protected static bool $isLazy = false;
    protected string $view = 'dth-email::filament.widgets.dashboard.top-campaigns-table';
    protected int|string|array $columnSpan = [
        'md' => 6,
        'xl' => 4,
    ];

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return [
            'heading' => UiText::get('dashboard.charts.top_campaigns', 'Top Campaigns'),
            'description' => UiText::get(
                'dashboard.charts.top_campaigns_description',
                'Top campaigns by click rate with open rate for comparison.'
            ),
            'rows' => app(EmailAnalyticsService::class)->topCampaigns($this->analyticsFilters(), 5),
            'indexUrl' => EmailCampaignResource::getUrl('index'),
        ];
    }
}
