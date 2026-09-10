<?php

namespace Dth\Email\Filament\Resources\SendingDomainResource\Pages;

use Dth\Email\Filament\Resources\SendingDomainResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSendingDomain extends CreateRecord
{
    protected static string $resource = SendingDomainResource::class;

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
