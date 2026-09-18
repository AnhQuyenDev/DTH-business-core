<?php

namespace Dth\Marketing\Filament\Resources\LandingPageResource\Pages;

use Dth\Marketing\Enums\LandingPageStatus;
use Dth\Marketing\Filament\Resources\LandingPageResource;
use Dth\Marketing\Filament\Support\MarketingPageUi;
use Dth\Marketing\Models\LandingPage;
use Dth\Marketing\Support\UiText;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateLandingPage extends CreateRecord
{
    protected static string $resource = LandingPageResource::class;

    public function getTitle(): string|Htmlable
    {
        return MarketingPageUi::title(UiText::get('pages.landing_create.title', 'Create Landing Page'), 'landing', 'blue');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.landing_create.subheading', 'Configure content, form experience, campaign linkage, theme, and tracking before publishing.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = LandingPageStatus::Draft->value;
        $data['created_by'] = auth()->id();
        $data['theme_tokens'] = array_replace(LandingPage::defaultTheme(), (array) ($data['theme_tokens'] ?? []));
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
