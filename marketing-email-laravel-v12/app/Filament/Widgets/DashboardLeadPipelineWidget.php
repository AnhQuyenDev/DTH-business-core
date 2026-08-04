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
            Stat::make('Mới', number_format($new))
                ->description('Chờ xử lý')
                ->icon('heroicon-o-inbox')
                ->color('gray'),
            Stat::make('Đang liên hệ / Theo dõi', number_format($contacting + $followUp))
                ->description("Liên hệ: {$contacting}, Theo dõi: {$followUp}")
                ->icon('heroicon-o-phone')
                ->color('warning'),
            Stat::make('Đã đánh giá / Chuyển đổi', number_format($qualified + $converted))
                ->description("Đánh giá: {$qualified}, Chuyển đổi: {$converted}")
                ->icon('heroicon-o-check-badge')
                ->color('success'),
            Stat::make('Không đạt', number_format($unqualified))
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
            Stat::make('Đang xử lý', number_format($totalActive))
                ->description("Đã xử lý: {$totalProcessed}")
                ->icon('heroicon-o-queue-list')
                ->color('primary'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}
