<?php

namespace Dth\Marketing\Filament\Resources\MarketingCampaignResource\Pages;

use Dth\Marketing\Enums\MarketingCampaignStatus;
use Dth\Marketing\Filament\Resources\MarketingCampaignResource;
use Dth\Marketing\Filament\Support\MarketingPageUi;
use Dth\Marketing\Support\UiText;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateMarketingCampaign extends CreateRecord
{
    protected static string $resource = MarketingCampaignResource::class;

    public function getTitle(): string|Htmlable
    {
        return MarketingPageUi::title(UiText::get('pages.campaign_create.title', 'Create Marketing Campaign'), 'campaign');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.campaign_create.subheading', 'Define campaign scope, promoted services, schedule, budget, and operational notes.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = MarketingCampaignStatus::Draft->value;
        $data['created_by'] = auth()->id();
        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label(UiText::get('common.actions.save', 'Save'))->icon('heroicon-o-check-circle')
                ->color('primary')
                ->extraAttributes(['class' => 'dth-mkt-form-action dth-mkt-form-action--primary']),
            $this->getCancelFormAction()->label(UiText::get('common.actions.cancel', 'Cancel'))->icon('heroicon-o-x-mark')->color('gray')
                ->extraAttributes(['class' => 'dth-mkt-form-action dth-mkt-form-action--secondary']),
        ];
    }
}
