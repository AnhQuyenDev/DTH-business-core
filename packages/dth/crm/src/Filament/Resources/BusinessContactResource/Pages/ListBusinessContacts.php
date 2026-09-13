<?php
namespace Dth\Crm\Filament\Resources\BusinessContactResource\Pages;use Dth\Crm\Filament\Resources\BusinessContactResource;use Filament\Resources\Pages\ListRecords;use Filament\Actions\CreateAction;class ListBusinessContacts extends ListRecords{protected static string $resource=BusinessContactResource::class;protected function getHeaderActions():array{return [CreateAction::make()];}}
