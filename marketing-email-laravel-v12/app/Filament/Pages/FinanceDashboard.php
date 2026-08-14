<?php

namespace App\Filament\Pages;

use App\Enums\Sales\PaymentStatus;
use App\Filament\Resources\Finance\PaymentResource;
use App\Filament\Resources\Sales\PaymentTrackingResource;
use App\Models\Sales\Quotation;
use App\Services\Dashboard\EnterpriseAnalyticsService;
use App\Services\Dashboard\DashboardSnapshotCache;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?int $navigationSort = -10;
    protected static string $view = 'filament.pages.finance-dashboard';

    public string $period = '30d';

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.finance');
    }

    public static function getNavigationLabel(): string
    {
        return __('uiux.dashboard.finance.title');
    }

    public function getTitle(): string
    {
        return __('uiux.dashboard.finance.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('sales.view-payments') ?? false;
    }

    protected function getViewData(): array
    {
        $queue = Quotation::query()
            ->where('payment_status', PaymentStatus::PendingVerification->value)
            ->with(['paymentNotices' => fn ($q) => $q->latest('submitted_at'), 'company', 'contact'])
            ->orderBy('updated_at')
            ->limit(8)
            ->get()
            ->map(function (Quotation $quotation): array {
                $notice = $quotation->paymentNotices->first();

                return [
                    'id' => $quotation->id,
                    'code' => $quotation->quotation_code,
                    'customer' => $quotation->party_display_name,
                    'amount' => (float) $quotation->grand_total,
                    'submitted_at' => $notice?->submitted_at,
                ];
            })
            ->all();

        return [
            'analytics' => app(DashboardSnapshotCache::class)->remember(auth()->user(), 'finance', $this->period, fn (): array => app(EnterpriseAnalyticsService::class)->finance(auth()->user(), $this->period)),
            'queue' => $queue,
            'links' => [
                'tracking' => PaymentTrackingResource::canViewAny() ? PaymentTrackingResource::getUrl() : null,
                'history' => PaymentResource::canViewAny() ? PaymentResource::getUrl() : null,
                'revenue' => RevenueReportPage::canAccess() ? RevenueReportPage::getUrl() : null,
            ],
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $user = auth()->user();
        $data = app(DashboardSnapshotCache::class)->remember($user, 'finance', $this->period, fn (): array => app(EnterpriseAnalyticsService::class)->finance($user, $this->period));
        $filename = 'finance-analytics-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [__('uiux.dashboard.finance.title')]);
            foreach (['gross_collected', 'net_revenue', 'tax', 'payments', 'pending_verification', 'outstanding', 'avg_verification_minutes'] as $key) {
                fputcsv($out, [__('analytics.'.$key), $data['summary'][$key] ?? 0]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
