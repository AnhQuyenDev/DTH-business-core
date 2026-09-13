<?php
namespace Dth\Crm\Filament\Resources\ContactResource\Pages; use Dth\Crm\Filament\Resources\ContactResource; use Filament\Resources\Pages\ListRecords; use Filament\Actions\CreateAction; class ListContacts extends ListRecords {protected static string $resource=ContactResource::class; protected function getHeaderActions():array{return [CreateAction::make()];}}
