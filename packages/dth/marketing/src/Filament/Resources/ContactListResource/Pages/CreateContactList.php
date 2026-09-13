<?php

namespace Dth\Marketing\Filament\Resources\ContactListResource\Pages;

use Dth\Marketing\Filament\Resources\ContactListResource;
use Filament\Resources\Pages\CreateRecord;

class CreateContactList extends CreateRecord
{
    protected static string $resource = ContactListResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
