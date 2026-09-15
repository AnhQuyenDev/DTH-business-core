<?php

namespace Dth\Marketing\Filament\Resources\LandingPageResource\Pages;

use Dth\Marketing\Filament\Resources\LandingPageResource;
use Dth\Marketing\Filament\Support\MarketingPageUi;
use Dth\Marketing\Support\UiText;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditLandingPage extends EditRecord
{
    protected static string $resource = LandingPageResource::class;

    public function getTitle(): string|Htmlable
    {
        return MarketingPageUi::title(UiText::get('pages.landing_edit.title', 'Edit Landing Page'), 'landing', 'blue');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.landing_edit.subheading', 'Refine landing page content, form configuration, campaign linkage, and presentation.');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->label(UiText::get('common.actions.save', 'Save'))->icon('heroicon-o-check'),
            $this->getCancelFormAction()->label(UiText::get('common.actions.cancel', 'Cancel'))->icon('heroicon-o-x-mark')->color('gray'),
        ];
    }
}
