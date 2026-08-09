<?php

namespace App\Filament\Pages;

use App\Enums\Sales\OpportunityStage;
use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\QuotationStatus;
use App\Filament\Resources\Sales\OpportunityResource;
use App\Filament\Resources\Sales\QuotationApprovalResource;
use App\Filament\Resources\Sales\QuotationResource;
use App\Models\Sales\Opportunity;
use App\Services\Dashboard\DashboardScopeService;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class SalesDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static ?int $navigationSort = -10;
    protected static string $view = 'filament.pages.sales-dashboard';

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.sales');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.sales_dashboard');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->isSalesManager() || $user->isSalesStaff());
    }

    public function getTitle(): string
    {
        return auth()->user()?->isSalesManager()
            ? __('uiux.dashboard.sales.manager_title')
            : __('uiux.dashboard.sales.staff_title');
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $scope = app(DashboardScopeService::class);
        $opportunities = $scope->opportunities($user);
        $quotations = $scope->quotations($user);
        $payments = $scope->payments($user)->verified();

        $openStages = [
            OpportunityStage::Discovery->value,
            OpportunityStage::Qualified->value,
            OpportunityStage::Proposal->value,
            OpportunityStage::Negotiation->value,
        ];

        $pipelineRows = collect(OpportunityStage::cases())
            ->map(function (OpportunityStage $stage) use ($opportunities): array {
                return [
                    'label' => $stage->label(),
                    'count' => (clone $opportunities)->where('stage', $stage->value)->count(),
                    'value' => (float) (clone $opportunities)->where('stage', $stage->value)->sum('estimated_value'),
                    'color' => $stage->color(),
                ];
            })
            ->filter(fn (array $row): bool => $row['count'] > 0 || $row['value'] > 0)
            ->values()
            ->all();

        $recent = (clone $opportunities)
            ->with(['company:id,legal_name', 'primaryContact:id,full_name', 'assignedStaff:id,full_name'])
            ->whereIn('stage', $openStages)
            ->orderByRaw('expected_close_date is null, expected_close_date asc')
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get()
            ->map(fn (Opportunity $opportunity): array => [
                'id' => $opportunity->id,
                'code' => $opportunity->opportunity_code,
                'title' => $opportunity->title,
                'party' => $opportunity->company?->legal_name ?: $opportunity->primaryContact?->full_name ?: '—',
                'stage' => $opportunity->stage?->label() ?: '—',
                'expected_close_date' => $opportunity->expected_close_date,
                'value' => (float) $opportunity->estimated_value,
                'staff' => $opportunity->assignedStaff?->full_name,
            ])
            ->all();

        return [
            'isManager' => $user->isSalesManager(),
            'summary' => [
                'open_pipeline' => (clone $opportunities)->whereIn('stage', $openStages)->count(),
                'pipeline_value' => (float) (clone $opportunities)->whereIn('stage', $openStages)->sum('estimated_value'),
                'won_month' => (clone $opportunities)->where('stage', OpportunityStage::Won->value)->whereBetween('won_at', [now()->startOfMonth(), now()->endOfDay()])->count(),
                'cash_collected' => (float) (clone $payments)->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfDay()])->sum('amount'),
                'pending_approval' => (clone $quotations)->where('status', QuotationStatus::PendingApproval->value)->count(),
                'accepted_unpaid' => (clone $quotations)->where('status', QuotationStatus::Accepted->value)->where('payment_status', PaymentStatus::Unpaid->value)->count(),
                'expiring_soon' => (clone $quotations)->whereIn('status', [QuotationStatus::Approved->value, QuotationStatus::Sent->value, QuotationStatus::Viewed->value])->whereDate('valid_until', '>=', today())->whereDate('valid_until', '<=', now()->addDays(7))->count(),
            ],
            'pipelineRows' => $pipelineRows,
            'recent' => $recent,
            'links' => [
                'opportunities' => OpportunityResource::canViewAny() ? OpportunityResource::getUrl() : null,
                'quotations' => QuotationResource::canViewAny() ? QuotationResource::getUrl() : null,
                'approvals' => QuotationApprovalResource::canViewAny() ? QuotationApprovalResource::getUrl() : null,
                'revenue' => RevenueReportPage::canAccess() ? RevenueReportPage::getUrl() : null,
            ],
        ];
    }


    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = $this->getViewData();
        $filename = 'sales-dashboard-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [__('uiux.dashboard.sales.manager_title')]);
            foreach ($data['summary'] as $key => $value) {
                $labelKey = $key === 'expiring_soon' ? 'expiring' : $key;
                fputcsv($out, [__('uiux.dashboard.sales.'.$labelKey), $value]);
            }
            fputcsv($out, []);
            fputcsv($out, [__('uiux.dashboard.sales.pipeline_chart')]);
            fputcsv($out, [__('field.stage'), __('uiux.dashboard.common.count'), __('uiux.dashboard.common.value')]);
            foreach ($data['pipelineRows'] as $row) {
                fputcsv($out, [$row['label'], $row['count'], $row['value']]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

}
