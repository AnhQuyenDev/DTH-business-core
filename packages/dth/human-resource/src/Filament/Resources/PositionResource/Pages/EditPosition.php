<?php
namespace Dth\HumanResource\Filament\Resources\PositionResource\Pages;
use Dth\HumanResource\Filament\Resources\PositionResource;
use Dth\HumanResource\Support\UiText;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditPosition extends EditRecord { protected static string $resource = PositionResource::class; protected function getHeaderActions(): array { return [DeleteAction::make()->label(UiText::get('common.actions.delete','Delete'))]; } }
