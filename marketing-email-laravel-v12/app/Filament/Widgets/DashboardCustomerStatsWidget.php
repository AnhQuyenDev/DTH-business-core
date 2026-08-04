<?php

namespace App\Filament\Widgets;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\CustomerStatus;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Customer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardCustomerStatsWidget extends BaseWidget
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
        $active = Customer::where('status', CustomerStatus::Active->value)->count();
        $potential = Customer::where('status', CustomerStatus::Potential->value)->count();

        $new = ContactQualification::where('status', ContactQualificationStatus::New->value)->count();
        $contacting = ContactQualification::whereIn('status', [
            ContactQualificationStatus::Assigned->value,
            ContactQualificationStatus::Contacting->value,
        ])->count();
        $followUp = ContactQualification::where('status', ContactQualificationStatus::FollowUp->value)->count();
        $converted = ContactQualification::where('status', ContactQualificationStatus::Converted->value)->count();
        $unqualified = ContactQualification::where('status', ContactQualificationStatus::Unqualified->value)->count();

        $totalActive = $new + $contacting + $followUp;
        $totalProcessed = $unqualified + $converted;

        return [
            Stat::make(__('dashboard.customer.total_customers'), number_format($totalCustomers))
                ->icon('heroicon-o-user-group')
                ->color('primary'),
            Stat::make(__('dashboard.customer.active'), number_format($active))
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make(__('dashboard.customer.potential'), number_format($potential))
                ->icon('heroicon-o-light-bulb')
                ->color('info'),
            Stat::make(__('dashboard.customer.new'), number_format($new))
                ->description(__('dashboard.customer.pending'))
                ->icon('heroicon-o-inbox')
                ->color('gray'),
            Stat::make(__('dashboard.customer.contacting'), number_format($contacting + $followUp))
                ->icon('heroicon-o-phone')
                ->color('warning'),
            Stat::make(__('dashboard.customer.converted'), number_format($converted))
                ->icon('heroicon-o-arrow-right-circle')
                ->color('success'),
            Stat::make(__('dashboard.customer.unqualified'), number_format($unqualified))
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
            Stat::make(__('dashboard.customer.processing'), number_format($totalActive))
                ->description(__('dashboard.customer.processed') . ': ' . number_format($totalProcessed))
                ->icon('heroicon-o-queue-list')
                ->color('primary'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
