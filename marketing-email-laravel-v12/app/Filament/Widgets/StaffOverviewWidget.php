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
        $staffCounts = Staff::query()
            ->selectRaw('employment_status, COUNT(*) as aggregate')
            ->groupBy('employment_status')
            ->pluck('aggregate', 'employment_status');
        $total = (int) $staffCounts->sum();
        $active = (int) $staffCounts->get(StaffEmploymentStatus::Active->value, 0);
        $inactive = (int) $staffCounts->get(StaffEmploymentStatus::Inactive->value, 0);
        $resigned = (int) $staffCounts->get(StaffEmploymentStatus::Resigned->value, 0);

        $onLeave = StaffAvailability::where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->whereIn('status', ['leave', 'sick', 'absent'])
            ->distinct('staff_id')
            ->count('staff_id');

        $assignmentCounts = CustomerAssignment::query()->where('status', 'active')
            ->selectRaw('assignment_type, COUNT(*) as aggregate')
            ->groupBy('assignment_type')->pluck('aggregate', 'assignment_type');
        $totalManaged = (int) $assignmentCounts->get('owner', 0);
        $totalSupported = (int) $assignmentCounts->get('support', 0);

        return [
            Stat::make(__('dashboard.staff.total'), $total)
                ->icon('heroicon-o-users')
                ->color('info'),
            Stat::make(__('dashboard.staff.working'), $active)
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make(__('dashboard.staff.inactive'), $inactive)
                ->icon('heroicon-o-pause-circle')
                ->color('warning'),
            Stat::make(__('dashboard.staff.resigned'), $resigned)
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
            Stat::make(__('dashboard.staff.on_leave'), $onLeave)
                ->icon('heroicon-o-calendar')
                ->color('gray'),
            Stat::make(__('dashboard.staff.customers'), "{$totalManaged} / {$totalSupported}")
                ->icon('heroicon-o-user-group')
                ->color('primary'),
        ];
    }
}
