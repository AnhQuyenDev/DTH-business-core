<?php

namespace App\Filament\Widgets;

use App\Enums\Crm\StaffEmploymentStatus;
use App\Models\Crm\CustomerAssignment;
use App\Models\Crm\Staff;
use App\Models\Crm\StaffAvailability;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StaffOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getStats(): array
    {
        $total = Staff::count();
        $active = Staff::where('employment_status', StaffEmploymentStatus::Active->value)->count();
        $inactive = Staff::where('employment_status', StaffEmploymentStatus::Inactive->value)->count();
        $resigned = Staff::where('employment_status', StaffEmploymentStatus::Resigned->value)->count();

        $onLeave = StaffAvailability::where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->whereIn('status', ['leave', 'sick', 'absent'])
            ->distinct('staff_id')
            ->count('staff_id');

        $totalManaged = CustomerAssignment::where('assignment_type', 'owner')
            ->where('status', 'active')->count();
        $totalSupported = CustomerAssignment::where('assignment_type', 'support')
            ->where('status', 'active')->count();

        return [
            Stat::make('Tổng nhân viên', $total)
                ->icon('heroicon-o-users')
                ->color('info'),
            Stat::make('Đang làm việc', $active)
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Tạm nghỉ', $inactive)
                ->icon('heroicon-o-pause-circle')
                ->color('warning'),
            Stat::make('Đã nghỉ việc', $resigned)
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
            Stat::make('Đang nghỉ phép', $onLeave)
                ->icon('heroicon-o-calendar')
                ->color('gray'),
            Stat::make('KH đang quản lý / hỗ trợ', "{$totalManaged} / {$totalSupported}")
                ->icon('heroicon-o-user-group')
                ->color('primary'),
        ];
    }
}
