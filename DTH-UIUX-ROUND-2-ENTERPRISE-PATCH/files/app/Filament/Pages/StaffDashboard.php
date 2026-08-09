<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\StaffScheduleWidget;
use Filament\Pages\Page;

class StaffDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static string $view = 'filament.pages.staff-dashboard';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.crm');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.staff_schedule');
    }

    public function getTitle(): string
    {
        return __('navigation.staff_schedule');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->staff !== null;
    }

    protected function getViewData(): array
    {
        $staff = auth()->user()?->staff?->loadMissing(['department', 'position']);

        return [
            'staff' => $staff,
            'showSchedule' => auth()->user()?->isCustomerServiceStaff() ?? false,
        ];
    }

    public function getWidgets(): array
    {
        return (auth()->user()?->isCustomerServiceStaff() ?? false)
            ? [StaffScheduleWidget::class]
            : [];
    }
}
