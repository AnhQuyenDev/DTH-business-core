<?php

namespace App\Filament\Resources\CustomerDistributionBatchResource\Pages;

use App\Filament\Resources\CustomerDistributionBatchResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerDistributionBatches extends ListRecords
{
    protected static string $resource = CustomerDistributionBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
