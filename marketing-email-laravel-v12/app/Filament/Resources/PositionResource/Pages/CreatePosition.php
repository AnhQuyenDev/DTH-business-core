<?php

namespace App\Filament\Resources\PositionResource\Pages;

use App\Filament\Resources\PositionResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreatePosition extends CreateRecord
{
    protected static string $resource = PositionResource::class;

    public function getTitle(): string
    {
        return __('configuration.position.create');
    }

    public function getSubheading(): ?string
    {
        return __('configuration.position.create_subheading');
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->icon('heroicon-o-plus-circle');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()->icon('heroicon-o-plus');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->icon('heroicon-o-arrow-left');
    }

}
