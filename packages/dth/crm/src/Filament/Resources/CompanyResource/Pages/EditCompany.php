<?php
namespace Dth\Crm\Filament\Resources\CompanyResource\Pages; use Dth\Crm\Filament\Resources\CompanyResource; use Filament\Resources\Pages\EditRecord; use Filament\Actions\DeleteAction; class EditCompany extends EditRecord {protected static string $resource=CompanyResource::class; protected function getHeaderActions():array{return [DeleteAction::make()];}}
