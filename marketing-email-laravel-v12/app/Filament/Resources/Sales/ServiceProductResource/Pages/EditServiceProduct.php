<?php
namespace App\Filament\Resources\Sales\ServiceProductResource\Pages;
use App\Filament\Resources\Sales\ServiceProductResource; use Filament\Resources\Pages\EditRecord;
class EditServiceProduct extends EditRecord { protected static string $resource=ServiceProductResource::class; protected function mutateFormDataBeforeSave(array $data): array { $data['updated_by']=auth()->id(); return $data; } }
