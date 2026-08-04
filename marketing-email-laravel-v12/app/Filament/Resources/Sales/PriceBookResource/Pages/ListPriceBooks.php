<?php

namespace App\Filament\Resources\Sales\PriceBookResource\Pages;

use App\Filament\Resources\Sales\PriceBookResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPriceBooks extends ListRecords
{
    protected static string $resource = PriceBookResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
