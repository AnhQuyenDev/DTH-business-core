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
        $counts = ContactQualification::query()->selectRaw('status, COUNT(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $new = (int) $counts->get(ContactQualificationStatus::New->value, 0);
        $contacting = (int) $counts->get(ContactQualificationStatus::Assigned->value, 0)
            + (int) $counts->get(ContactQualificationStatus::Contacting->value, 0);
        $followUp = (int) $counts->get(ContactQualificationStatus::FollowUp->value, 0);
        $qualified = (int) $counts->get(ContactQualificationStatus::Qualified->value, 0);
        $converted = (int) $counts->get(ContactQualificationStatus::Converted->value, 0);
        $unqualified = (int) $counts->get(ContactQualificationStatus::Unqualified->value, 0);

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
