<?php
namespace Dth\Crm\Filament\Resources\CustomerResource\Pages; use Dth\Crm\Filament\Resources\CustomerResource; use Filament\Resources\Pages\ListRecords; use Filament\Actions\CreateAction; class ListCustomers extends ListRecords {protected static string $resource=CustomerResource::class; protected function getHeaderActions():array{return [CreateAction::make()];}}
