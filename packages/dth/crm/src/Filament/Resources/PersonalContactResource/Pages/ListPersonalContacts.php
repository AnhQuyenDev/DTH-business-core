<?php
namespace Dth\Crm\Filament\Resources\PersonalContactResource\Pages;use Dth\Crm\Filament\Resources\PersonalContactResource;use Filament\Resources\Pages\ListRecords;use Filament\Actions\CreateAction;class ListPersonalContacts extends ListRecords{protected static string $resource=PersonalContactResource::class;protected function getHeaderActions():array{return [CreateAction::make()];}}
