<?php

namespace App\Filament\Widgets;

use App\Enums\Crm\StaffEmploymentStatus;
use App\Models\Crm\Staff;
use App\Models\Crm\StaffAvailability;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardStaffWorkloadWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getStats(): array
    {
        $active = Staff::where('employment_status', StaffEmploymentStatus::Active->value)->count();
        $inactive = Staff::where('employment_status', StaffEmploymentStatus::Inactive->value)->count();
        $resigned = Staff::where('employment_status', StaffEmploymentStatus::Resigned->value)->count();

        $onLeave = StaffAvailability::where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->whereIn('status', ['leave', 'sick', 'absent'])
            ->distinct('staff_id')
            ->count('staff_id');

        $available = $active - $onLeave;

        $staffList = Staff::where('employment_status', StaffEmploymentStatus::Active->value)
            ->withCount([
                'assignments as managing_count' => fn ($q) => $q->where('assignment_type', 'owner')->where('status', 'active'),
                'assignments as supporting_count' => fn ($q) => $q->where('assignment_type', 'support')->where('status', 'active'),
            ])
            ->get();

        $totalManaging = $staffList->sum('managing_count');
        $totalSupporting = $staffList->sum('supporting_count');
        $avgManaging = $staffList->count() > 0 ? round($totalManaging / $staffList->count(), 1) : 0;
        $avgSupporting = $staffList->count() > 0 ? round($totalSupporting / $staffList->count(), 1) : 0;

        return [
            Stat::make(__('Đang làm việc'), number_format($active))
                ->description(__('Có mặt') . ': ' . number_format($available) . ' / ' . __('Nghỉ phép') . ': ' . number_format($onLeave))
                ->icon('heroicon-o-users')
                ->color('success'),
            Stat::make(__('Tạm nghỉ / Đã nghỉ việc'), number_format($inactive) . ' / ' . number_format($resigned))
                ->icon('heroicon-o-pause-circle')
                ->color('warning'),
            Stat::make(__('Tổng khách hàng quản lý'), number_format($totalManaging))
                ->description(__('Trung bình') . ': ' . number_format($avgManaging) . ' ' . __('khách hàng/nhân viên'))
                ->icon('heroicon-o-user-group')
                ->color('primary'),
            Stat::make(__('Tổng khách hàng hỗ trợ'), number_format($totalSupporting))
                ->description(__('Trung bình') . ': ' . number_format($avgSupporting) . ' ' . __('khách hàng/nhân viên'))
                ->icon('heroicon-o-hand-raised')
                ->color('info'),
        ];
    }
}
