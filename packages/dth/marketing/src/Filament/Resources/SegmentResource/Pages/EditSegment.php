<?php
namespace Dth\Marketing\Filament\Resources\SegmentResource\Pages;
use Dth\Marketing\Filament\Resources\SegmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditSegment extends EditRecord { protected static string $resource = SegmentResource::class; protected function getHeaderActions(): array { return [DeleteAction::make()]; } }
