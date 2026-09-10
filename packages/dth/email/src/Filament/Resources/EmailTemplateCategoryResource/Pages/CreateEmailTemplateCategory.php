<?php

namespace Dth\Email\Filament\Resources\EmailTemplateCategoryResource\Pages;

use Dth\Email\Filament\Resources\EmailTemplateCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmailTemplateCategory extends CreateRecord
{
    protected static string $resource = EmailTemplateCategoryResource::class;

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label('Save')
                ->icon('heroicon-o-check'),
            $this->getCancelFormAction()
                ->label('Cancel')
                ->icon('heroicon-o-x-mark')
                ->color('gray'),
        ];
    }
}
