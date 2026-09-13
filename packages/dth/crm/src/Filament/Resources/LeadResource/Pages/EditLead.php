<?php
namespace Dth\Crm\Filament\Resources\LeadResource\Pages; use Dth\Crm\Filament\Resources\LeadResource; use Filament\Resources\Pages\EditRecord; use Filament\Actions\DeleteAction; class EditLead extends EditRecord {protected static string $resource=LeadResource::class; protected function getHeaderActions():array{return [DeleteAction::make()];}}
