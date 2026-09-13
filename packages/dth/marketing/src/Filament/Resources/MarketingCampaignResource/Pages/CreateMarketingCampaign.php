<?php

namespace Dth\Marketing\Filament\Resources\MarketingCampaignResource\Pages;

use Dth\Marketing\Enums\MarketingCampaignStatus;
use Dth\Marketing\Filament\Resources\MarketingCampaignResource;
use Dth\Marketing\Support\UiText;
use Filament\Resources\Pages\CreateRecord;

class CreateMarketingCampaign extends CreateRecord
{
    protected static string $resource = MarketingCampaignResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = MarketingCampaignStatus::Draft->value;
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label(UiText::get('common.actions.save', 'Save'))
                ->icon('heroicon-o-check'),
            $this->getCancelFormAction()
                ->label(UiText::get('common.actions.cancel', 'Cancel'))
                ->icon('heroicon-o-x-mark')
                ->color('gray'),
        ];
    }
}
