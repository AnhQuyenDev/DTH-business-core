<?php

namespace App\Filament\Resources\SendingAccountResource\Pages;

use App\Filament\Resources\SendingAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSendingAccounts extends ListRecords
{
    protected static string $resource = SendingAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}