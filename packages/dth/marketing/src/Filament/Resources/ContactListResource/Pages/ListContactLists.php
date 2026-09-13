<?php

namespace Dth\Marketing\Filament\Resources\ContactListResource\Pages;

use Dth\Marketing\Filament\Resources\ContactListResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListContactLists extends ListRecords
{
    protected static string $resource = ContactListResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
