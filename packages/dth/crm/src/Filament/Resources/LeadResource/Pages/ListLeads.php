<?php
namespace Dth\Crm\Filament\Resources\LeadResource\Pages; use Dth\Crm\Filament\Resources\LeadResource; use Filament\Resources\Pages\ListRecords; use Filament\Actions\CreateAction; class ListLeads extends ListRecords {protected static string $resource=LeadResource::class; protected function getHeaderActions():array{return [CreateAction::make()];}}
