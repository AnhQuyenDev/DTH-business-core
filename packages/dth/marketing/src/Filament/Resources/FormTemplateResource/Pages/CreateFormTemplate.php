<?php

namespace Dth\Marketing\Filament\Resources\FormTemplateResource\Pages;

use Dth\Marketing\Enums\FormTemplateStatus;
use Dth\Marketing\Filament\Resources\FormTemplateResource;
use Dth\Marketing\Filament\Support\MarketingPageUi;
use Dth\Marketing\Support\UiText;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateFormTemplate extends CreateRecord
{
    protected static string $resource = FormTemplateResource::class;

    public function getTitle(): string|Htmlable
    {
        return MarketingPageUi::title(UiText::get('pages.form_create.title', 'Create Form Template'), 'form', 'amber');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.form_create.subheading', 'Define fields, audience type, consent behavior, and reusable form presentation.');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = FormTemplateStatus::Draft->value;
        $data['version'] = 1;
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
