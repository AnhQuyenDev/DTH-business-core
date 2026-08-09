<?php

namespace App\Filament\Widgets;

use App\Enums\Crm\ContactQualificationStatus;
use App\Models\Crm\ContactQualification;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DashboardLeadPipelineWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected static bool $isLazy = false;

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getStats(): array
    {
        $new = ContactQualification::where('status', ContactQualificationStatus::New->value)->count();
        $contacting = ContactQualification::whereIn('status', [
            ContactQualificationStatus::Assigned->value,
            ContactQualificationStatus::Contacting->value,
        ])->count();
        $followUp = ContactQualification::where('status', ContactQualificationStatus::FollowUp->value)->count();
        $qualified = ContactQualification::where('status', ContactQualificationStatus::Qualified->value)->count();
        $converted = ContactQualification::where('status', ContactQualificationStatus::Converted->value)->count();
        $unqualified = ContactQualification::where('status', ContactQualificationStatus::Unqualified->value)->count();

        $totalActive = $new + $contacting + $followUp;
        $totalProcessed = $qualified + $unqualified + $converted;

        return [
            Stat::make(__('dashboard.lead.new'), number_format($new))
                ->description(__('dashboard.lead.not_contacted'))
                ->icon('heroicon-o-inbox')
                ->color('gray'),
            Stat::make(__('dashboard.lead.in_progress'), number_format($contacting + $followUp))
                ->description(__('dashboard.lead.contacting_followup', ['contacting' => $contacting, 'follow_up' => $followUp]))
                ->icon('heroicon-o-phone')
                ->color('warning'),
            Stat::make(__('dashboard.lead.qualified_or_converted'), number_format($qualified + $converted))
                ->description(__('dashboard.lead.qualified_converted_detail', ['qualified' => $qualified, 'converted' => $converted]))
                ->icon('heroicon-o-check-badge')
                ->color('success'),
            Stat::make(__('dashboard.lead.unqualified'), number_format($unqualified))
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
            Stat::make(__('dashboard.lead.processing'), number_format($totalActive))
                ->description(__('dashboard.lead.processed_detail', ['count' => $totalProcessed]))
                ->icon('heroicon-o-queue-list')
                ->color('primary'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}
