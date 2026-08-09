<?php

namespace App\Filament\Pages;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Sales\OpportunityStage;
use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\QuotationStatus;
use App\Filament\Resources\AuditLogResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\MarketingCampaignResource;
use App\Filament\Resources\Sales\OpportunityResource;
use App\Filament\Resources\Sales\PaymentTrackingResource;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Finance\Payment;
use App\Models\Marketing\LandingPageSubmission;
use App\Models\Marketing\MarketingCampaign;
use App\Models\Sales\Opportunity;
use App\Models\Sales\Quotation;
use Filament\Pages\Dashboard as BaseDashboard;

class AdminDashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'filament.pages.admin-dashboard';
    protected static ?int $navigationSort = -100;

    public static function getNavigationLabel(): string
    {
        return __('filament-panels::pages/dashboard.title');
    }

    public function getTitle(): string
    {
        return __('uiux.dashboard.admin.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user() !== null;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user !== null && ($user->isAdmin() || $user->canReadAcrossBusiness());
    }

    public function mount(): void
    {
        $user = auth()->user();

        if (! $user || $user->isAdmin() || $user->canReadAcrossBusiness()) {
            return;
        }

        $target = match (true) {
            $user->isFinanceStaff() => FinanceDashboard::class,
            $user->isSalesManager(), $user->isSalesStaff() => SalesDashboard::class,
            $user->isCustomerServiceManager(), $user->isCustomerServiceStaff() => CustomerServiceDashboard::class,
            $user->isMarketingManager(), $user->isMarketingStaff() => MarketingDashboard::class,
            default => StaffDashboard::class,
        };

        if ($target::canAccess()) {
            $this->redirect($target::getUrl());
        }
    }

    public function getWidgets(): array
    {
        return [];
    }

    protected function getViewData(): array
    {
        $openStages = [
            OpportunityStage::Discovery->value,
            OpportunityStage::Qualified->value,
            OpportunityStage::Proposal->value,
            OpportunityStage::Negotiation->value,
        ];

        $start = now()->subMonths(5)->startOfMonth();
        $monthRows = Payment::query()->verified()
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

        $funnel = [
            ['label' => __('resource.landing_page_submission.plural'), 'value' => LandingPageSubmission::query()->count()],
            ['label' => __('resource.lead.plural'), 'value' => Lead::query()->count()],
            ['label' => __('enum.qualification.qualified'), 'value' => ContactQualification::query()->whereIn('status', [ContactQualificationStatus::Qualified->value, ContactQualificationStatus::Converted->value])->count()],
            ['label' => __('resource.opportunity.plural'), 'value' => Opportunity::query()->count()],
            ['label' => __('enum.sales.quotation_status.accepted'), 'value' => Quotation::query()->where('status', QuotationStatus::Accepted->value)->count()],
            ['label' => __('enum.sales.payment_status.paid'), 'value' => Payment::query()->verified()->count()],
            ['label' => __('resource.customer.plural'), 'value' => Customer::query()->count()],
        ];

        return [
            'summary' => [
                'new_leads' => Lead::query()->whereDate('created_at', '>=', now()->startOfMonth())->count(),
                'open_opportunities' => Opportunity::query()->whereIn('stage', $openStages)->count(),
                'cash_collected' => (float) Payment::query()->verified()->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfDay()])->sum('amount'),
                'customers' => Customer::query()->count(),
                'pending_payments' => Quotation::query()->where('payment_status', PaymentStatus::PendingVerification->value)->count(),
                'active_campaigns' => MarketingCampaign::query()->where('status', 'active')->count(),
                'unassigned_leads' => Lead::query()->whereNull('assigned_staff_id')->whereNotIn('intake_status', ['closed', 'duplicate', 'spam', 'converted_to_opportunity'])->count(),
            ],
            'funnel' => $funnel,
            'cashflow' => $cashflow,
            'links' => [
                'leads' => LeadResource::canViewAny() ? LeadResource::getUrl() : null,
                'opportunities' => OpportunityResource::canViewAny() ? OpportunityResource::getUrl() : null,
                'payments' => PaymentTrackingResource::canViewAny() ? PaymentTrackingResource::getUrl() : null,
                'campaigns' => MarketingCampaignResource::canViewAny() ? MarketingCampaignResource::getUrl() : null,
                'audit' => AuditLogResource::canViewAny() ? AuditLogResource::getUrl() : null,
                'campaign_report' => CampaignReportPage::canAccess() ? CampaignReportPage::getUrl() : null,
                'revenue_report' => RevenueReportPage::canAccess() ? RevenueReportPage::getUrl() : null,
            ],
        ];
    }


    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = $this->getViewData();
        $filename = 'enterprise-dashboard-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [__('uiux.dashboard.admin.title')]);
            foreach ($data['summary'] as $key => $value) {
                fputcsv($out, [__('uiux.dashboard.admin.'.$key), $value]);
            }
            fputcsv($out, []);
            fputcsv($out, [__('uiux.dashboard.admin.funnel')]);
            fputcsv($out, [__('field.name'), __('uiux.dashboard.common.count')]);
            foreach ($data['funnel'] as $row) {
                fputcsv($out, [$row['label'], $row['value']]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

}
