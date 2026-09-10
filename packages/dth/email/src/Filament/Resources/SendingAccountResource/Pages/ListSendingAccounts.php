<?php

namespace Dth\Email\Filament\Resources\SendingAccountResource\Pages;

use Dth\Email\Filament\Resources\SendingAccountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSendingAccounts extends ListRecords
{
    protected static string $resource = SendingAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New')
                ->icon('heroicon-o-plus'),
        ];
    }
}
