<?php

namespace App\Filament\Resources\LandingPageResource\Pages;

use App\Filament\Resources\LandingPageResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateLandingPage extends CreateRecord
{
    protected static string $resource = LandingPageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->validateCampaignBeforePublish($data);
        $data['created_by'] = auth()->id();

        return $data;
    }

    private function validateCampaignBeforePublish(array $data): void
    {
        if (($data['status'] ?? null) !== 'published') {
            return;
        }

        if (empty($data['campaign_id']) && empty($data['marketing_campaign_id'])) {
            throw ValidationException::withMessages([
                'campaign_id' => __('notification.landing_page_requires_campaign'),
            ]);
        }
    }
}
