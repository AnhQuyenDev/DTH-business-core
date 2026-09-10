<?php

namespace Dth\Email\Filament\Resources\EmailTemplateResource\Pages;

use Dth\Email\Filament\Resources\EmailTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmailTemplate extends CreateRecord
{
    protected static string $resource = EmailTemplateResource::class;

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
