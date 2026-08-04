<?php

namespace App\Filament\Resources\MarketingCampaignResource\Pages;

use App\Filament\Resources\MarketingCampaignResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMarketingCampaign extends CreateRecord
{
    protected static string $resource = MarketingCampaignResource::class;

    protected function afterCreate(): void
    {
        $record = $this->getRecord();
        if (!empty($this->data['landing_page_ids'])) {
            \App\Models\Marketing\LandingPage::whereIn('id', $this->data['landing_page_ids'])
                ->update(['marketing_campaign_id' => $record->id]);
        }
    }
}
