<?php

namespace Dth\Marketing\Filament\Resources\MarketingCampaignResource\Pages;

use Dth\Marketing\Filament\Resources\MarketingCampaignResource;
use Dth\Marketing\Filament\Support\MarketingPageUi;
use Dth\Marketing\Support\UiText;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditMarketingCampaign extends EditRecord
{
    protected static string $resource = MarketingCampaignResource::class;

    public function getTitle(): string|Htmlable
    {
        return MarketingPageUi::title(UiText::get('pages.campaign_edit.title', 'Edit Marketing Campaign'), 'campaign');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.campaign_edit.subheading', 'Update campaign scope, budget, schedule, and operational information without changing its lifecycle rules.');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->label(UiText::get('common.actions.save', 'Save'))->icon('heroicon-o-check'),
            $this->getCancelFormAction()->label(UiText::get('common.actions.cancel', 'Cancel'))->icon('heroicon-o-x-mark')->color('gray'),
        ];
    }
}
