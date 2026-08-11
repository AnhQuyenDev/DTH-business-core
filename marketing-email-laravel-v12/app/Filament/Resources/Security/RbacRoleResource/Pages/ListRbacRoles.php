<?php

namespace App\Filament\Resources\Security\RbacRoleResource\Pages;

use App\Filament\Pages\RolePermissionMatrix;
use App\Filament\Resources\Security\RbacRoleResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRbacRoles extends ListRecords
{
    protected static string $resource = RbacRoleResource::class;

    public function getSubheading(): ?string
    {
        return __('configuration.rbac.list_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('system_role_matrix')
                ->label(__('configuration.rbac.matrix'))
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(RolePermissionMatrix::getUrl()),
            CreateAction::make()->label(__('configuration.rbac.create'))->icon('heroicon-o-plus-circle'),
        ];
    }
}
