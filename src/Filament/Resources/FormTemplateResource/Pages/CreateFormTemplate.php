<?php

namespace Dth\Marketing\Filament\Resources\FormTemplateResource\Pages;

use Dth\Marketing\Enums\FormTemplateStatus;
use Dth\Marketing\Filament\Resources\FormTemplateResource;
use Dth\Marketing\Support\UiText;
use Filament\Resources\Pages\CreateRecord;

class CreateFormTemplate extends CreateRecord
{
    protected static string $resource = FormTemplateResource::class;

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
            $this->getCreateFormAction()->label(UiText::get('common.actions.save', 'Save'))->icon('heroicon-o-check'),
            $this->getCancelFormAction()->label(UiText::get('common.actions.cancel', 'Cancel'))->icon('heroicon-o-x-mark')->color('gray'),
        ];
    }
}
