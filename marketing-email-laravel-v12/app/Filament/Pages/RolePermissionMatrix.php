<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use Filament\Pages\Page;

class RolePermissionMatrix extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static string $view = 'filament.pages.role-permission-matrix';

    protected static ?int $navigationSort = 70;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.role_permissions');
    }

    public function getTitle(): string
    {
        return __('navigation.role_permissions');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getViewData(): array
    {
        return [
            'roles' => collect(UserRole::cases())->map(function (UserRole $role): array {
                $function = $role->requiredDepartmentFunction();

                return [
                    'code' => $role->value,
                    'label' => $role->label(),
                    'color' => $role->color(),
                    'department_function' => $function?->label()
                        ?? __('role_permissions.no_department_requirement'),
                    'summary' => __('role_permissions.summary.'.$role->value),
                ];
            })->all(),
        ];
    }
}
