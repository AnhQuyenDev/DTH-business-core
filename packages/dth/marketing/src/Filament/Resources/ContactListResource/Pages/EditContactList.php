<?php

namespace Dth\Marketing\Filament\Resources\ContactListResource\Pages;

use Dth\Marketing\Filament\Resources\ContactListResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditContactList extends EditRecord
{
    protected static string $resource = ContactListResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
