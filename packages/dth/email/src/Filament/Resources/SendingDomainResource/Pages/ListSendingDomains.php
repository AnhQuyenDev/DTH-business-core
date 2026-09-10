<?php

namespace Dth\Email\Filament\Resources\SendingDomainResource\Pages;

use Dth\Email\Filament\Resources\SendingDomainResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSendingDomains extends ListRecords
{
    protected static string $resource = SendingDomainResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New')
                ->icon('heroicon-o-plus'),
        ];
    }
}
