<?php

namespace Dth\Marketing\Filament\Resources\FormTemplateResource\Pages;

use Dth\Marketing\Filament\Resources\FormTemplateResource;
use Dth\Marketing\Filament\Support\MarketingPageUi;
use Dth\Marketing\Models\FormTemplate;
use Dth\Marketing\Services\FormTemplateLifecycleService;
use Dth\Marketing\Support\UiText;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditFormTemplate extends EditRecord
{
    protected static string $resource = FormTemplateResource::class;

    public function getTitle(): string|Htmlable
    {
        return MarketingPageUi::title(UiText::get('pages.form_edit.title', 'Edit Form Template'), 'form', 'amber');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.form_edit.subheading', 'Update form fields and presentation while preserving the template version lifecycle.');
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->label(UiText::get('common.actions.delete', 'Delete'))->icon('heroicon-o-trash')];
    }

    protected function afterSave(): void
    {
        if ($this->record instanceof FormTemplate) {
            app(FormTemplateLifecycleService::class)->bumpVersion($this->record);
        }
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->label(UiText::get('common.actions.save', 'Save'))->icon('heroicon-o-check-circle')
                ->color('primary')
                ->extraAttributes(['class' => 'dth-mkt-form-action dth-mkt-form-action--primary']),
            $this->getCancelFormAction()->label(UiText::get('common.actions.cancel', 'Cancel'))->icon('heroicon-o-x-mark')->color('gray')
                ->extraAttributes(['class' => 'dth-mkt-form-action dth-mkt-form-action--secondary']),
        ];
    }
}
