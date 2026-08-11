<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Security\RbacRoleResource;
use App\Filament\Resources\StaffResource;
use App\Filament\Resources\UserResource;
use App\Models\Crm\Staff;
use App\Models\Crm\StaffBusinessFunction;
use App\Models\Security\Role;
use App\Models\User;
use Filament\Pages\Page;

class AccessControlPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'configuration/access-control';

    protected static string $view = 'filament.pages.access-control';

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('configuration.navigation.access_control');
    }

    public function getTitle(): string
    {
        return __('configuration.access.title');
    }

    public function getSubheading(): ?string
    {
        return __('configuration.access.subheading');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->can('system.manage-users')
            || $user?->can('system.manage-rbac')
            || $user?->can('crm.manage-staff'));
    }

    protected function getViewData(): array
    {
        $canManageAccounts = UserResource::canViewAny();
        $canManageRoles = RbacRoleResource::canViewAny();
        $canManageStaff = StaffResource::canViewAny();

        $cards = [
            'functions' => [
                'icon' => 'heroicon-o-squares-plus',
                'url' => StaffResource::getUrl(),
                'visible' => $canManageStaff,
                'count' => $canManageStaff ? StaffBusinessFunction::query()->where('is_active', true)->count() : 0,
            ],
            'accounts' => [
                'icon' => 'heroicon-o-key',
                'url' => UserResource::getUrl(),
                'visible' => $canManageAccounts,
                'count' => $canManageAccounts ? User::query()->count() : 0,
            ],
            'roles' => [
                'icon' => 'heroicon-o-shield-check',
                'url' => RbacRoleResource::getUrl(),
                'visible' => $canManageRoles,
                'count' => $canManageRoles ? Role::query()->count() : 0,
            ],
        ];

        $attention = [];

        if ($canManageStaff) {
            $withoutFunction = Staff::query()
                ->whereDoesntHave('businessFunctions', fn ($query) => $query->where('is_active', true))
                ->count();
            if ($withoutFunction > 0) {
                $attention[] = __('configuration.access.staff_without_function', ['count' => $withoutFunction]);
            }
        }

        if ($canManageAccounts) {
            $withoutAccount = Staff::query()->whereNull('user_id')->count();
            if ($withoutAccount > 0) {
                $attention[] = __('configuration.access.staff_without_account', ['count' => $withoutAccount]);
            }

            $inactiveAccounts = User::query()->where('is_active', false)->count();
            if ($inactiveAccounts > 0) {
                $attention[] = __('configuration.access.inactive_accounts', ['count' => $inactiveAccounts]);
            }
        }

        return compact('cards', 'attention');
    }
}
