<?php
namespace App\Filament\Resources\Sales\ServiceProductResource\Pages;
use App\Filament\Resources\Sales\ServiceProductResource; use Filament\Resources\Pages\CreateRecord;
class CreateServiceProduct extends CreateRecord { protected static string $resource=ServiceProductResource::class; protected function mutateFormDataBeforeCreate(array $data): array { $data['created_by']=auth()->id(); $data['updated_by']=auth()->id(); return $data; } }
