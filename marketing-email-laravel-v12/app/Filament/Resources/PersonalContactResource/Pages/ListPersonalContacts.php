<?php

namespace App\Filament\Resources\PersonalContactResource\Pages;

use App\Filament\Resources\PersonalContactResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPersonalContacts extends ListRecords
{
    protected static string $resource = PersonalContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
