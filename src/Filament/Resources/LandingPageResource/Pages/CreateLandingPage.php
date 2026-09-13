<?php

namespace Dth\Marketing\Filament\Resources\LandingPageResource\Pages;

use Dth\Marketing\Enums\LandingPageStatus;
use Dth\Marketing\Filament\Resources\LandingPageResource;
use Dth\Marketing\Models\LandingPage;
use Dth\Marketing\Support\UiText;
use Filament\Resources\Pages\CreateRecord;

class CreateLandingPage extends CreateRecord
{
    protected static string $resource = LandingPageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = LandingPageStatus::Draft->value;
        $data['created_by'] = auth()->id();
        $data['theme_tokens'] = array_replace(
            LandingPage::defaultTheme(),
            (array) ($data['theme_tokens'] ?? []),
        );

        return $data;
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label(UiText::get('common.actions.save', 'Save'))->icon('heroicon-o-check'),
            $this->getCancelFormAction()->label(UiText::get('common.actions.cancel', 'Cancel'))->icon('heroicon-o-x-mark')->color('gray'),
        ];
    }
}
