<?php
namespace Dth\Crm\Filament\Resources\ContactResource\Pages; use Dth\Crm\Filament\Resources\ContactResource; use Filament\Resources\Pages\EditRecord; use Filament\Actions\DeleteAction; class EditContact extends EditRecord {protected static string $resource=ContactResource::class; protected function getHeaderActions():array{return [DeleteAction::make()];}}
