<?php
namespace Dth\Crm\Filament\Resources\LeadResource\Pages; use Dth\Crm\Filament\Resources\LeadResource; use Filament\Resources\Pages\ViewRecord; use Filament\Actions\EditAction; class ViewLead extends ViewRecord {protected static string $resource=LeadResource::class; protected function getHeaderActions():array{return [EditAction::make()];}}
