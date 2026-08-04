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
        $totalCustomers = Customer::count();
        $newThisMonth = Customer::where('created_at', '>=', now()->startOfMonth())->count();
        $active = Customer::where('status', CustomerStatus::Active->value)->count();
        $potential = Customer::where('status', CustomerStatus::Potential->value)->count();
        $churned = Customer::where('status', CustomerStatus::Churned->value)->count();
        $totalContacts = Contact::count();

        return [
            Stat::make('Tổng khách hàng', number_format($totalCustomers))
                ->description("Mới tháng này: {$newThisMonth}")
                ->icon('heroicon-o-user-group')
                ->color('primary'),
            Stat::make('Đang hoạt động', number_format($active))
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Khách hàng tiềm năng', number_format($potential))
                ->icon('heroicon-o-light-bulb')
                ->color('info'),
            Stat::make('Đã mất', number_format($churned))
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
            Stat::make('Tổng Contact (khách hàng tiềm năng)', number_format($totalContacts))
                ->icon('heroicon-o-identification')
                ->color('gray'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
