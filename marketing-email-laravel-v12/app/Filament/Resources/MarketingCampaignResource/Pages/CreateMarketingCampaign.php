<?php

namespace App\Filament\Resources\MarketingCampaignResource\Pages;

use App\Filament\Resources\MarketingCampaignResource;
use App\Models\Marketing\LandingPage;
use App\Services\Marketing\MarketingCampaignServiceScopeService;
use Filament\Resources\Pages\CreateRecord;

class CreateMarketingCampaign extends CreateRecord
{
    protected static string $resource = MarketingCampaignResource::class;

    /** @var array<int, int> */
    private array $serviceIds = [];

    /** @var array<int, int> */
    private array $landingPageIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $scope = app(MarketingCampaignServiceScopeService::class);

        $this->serviceIds = $scope->normalizeIds(
            (array) ($this->data['service_ids'] ?? [])
        );
        $this->landingPageIds = $scope->normalizeIds(
            (array) ($this->data['landing_page_ids'] ?? [])
        );

        $scope->assertCampaignConfiguration(
            $this->serviceIds,
            $this->landingPageIds,
        );

        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        $record->services()->sync($this->serviceIds);

        if ($this->landingPageIds !== []) {
            LandingPage::query()
                ->whereIn('id', $this->landingPageIds)
                ->update(['marketing_campaign_id' => $record->id]);
        }
    }
}
