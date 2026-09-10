<?php

namespace Dth\Email\Filament\Resources\EmailCampaignResource\Widgets;

use Dth\Email\Services\EmailAnalyticsService;
use Filament\Widgets\Widget;

class CampaignTopLinksTableWidget extends Widget
{
    public int $campaignId;

    protected static bool $isLazy = false;
    protected string $view = 'dth-email::filament.widgets.campaign-top-links-table';
    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'links' => app(EmailAnalyticsService::class)->campaignTopLinks($this->campaignId, 10),
        ];
    }
}
