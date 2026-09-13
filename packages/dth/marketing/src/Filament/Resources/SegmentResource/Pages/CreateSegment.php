<?php
namespace Dth\Marketing\Filament\Resources\SegmentResource\Pages;
use Dth\Marketing\Filament\Resources\SegmentResource;
use Filament\Resources\Pages\CreateRecord;
class CreateSegment extends CreateRecord { protected static string $resource = SegmentResource::class; protected function mutateFormDataBeforeCreate(array $data): array { $data['created_by'] = auth()->id(); $data['is_automatic'] = false; return $data; } }
