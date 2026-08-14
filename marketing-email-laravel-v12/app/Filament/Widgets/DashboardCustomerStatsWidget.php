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
        $customerCounts = Customer::query()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $totalCustomers = (int) $customerCounts->sum();
        $active = (int) $customerCounts->get(CustomerStatus::Active->value, 0);
        $potential = (int) $customerCounts->get(CustomerStatus::Potential->value, 0);

        $qualificationCounts = ContactQualification::query()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $new = (int) $qualificationCounts->get(ContactQualificationStatus::New->value, 0);
        $contacting = (int) $qualificationCounts->get(ContactQualificationStatus::Assigned->value, 0)
            + (int) $qualificationCounts->get(ContactQualificationStatus::Contacting->value, 0);
        $followUp = (int) $qualificationCounts->get(ContactQualificationStatus::FollowUp->value, 0);
        $converted = (int) $qualificationCounts->get(ContactQualificationStatus::Converted->value, 0);
        $unqualified = (int) $qualificationCounts->get(ContactQualificationStatus::Unqualified->value, 0);

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
                ->description(__('dashboard.customer.processed').': '.number_format($totalProcessed))
                ->icon('heroicon-o-queue-list')
                ->color('primary'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}
