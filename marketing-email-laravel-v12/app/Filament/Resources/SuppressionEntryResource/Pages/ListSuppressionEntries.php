<?php

namespace App\Filament\Resources\SuppressionEntryResource\Pages;

use App\Filament\Resources\SuppressionEntryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSuppressionEntries extends ListRecords
{
    protected static string $resource = SuppressionEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
