<?php

namespace Dth\Email\Filament\Resources\EmailCampaignResource\Widgets;

use Dth\Email\Services\EmailInsightService;
use Filament\Widgets\Widget;

class CampaignInsightsWidget extends Widget
{
    public int $campaignId;

    protected static bool $isLazy = false;
    protected string $view = 'dth-email::filament.widgets.email-insights';
    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'report' => app(EmailInsightService::class)->campaign($this->campaignId),
        ];
    }
}
