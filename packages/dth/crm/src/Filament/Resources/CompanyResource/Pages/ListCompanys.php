<?php
namespace Dth\Crm\Filament\Resources\CompanyResource\Pages; use Dth\Crm\Filament\Resources\CompanyResource; use Filament\Resources\Pages\ListRecords; use Filament\Actions\CreateAction; class ListCompanys extends ListRecords {protected static string $resource=CompanyResource::class; protected function getHeaderActions():array{return [CreateAction::make()];}}
