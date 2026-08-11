<?php

namespace App\Filament\Pages;

use App\Filament\Resources\DepartmentResource;
use App\Filament\Resources\PositionResource;
use App\Filament\Resources\StaffResource;
use App\Models\Crm\Department;
use App\Models\Crm\Position;
use App\Models\Crm\Staff;
use Filament\Pages\Page;

class OrganizationAccessPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'configuration/organization-structure';

    protected static string $view = 'filament.pages.organization-access';

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
        return __('configuration.navigation.organization');
    }

    public function getTitle(): string
    {
        return __('configuration.organization.title');
    }

    public function getSubheading(): ?string
    {
        return __('configuration.organization.subheading');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->can('system.manage-organization')
            || $user?->can('crm.manage-staff'));
    }

    protected function getViewData(): array
    {
        $canManageDepartments = DepartmentResource::canViewAny();
        $canManagePositions = PositionResource::canViewAny();
        $canManageStaff = StaffResource::canViewAny();

        $cards = [
            'departments' => [
                'icon' => 'heroicon-o-building-office',
                'url' => DepartmentResource::getUrl(),
                'visible' => $canManageDepartments,
                'count' => $canManageDepartments ? Department::query()->count() : 0,
            ],
            'positions' => [
                'icon' => 'heroicon-o-identification',
                'url' => PositionResource::getUrl(),
                'visible' => $canManagePositions,
                'count' => $canManagePositions ? Position::query()->count() : 0,
            ],
            'staff' => [
                'icon' => 'heroicon-o-users',
                'url' => StaffResource::getUrl(),
                'visible' => $canManageStaff,
                'count' => $canManageStaff ? Staff::query()->count() : 0,
            ],
        ];

        $attention = [];

        if ($canManageStaff) {
            $withoutDepartment = Staff::query()->whereNull('department_id')->count();
            if ($withoutDepartment > 0) {
                $attention[] = __('configuration.organization.staff_without_department', ['count' => $withoutDepartment]);
            }

            $withoutPosition = Staff::query()->whereNull('position_id')->count();
            if ($withoutPosition > 0) {
                $attention[] = __('configuration.organization.staff_without_position', ['count' => $withoutPosition]);
            }

            $withoutFunction = Staff::query()->whereDoesntHave('businessFunctions', fn ($query) => $query->where('is_active', true))->count();
            if ($withoutFunction > 0) {
                $attention[] = __('configuration.organization.staff_without_function', ['count' => $withoutFunction]);
            }
        }

        return compact('cards', 'attention');
    }
}
