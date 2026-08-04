<?php

namespace App\Filament\Resources\BusinessContactResource\Pages;

use App\Filament\Resources\BusinessContactResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBusinessContacts extends ListRecords
{
    protected static string $resource = BusinessContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
