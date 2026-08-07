<?php

namespace App\Filament\Resources\MarketingCampaignResource\Pages;

use App\Filament\Resources\MarketingCampaignResource;
use App\Models\Marketing\LandingPage;
use App\Services\Marketing\MarketingCampaignServiceScopeService;
use Filament\Resources\Pages\EditRecord;

class EditMarketingCampaign extends EditRecord
{
    protected static string $resource = MarketingCampaignResource::class;

    /** @var array<int, int> */
    private array $serviceIds = [];

    /** @var array<int, int> */
    private array $landingPageIds = [];

    protected function mutateFormDataBeforeSave(array $data): array
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
            (int) $this->getRecord()->id,
        );

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord();

        // Đồng bộ scope trước, sau đó mới cập nhật các Landing Page thuộc Campaign.
        $record->services()->sync($this->serviceIds);

        $record->landingPages()
            ->whereNotIn('id', $this->landingPageIds ?: [0])
            ->update(['marketing_campaign_id' => null]);

        if ($this->landingPageIds !== []) {
            LandingPage::query()
                ->whereIn('id', $this->landingPageIds)
                ->update(['marketing_campaign_id' => $record->id]);
        }
    }
}
