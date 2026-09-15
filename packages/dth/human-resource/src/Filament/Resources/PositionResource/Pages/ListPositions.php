<?php
namespace Dth\HumanResource\Filament\Resources\PositionResource\Pages;
use Dth\HumanResource\Filament\Resources\PositionResource;
use Dth\HumanResource\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListPositions extends ListRecords { protected static string $resource = PositionResource::class; protected function getHeaderActions(): array { return [CreateAction::make()->label(UiText::get('actions.new_position','New job title'))]; } }
