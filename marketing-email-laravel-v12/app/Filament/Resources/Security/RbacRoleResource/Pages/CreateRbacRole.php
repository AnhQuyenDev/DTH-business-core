<?php

namespace App\Filament\Resources\Security\RbacRoleResource\Pages;

use App\Filament\Resources\Security\RbacRoleResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateRbacRole extends CreateRecord
{
    protected static string $resource = RbacRoleResource::class;

    public function getTitle(): string
    {
        return __('configuration.rbac.create');
    }

    public function getSubheading(): ?string
    {
        return __('configuration.rbac.create_subheading');
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->icon('heroicon-o-shield-check');
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
