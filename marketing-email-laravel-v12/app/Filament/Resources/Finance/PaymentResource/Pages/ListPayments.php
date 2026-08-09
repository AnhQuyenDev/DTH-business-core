<?php

namespace App\Filament\Resources\Finance\PaymentResource\Pages;

use App\Filament\Resources\Finance\PaymentResource;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;
}
