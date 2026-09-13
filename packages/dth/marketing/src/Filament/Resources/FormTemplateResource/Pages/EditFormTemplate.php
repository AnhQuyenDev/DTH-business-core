<?php

namespace Dth\Marketing\Filament\Resources\FormTemplateResource\Pages;

use Dth\Marketing\Filament\Resources\FormTemplateResource;
use Dth\Marketing\Models\FormTemplate;
use Dth\Marketing\Services\FormTemplateLifecycleService;
use Dth\Marketing\Support\UiText;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFormTemplate extends EditRecord
{
    protected static string $resource = FormTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label(UiText::get('common.actions.delete', 'Delete'))
                ->icon('heroicon-o-trash'),
        ];
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
            $this->getSaveFormAction()->label(UiText::get('common.actions.save', 'Save'))->icon('heroicon-o-check'),
            $this->getCancelFormAction()->label(UiText::get('common.actions.cancel', 'Cancel'))->icon('heroicon-o-x-mark')->color('gray'),
        ];
    }
}
