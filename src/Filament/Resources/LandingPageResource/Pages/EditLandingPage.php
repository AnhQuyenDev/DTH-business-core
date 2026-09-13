<?php

namespace Dth\Marketing\Filament\Resources\LandingPageResource\Pages;

use Dth\Marketing\Filament\Resources\LandingPageResource;
use Dth\Marketing\Support\UiText;
use Filament\Resources\Pages\EditRecord;

class EditLandingPage extends EditRecord
{
    protected static string $resource = LandingPageResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->label(UiText::get('common.actions.save', 'Save'))->icon('heroicon-o-check'),
            $this->getCancelFormAction()->label(UiText::get('common.actions.cancel', 'Cancel'))->icon('heroicon-o-x-mark')->color('gray'),
        ];
    }
}
