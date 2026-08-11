<?php

namespace App\Filament\Resources\UiBadgeStyleResource\Pages;

use App\Filament\Resources\UiBadgeStyleResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUiBadgeStyle extends EditRecord
{
    protected static string $resource = UiBadgeStyleResource::class;

    public function getTitle(): string
    {
        return __('configuration.appearance.edit');
    }

    public function getSubheading(): ?string
    {
        return __('configuration.appearance.edit_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->icon('heroicon-o-trash'),
        ];
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
