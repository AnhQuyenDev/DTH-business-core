<?php

namespace App\Filament\Pages;

use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\QuotationStatus;
use App\Filament\Resources\Finance\PaymentResource;
use App\Filament\Resources\Sales\PaymentTrackingResource;
use App\Models\Finance\Payment;
use App\Models\Finance\PaymentReceipt;
use App\Models\Sales\Quotation;
use App\Services\Dashboard\DashboardScopeService;
use Carbon\Carbon;
use Filament\Pages\Page;

class FinanceDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?int $navigationSort = -10;
    protected static string $view = 'filament.pages.finance-dashboard';

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
        return auth()->user()?->isFinanceStaff() ?? false;
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $scope = app(DashboardScopeService::class);
        $payments = $scope->payments($user)->verified();
        $quotations = $scope->quotations($user);

        $start = now()->subMonths(5)->startOfMonth();
        $monthRows = (clone $payments)
            ->whereBetween('paid_at', [$start, now()->endOfDay()])
            ->get(['amount', 'paid_at'])
            ->groupBy(fn (Payment $payment): string => $payment->paid_at?->format('Y-m') ?: '');

        $cashflow = collect(range(0, 5))->map(function (int $offset) use ($start, $monthRows): array {
            $month = $start->copy()->addMonths($offset);
            $key = $month->format('Y-m');

            return [
                'label' => $month->format('m/Y'),
                'value' => (float) ($monthRows->get($key)?->sum('amount') ?? 0),
            ];
        })->all();

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
            'summary' => [
                'pending' => (clone $quotations)->where('payment_status', PaymentStatus::PendingVerification->value)->count(),
                'paid_today' => (float) (clone $payments)->whereDate('paid_at', today())->sum('amount'),
                'month_revenue' => (float) (clone $payments)->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfDay()])->sum('amount'),
                'receipts' => PaymentReceipt::query()->whereBetween('generated_at', [now()->startOfMonth(), now()->endOfDay()])->count(),
                'accepted_unpaid' => (clone $quotations)->where('status', QuotationStatus::Accepted->value)->where('payment_status', PaymentStatus::Unpaid->value)->count(),
            ],
            'cashflow' => $cashflow,
            'queue' => $queue,
            'links' => [
                'tracking' => PaymentTrackingResource::canViewAny() ? PaymentTrackingResource::getUrl() : null,
                'history' => PaymentResource::canViewAny() ? PaymentResource::getUrl() : null,
                'revenue' => RevenueReportPage::canAccess() ? RevenueReportPage::getUrl() : null,
            ],
        ];
    }


    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = $this->getViewData();
        $filename = 'finance-dashboard-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [__('uiux.dashboard.finance.title')]);
            foreach ($data['summary'] as $key => $value) {
                fputcsv($out, [__('uiux.dashboard.finance.'.$key), $value]);
            }
            fputcsv($out, []);
            fputcsv($out, [__('uiux.dashboard.finance.cashflow')]);
            fputcsv($out, [__('uiux.dashboard.common.month'), __('uiux.dashboard.common.value')]);
            foreach ($data['cashflow'] as $row) {
                fputcsv($out, [$row['label'], $row['value']]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

}
