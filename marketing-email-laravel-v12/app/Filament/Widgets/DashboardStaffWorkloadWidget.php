<?php

namespace App\Filament\Widgets;

use App\Enums\Crm\StaffEmploymentStatus;
use App\Models\Crm\CustomerAssignment;
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
        $staffCounts = Staff::query()->selectRaw('employment_status, COUNT(*) as aggregate')->groupBy('employment_status')->pluck('aggregate', 'employment_status');
        $active = (int) $staffCounts->get(StaffEmploymentStatus::Active->value, 0);
        $inactive = (int) $staffCounts->get(StaffEmploymentStatus::Inactive->value, 0);
        $resigned = (int) $staffCounts->get(StaffEmploymentStatus::Resigned->value, 0);

        $onLeave = StaffAvailability::where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->whereIn('status', ['leave', 'sick', 'absent'])
            ->distinct('staff_id')
            ->count('staff_id');

        $available = $active - $onLeave;

        $assignmentCounts = CustomerAssignment::query()
            ->join('staff as workload_staff', 'workload_staff.id', '=', 'customer_assignments.staff_id')
            ->where('workload_staff.employment_status', StaffEmploymentStatus::Active->value)
            ->where('customer_assignments.status', 'active')
            ->selectRaw('customer_assignments.assignment_type, COUNT(*) as aggregate')
            ->groupBy('customer_assignments.assignment_type')
            ->pluck('aggregate', 'assignment_type');
        $totalManaging = (int) $assignmentCounts->get('owner', 0);
        $totalSupporting = (int) $assignmentCounts->get('support', 0);
        $avgManaging = $active > 0 ? round($totalManaging / $active, 1) : 0;
        $avgSupporting = $active > 0 ? round($totalSupporting / $active, 1) : 0;

        return [
            Stat::make(__('dashboard.staff.working'), number_format($active))
                ->description(__('dashboard.staff.present').': '.number_format($available).' / '.__('dashboard.staff.on_leave').': '.number_format($onLeave))
                ->icon('heroicon-o-users')
                ->color('success'),
            Stat::make(__('dashboard.staff.inactive').' / '.__('dashboard.staff.resigned'), number_format($inactive).' / '.number_format($resigned))
                ->icon('heroicon-o-pause-circle')
                ->color('warning'),
            Stat::make(__('dashboard.staff.managed_customers'), number_format($totalManaging))
                ->description(__('dashboard.staff.average').': '.number_format($avgManaging).' '.__('uiux.dashboard.common.customers_per_staff'))
                ->icon('heroicon-o-user-group')
                ->color('primary'),
            Stat::make(__('dashboard.staff.supported_customers'), number_format($totalSupporting))
                ->description(__('dashboard.staff.average').': '.number_format($avgSupporting).' '.__('uiux.dashboard.common.customers_per_staff'))
                ->icon('heroicon-o-hand-raised')
                ->color('info'),
        ];
    }
}
