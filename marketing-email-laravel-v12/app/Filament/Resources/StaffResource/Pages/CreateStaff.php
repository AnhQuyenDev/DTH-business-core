<?php

namespace App\Filament\Resources\StaffResource\Pages;

use App\Filament\Resources\StaffResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    public function getTitle(): string
    {
        return __('configuration.staff.create');
    }

    public function getSubheading(): ?string
    {
        return __('configuration.staff.create_subheading');
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->icon('heroicon-o-user-plus');
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
