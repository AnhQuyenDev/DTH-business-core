<?php
namespace Dth\Crm\Filament\Resources\StaffResource\Pages; use Dth\Crm\Filament\Resources\StaffResource; use Filament\Resources\Pages\EditRecord; use Filament\Actions\DeleteAction; class EditStaff extends EditRecord {protected static string $resource=StaffResource::class; protected function getHeaderActions():array{return [DeleteAction::make()];}}
