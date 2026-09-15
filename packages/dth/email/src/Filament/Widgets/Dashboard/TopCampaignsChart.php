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
            'heading' => UiText::get('dashboard.charts.top_campaigns', 'Chiến dịch nổi bật'),
            'description' => UiText::get(
                'dashboard.charts.top_campaigns_description',
                'Top chiến dịch theo tỷ lệ nhấp, kèm tỷ lệ mở.'
            ),
            'rows' => app(EmailAnalyticsService::class)->topCampaigns($this->analyticsFilters(), 5),
            'indexUrl' => EmailCampaignResource::getUrl('index'),
        ];
    }
}
