<?php

namespace App\Filament\Resources\LandingPageResource\Pages;

use App\Filament\Resources\LandingPageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditLandingPage extends EditRecord
{
    protected static string $resource = LandingPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->validateCampaignBeforePublish($data);

        return $data;
    }

    public function getRelationManagers(): array
    {
        return [];
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
