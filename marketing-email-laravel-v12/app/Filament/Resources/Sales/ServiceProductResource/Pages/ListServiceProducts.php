<?php
namespace App\Filament\Resources\Sales\ServiceProductResource\Pages;
use App\Filament\Resources\Sales\ServiceProductResource; use Filament\Actions\CreateAction; use Filament\Resources\Pages\ListRecords;
class ListServiceProducts extends ListRecords { protected static string $resource=ServiceProductResource::class; protected function getHeaderActions(): array { return [CreateAction::make()]; } }
