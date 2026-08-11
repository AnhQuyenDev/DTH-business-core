<?php

namespace App\Filament\Resources\UiBadgeStyleResource\Pages;

use App\Filament\Resources\UiBadgeStyleResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateUiBadgeStyle extends CreateRecord
{
    protected static string $resource = UiBadgeStyleResource::class;

    public function getTitle(): string
    {
        return __('configuration.appearance.create');
    }

    public function getSubheading(): ?string
    {
        return __('configuration.appearance.create_subheading');
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
