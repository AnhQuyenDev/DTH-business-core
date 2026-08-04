<?php

namespace App\Filament\Resources\MarketingCampaignResource\Pages;

use App\Filament\Resources\MarketingCampaignResource;
use Filament\Resources\Pages\EditRecord;

class EditMarketingCampaign extends EditRecord
{
    protected static string $resource = MarketingCampaignResource::class;

    protected function afterSave(): void
    {
        $record = $this->getRecord();
        $record->landingPages()->update(['marketing_campaign_id' => null]);
        if (!empty($this->data['landing_page_ids'])) {
            \App\Models\Marketing\LandingPage::whereIn('id', $this->data['landing_page_ids'])
                ->update(['marketing_campaign_id' => $record->id]);
        }
    }
}
