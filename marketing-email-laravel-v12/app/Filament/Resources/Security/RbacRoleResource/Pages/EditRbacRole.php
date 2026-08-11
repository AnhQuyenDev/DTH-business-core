<?php

namespace App\Filament\Resources\Security\RbacRoleResource\Pages;

use App\Filament\Resources\Security\RbacRoleResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRbacRole extends EditRecord
{
    protected static string $resource = RbacRoleResource::class;

    public function getTitle(): string
    {
        return __('configuration.rbac.edit');
    }

    public function getSubheading(): ?string
    {
        return __('configuration.rbac.edit_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->visible(fn (): bool => ! $this->record->is_system),
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
