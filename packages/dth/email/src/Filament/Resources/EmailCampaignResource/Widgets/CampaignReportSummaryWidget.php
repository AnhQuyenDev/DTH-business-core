<?php

namespace Dth\Email\Filament\Resources\EmailCampaignResource\Widgets;

use Dth\Email\Filament\Support\StatusColor;
use Dth\Email\Models\EmailCampaign;
use Dth\Email\Support\UiText;
use Filament\Widgets\Widget;

class CampaignReportSummaryWidget extends Widget
{
    public int $campaignId;

    protected static bool $isLazy = false;
    protected string $view = 'dth-email::filament.widgets.campaign-report-summary';
    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $campaign = EmailCampaign::query()
            ->with(['sendingAccount', 'template'])
            ->findOrFail($this->campaignId);

        return [
            'campaign' => $campaign,
            'statusLabel' => UiText::status($campaign->status),
            'statusColor' => StatusColor::for($campaign->status),
        ];
    }
}
