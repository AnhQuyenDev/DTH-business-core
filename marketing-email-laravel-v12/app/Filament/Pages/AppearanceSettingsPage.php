<?php

namespace App\Filament\Pages;

use App\Filament\Resources\DepartmentResource;
use App\Filament\Resources\UiBadgeStyleResource;
use App\Support\Ui\SystemColorPalette;
use Filament\Pages\Page;

class AppearanceSettingsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'configuration/appearance';

    protected static string $view = 'filament.pages.appearance-settings';

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('configuration.navigation.appearance');
    }

    public function getTitle(): string
    {
        return __('configuration.appearance_hub.title');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->can('system.manage-company-settings')
            || $user?->can('system.manage-organization'));
    }

    protected function getViewData(): array
    {
        return [
            'colors' => SystemColorPalette::options(),
            'canManageDepartments' => DepartmentResource::canViewAny(),
            'canManageSharedBadges' => UiBadgeStyleResource::canViewAny(),
            'departmentUrl' => DepartmentResource::getUrl(),
            'sharedBadgeUrl' => UiBadgeStyleResource::getUrl(),
        ];
    }
}
