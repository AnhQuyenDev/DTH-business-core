<?php

namespace Dth\Marketing\Filament\Resources\MarketingCampaignResource\Pages;

use Dth\Marketing\Filament\Resources\MarketingCampaignResource;
use Dth\Marketing\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMarketingCampaigns extends ListRecords
{
    protected static string $resource = MarketingCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(UiText::get('common.actions.new', 'New'))
                ->icon('heroicon-o-plus'),
        ];
    }
}
