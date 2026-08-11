<?php

namespace App\Filament\Resources\PositionResource\Pages;

use App\Filament\Resources\PositionResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditPosition extends EditRecord
{
    protected static string $resource = PositionResource::class;

    public function getTitle(): string
    {
        return __('configuration.position.edit');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label(__('configuration.position.delete'))->icon('heroicon-o-trash'),
        ];
    }

    public function getSubheading(): ?string
    {
        return __('configuration.position.edit_subheading');
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->icon('heroicon-o-check-circle');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->icon('heroicon-o-arrow-left');
    }

}
