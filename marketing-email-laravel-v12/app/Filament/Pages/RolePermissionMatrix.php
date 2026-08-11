<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use Filament\Pages\Page;

class RolePermissionMatrix extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static string $view = 'filament.pages.role-permission-matrix';

    protected static ?int $navigationSort = 60;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

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
        return auth()->user()?->can('system.manage-rbac') ?? false;
    }

    protected function getViewData(): array
    {
        return [
            'roles' => collect(UserRole::assignableCases())
                ->map(fn (UserRole $role): array => [
                    'code' => $role->value,
                    'label' => $role->label(),
                    'color' => $role->color(),
                    'staff_required' => $role->requiresStaff()
                        ? __('field.yes')
                        : __('field.no'),
                    'summary' => __('role_permissions.summary.'.$role->value),
                ])
                ->all(),
        ];
    }
}
