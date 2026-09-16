<?php

namespace Dth\Marketing\Filament\Resources\MarketingCampaignResource\Pages;

use Dth\Marketing\Filament\Resources\MarketingCampaignResource;
use Dth\Marketing\Filament\Support\MarketingPageUi;
use Dth\Marketing\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListMarketingCampaigns extends ListRecords
{
    protected static string $resource = MarketingCampaignResource::class;

    public function getTitle(): string|Htmlable
    {
        return MarketingPageUi::title(UiText::get('pages.campaigns.title', 'Marketing Campaigns'), 'campaign');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.campaigns.subheading', 'Plan, monitor, and optimize acquisition campaigns across channels and services.');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(UiText::get('pages.campaigns.create', 'Create campaign'))
                ->icon('heroicon-o-plus')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-mkt-entry-action dth-mkt-entry-action--indigo']),
        ];
    }
}
