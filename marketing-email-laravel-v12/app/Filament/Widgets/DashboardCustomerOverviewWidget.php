<?php

namespace App\Filament\Widgets;

use App\Enums\Crm\CustomerStatus;
use App\Models\Crm\Customer;
use App\Models\Marketing\Contact;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardCustomerOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getStats(): array
    {
        $statusCounts = Customer::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')->pluck('aggregate', 'status');
        $totalCustomers = (int) $statusCounts->sum();
        $newThisMonth = Customer::where('created_at', '>=', now()->startOfMonth())->count();
        $active = (int) $statusCounts->get(CustomerStatus::Active->value, 0);
        $potential = (int) $statusCounts->get(CustomerStatus::Potential->value, 0);
        $churned = (int) $statusCounts->get(CustomerStatus::Churned->value, 0);
        $totalContacts = Contact::count();

        return [
            Stat::make(__('dashboard.customer.total_customers'), number_format($totalCustomers))
                ->description(__('dashboard.customer.new_this_month', ['count' => $newThisMonth]))
                ->icon('heroicon-o-user-group')
                ->color('primary'),
            Stat::make(__('dashboard.customer.active'), number_format($active))
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make(__('dashboard.customer.potential'), number_format($potential))
                ->icon('heroicon-o-light-bulb')
                ->color('info'),
            Stat::make(__('dashboard.customer.lost'), number_format($churned))
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
            Stat::make(__('dashboard.customer.total_contacts'), number_format($totalContacts))
                ->icon('heroicon-o-identification')
                ->color('gray'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
