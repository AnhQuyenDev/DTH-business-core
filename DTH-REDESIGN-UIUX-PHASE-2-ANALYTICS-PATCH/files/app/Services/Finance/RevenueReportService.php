<?php

namespace App\Services\Finance;

use App\Enums\Sales\PaymentNoticeStatus;
use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\QuotationStatus;
use App\Models\Finance\Payment;
use App\Models\Finance\PaymentAttribution;
use App\Models\Finance\PaymentRevenueLine;
use App\Models\User;
use App\Services\Dashboard\DashboardScopeService;
use App\Models\Sales\Quotation;
use App\Models\Sales\QuotationPaymentNotice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class RevenueReportService
{
    /**
     * @param array<string, mixed> $filters
     */
    public function paymentQuery(array $filters, ?User $viewer = null): Builder
    {
        $query = $viewer
            ? app(DashboardScopeService::class)->payments($viewer)->verified()
            : Payment::query()->verified();

        [$start, $end] = $this->dateRange($filters);
        $query->whereBetween('paid_at', [$start, $end]);

        if (filled($filters['marketing_campaign_id'] ?? null)) {
            $query->whereHas('attribution', fn (Builder $q): Builder => $q->where(
                'marketing_campaign_id',
                (int) $filters['marketing_campaign_id'],
            ));
        }

        if (filled($filters['email_campaign_id'] ?? null)) {
            $query->whereHas('attribution', fn (Builder $q): Builder => $q->where(
                'email_campaign_id',
                (int) $filters['email_campaign_id'],
            ));
        }

        if (filled($filters['landing_page_id'] ?? null)) {
            $query->whereHas('attribution', fn (Builder $q): Builder => $q->where(
                'landing_page_id',
                (int) $filters['landing_page_id'],
            ));
        }

        if (filled($filters['utm_source'] ?? null)) {
            $source = trim((string) $filters['utm_source']);
            $query->whereHas('attribution', function (Builder $q) use ($source): Builder {
                return $q->where('utm_source', $source)
                    ->orWhere(function (Builder $nested) use ($source): void {
                        $nested->whereNull('utm_source')
                            ->where('acquisition_source', $source);
                    });
            });
        }

        foreach (['utm_medium', 'utm_campaign', 'utm_content'] as $utmField) {
            if (filled($filters[$utmField] ?? null)) {
                $value = trim((string) $filters[$utmField]);
                $query->whereHas('attribution', fn (Builder $q): Builder => $q->where($utmField, $value));
            }
        }

        if (filled($filters['service_id'] ?? null)) {
            $query->whereHas('revenueLines', fn (Builder $q): Builder => $q->where(
                'service_id',
                (int) $filters['service_id'],
            ));
        }

        if (filled($filters['service_package_id'] ?? null)) {
            $query->whereHas('revenueLines', fn (Builder $q): Builder => $q->where(
                'service_package_id',
                (int) $filters['service_package_id'],
            ));
        }

        if (filled($filters['sales_staff_id'] ?? null)) {
            $query->where('sales_staff_id', (int) $filters['sales_staff_id']);
        }

        return $query;
    }

    /** @param array<string, mixed> $filters */
    public function summary(array $filters, ?User $viewer = null): array
    {
        $base = $this->paymentQuery($filters, $viewer);
        $financial = $this->financialRows($filters, $viewer);
        $amounts = DB::query()->fromSub($financial, 'f')
            ->selectRaw('COALESCE(SUM(f.gross_amount), 0) as gross, COALESCE(SUM(f.net_amount), 0) as net, COALESCE(SUM(f.tax_amount), 0) as tax')
            ->first();
        $gross = (float) ($amounts->gross ?? 0);
        $net = (float) ($amounts->net ?? 0);
        $tax = (float) ($amounts->tax ?? 0);
        $payments = (clone $base)->count();
        $customers = (clone $base)->whereNotNull('customer_id')->distinct('customer_id')->count('customer_id');

        [$start, $end] = $this->dateRange($filters);

        $quotationScope = $viewer
            ? app(DashboardScopeService::class)->quotations($viewer)
            : Quotation::query();

        $pending = (float) QuotationPaymentNotice::query()
            ->whereIn('quotation_id', (clone $quotationScope)->select('quotations.id'))
            ->where('status', PaymentNoticeStatus::Pending->value)
            ->whereBetween('submitted_at', [$start, $end])
            ->sum('declared_amount');

        $outstanding = (float) (clone $quotationScope)
            ->where('status', QuotationStatus::Accepted->value)
            ->whereIn('payment_status', [
                PaymentStatus::Unpaid->value,
                PaymentStatus::PendingVerification->value,
            ])
            ->sum('grand_total');

        return [
            'gross_collected' => $gross,
            'net_revenue' => $net,
            'tax' => $tax,
            'payments' => $payments,
            'customers' => $customers,
            'average_payment' => $payments > 0 ? $gross / $payments : 0,
            'pending_verification' => $pending,
            'outstanding_accepted' => $outstanding,
        ];
    }

    /** @param array<string, mixed> $filters */
    public function marketingCampaigns(array $filters, ?User $viewer = null): array
    {
        [$start, $end] = $this->dateRange($filters);

        $rows = DB::query()
            ->fromSub($this->financialRows($filters, $viewer), 'f')
            ->join('payment_attributions', 'payment_attributions.payment_id', '=', 'f.payment_id')
            ->leftJoin('marketing_campaigns', 'marketing_campaigns.id', '=', 'payment_attributions.marketing_campaign_id')
            ->whereNotNull('payment_attributions.marketing_campaign_id')
            ->groupBy('payment_attributions.marketing_campaign_id', 'marketing_campaigns.name', 'marketing_campaigns.budget')
            ->selectRaw('payment_attributions.marketing_campaign_id as id, marketing_campaigns.name as name, marketing_campaigns.budget as budget, COUNT(DISTINCT f.payment_id) as payments_count, COUNT(DISTINCT f.customer_id) as paid_customers, SUM(f.net_amount) as net_revenue, SUM(f.gross_amount) as gross_collected')
            ->orderByDesc('net_revenue')
            ->limit(50)
            ->get();

        $ids = $rows->pluck('id')->filter()->all();
        $leadCounts = empty($ids) ? collect() : DB::table('leads')
            ->join('landing_page_submissions', 'landing_page_submissions.id', '=', 'leads.submission_id')
            ->whereIn('landing_page_submissions.marketing_campaign_id', $ids)
            ->whereBetween('leads.created_at', [$start, $end])
            ->groupBy('landing_page_submissions.marketing_campaign_id')
            ->selectRaw('landing_page_submissions.marketing_campaign_id as id, COUNT(*) as lead_count')
            ->pluck('lead_count', 'id');

        return $rows->map(function ($row) use ($leadCounts): array {
            $budget = (float) ($row->budget ?? 0);
            $net = (float) $row->net_revenue;
            $paid = (int) $row->paid_customers;
            $leads = (int) ($leadCounts[$row->id] ?? 0);

            return [
                'name' => $row->name ?: __('finance.report.campaign').' #'.$row->id,
                'budget' => $budget,
                'leads' => $leads,
                'payments' => (int) $row->payments_count,
                'paid_customers' => $paid,
                'net_revenue' => $net,
                'gross_collected' => (float) $row->gross_collected,
                'conversion_rate' => $leads > 0 ? round(($paid / $leads) * 100, 2) : 0,
                'cac' => $budget > 0 && $paid > 0 ? $budget / $paid : null,
                'roas' => $budget > 0 ? $net / $budget : null,
            ];
        })->all();
    }

    /** @param array<string, mixed> $filters */
    public function emailCampaigns(array $filters, ?User $viewer = null): array
    {
        return DB::query()
            ->fromSub($this->financialRows($filters, $viewer), 'f')
            ->join('payment_attributions', 'payment_attributions.payment_id', '=', 'f.payment_id')
            ->leftJoin('campaigns', 'campaigns.id', '=', 'payment_attributions.email_campaign_id')
            ->whereNotNull('payment_attributions.email_campaign_id')
            ->groupBy('payment_attributions.email_campaign_id', 'campaigns.name')
            ->selectRaw('campaigns.name as name, COUNT(DISTINCT f.payment_id) as payments_count, COUNT(DISTINCT f.customer_id) as paid_customers, SUM(f.net_amount) as net_revenue, SUM(f.gross_amount) as gross_collected')
            ->orderByDesc('net_revenue')
            ->limit(50)
            ->get()
            ->map(fn ($row): array => [
                'name' => $row->name ?: __('finance.report.email_campaign'),
                'payments' => (int) $row->payments_count,
                'paid_customers' => (int) $row->paid_customers,
                'net_revenue' => (float) $row->net_revenue,
                'gross_collected' => (float) $row->gross_collected,
            ])->all();
    }

    /** @param array<string, mixed> $filters */
    public function sources(array $filters, ?User $viewer = null): array
    {
        $sourceExpr = "COALESCE(NULLIF(payment_attributions.utm_source, ''), NULLIF(payment_attributions.acquisition_source, ''), 'unknown')";

        return DB::query()
            ->fromSub($this->financialRows($filters, $viewer), 'f')
            ->join('payment_attributions', 'payment_attributions.payment_id', '=', 'f.payment_id')
            ->groupBy(DB::raw($sourceExpr))
            ->selectRaw("{$sourceExpr} as source, COUNT(DISTINCT f.payment_id) as payments_count, COUNT(DISTINCT f.customer_id) as paid_customers, SUM(f.net_amount) as net_revenue, SUM(f.gross_amount) as gross_collected")
            ->orderByDesc('net_revenue')
            ->limit(50)
            ->get()
            ->map(fn ($row): array => [
                'name' => $row->source,
                'payments' => (int) $row->payments_count,
                'paid_customers' => (int) $row->paid_customers,
                'net_revenue' => (float) $row->net_revenue,
                'gross_collected' => (float) $row->gross_collected,
            ])->all();
    }

    /** @param array<string, mixed> $filters */
    public function utmCampaigns(array $filters, ?User $viewer = null): array
    {
        return DB::query()
            ->fromSub($this->financialRows($filters, $viewer), 'f')
            ->join('payment_attributions', 'payment_attributions.payment_id', '=', 'f.payment_id')
            ->whereNotNull('payment_attributions.utm_campaign')
            ->groupBy('payment_attributions.utm_campaign', 'payment_attributions.utm_content')
            ->selectRaw("payment_attributions.utm_campaign as campaign, COALESCE(payment_attributions.utm_content, '—') as content, COUNT(DISTINCT f.payment_id) as payments_count, COUNT(DISTINCT f.customer_id) as paid_customers, SUM(f.net_amount) as net_revenue, SUM(f.gross_amount) as gross_collected")
            ->orderByDesc('net_revenue')
            ->limit(50)
            ->get()
            ->map(fn ($row): array => [
                'name' => $row->campaign,
                'content' => $row->content,
                'payments' => (int) $row->payments_count,
                'paid_customers' => (int) $row->paid_customers,
                'net_revenue' => (float) $row->net_revenue,
                'gross_collected' => (float) $row->gross_collected,
            ])->all();
    }

    /** @param array<string, mixed> $filters */
    public function landingPages(array $filters, ?User $viewer = null): array
    {
        [$start, $end] = $this->dateRange($filters);

        $rows = DB::query()
            ->fromSub($this->financialRows($filters, $viewer), 'f')
            ->join('payment_attributions', 'payment_attributions.payment_id', '=', 'f.payment_id')
            ->leftJoin('landing_pages', 'landing_pages.id', '=', 'payment_attributions.landing_page_id')
            ->whereNotNull('payment_attributions.landing_page_id')
            ->groupBy('payment_attributions.landing_page_id', 'landing_pages.name')
            ->selectRaw('payment_attributions.landing_page_id as id, landing_pages.name as name, COUNT(DISTINCT f.payment_id) as payments_count, COUNT(DISTINCT f.customer_id) as paid_customers, SUM(f.net_amount) as net_revenue')
            ->orderByDesc('net_revenue')
            ->limit(50)
            ->get();

        $ids = $rows->pluck('id')->filter()->all();
        $leadCounts = empty($ids) ? collect() : DB::table('leads')
            ->join('landing_page_submissions', 'landing_page_submissions.id', '=', 'leads.submission_id')
            ->whereIn('landing_page_submissions.landing_page_id', $ids)
            ->whereBetween('leads.created_at', [$start, $end])
            ->groupBy('landing_page_submissions.landing_page_id')
            ->selectRaw('landing_page_submissions.landing_page_id as id, COUNT(*) as lead_count')
            ->pluck('lead_count', 'id');

        return $rows->map(function ($row) use ($leadCounts): array {
            $leads = (int) ($leadCounts[$row->id] ?? 0);
            $paid = (int) $row->paid_customers;

            return [
                'name' => $row->name ?: __('finance.report.landing_page').' #'.$row->id,
                'leads' => $leads,
                'payments' => (int) $row->payments_count,
                'paid_customers' => $paid,
                'net_revenue' => (float) $row->net_revenue,
                'conversion_rate' => $leads > 0 ? round(($paid / $leads) * 100, 2) : 0,
            ];
        })->all();
    }

    /** @param array<string, mixed> $filters */
    public function services(array $filters, ?User $viewer = null): array
    {
        return $this->lineBreakdown($filters, false, $viewer);
    }

    /** @param array<string, mixed> $filters */
    public function packages(array $filters, ?User $viewer = null): array
    {
        return $this->lineBreakdown($filters, true, $viewer);
    }

    /** @param array<string, mixed> $filters */
    private function lineBreakdown(array $filters, bool $package, ?User $viewer = null): array
    {
        $base = $this->paymentQuery($filters, $viewer);
        $idColumn = $package ? 'service_package_id' : 'service_id';
        $nameColumn = $package ? 'package_name_snapshot' : 'service_name_snapshot';

        $lines = PaymentRevenueLine::query()
            ->whereIn('payment_id', (clone $base)->select('payments.id'));

        if (filled($filters['service_id'] ?? null)) {
            $lines->where('service_id', (int) $filters['service_id']);
        }

        if (filled($filters['service_package_id'] ?? null)) {
            $lines->where('service_package_id', (int) $filters['service_package_id']);
        }

        return $lines
            ->groupBy($idColumn, $nameColumn)
            ->selectRaw("{$idColumn} as entity_id, {$nameColumn} as name, COUNT(DISTINCT payment_id) as payments_count, SUM(net_amount) as net_revenue, SUM(tax_amount) as tax_amount, SUM(gross_amount) as gross_collected")
            ->orderByDesc('net_revenue')
            ->limit(50)
            ->get()
            ->map(fn ($row): array => [
                'name' => $row->name ?: '—',
                'payments' => (int) $row->payments_count,
                'net_revenue' => (float) $row->net_revenue,
                'tax' => (float) $row->tax_amount,
                'gross_collected' => (float) $row->gross_collected,
            ])->all();
    }

    /** @param array<string, mixed> $filters */
    public function sales(array $filters, ?User $viewer = null): array
    {
        return DB::query()
            ->fromSub($this->financialRows($filters, $viewer), 'p')
            ->leftJoin('staff', 'staff.id', '=', 'p.sales_staff_id')
            ->groupBy('p.sales_staff_id', 'staff.full_name')
            ->selectRaw("COALESCE(staff.full_name, '—') as name, COUNT(DISTINCT p.payment_id) as payments_count, COUNT(DISTINCT p.customer_id) as paid_customers, SUM(p.net_amount) as net_revenue, SUM(p.gross_amount) as gross_collected")
            ->orderByDesc('net_revenue')
            ->limit(50)
            ->get()
            ->map(fn ($row): array => [
                'name' => $row->name,
                'payments' => (int) $row->payments_count,
                'paid_customers' => (int) $row->paid_customers,
                'net_revenue' => (float) $row->net_revenue,
                'gross_collected' => (float) $row->gross_collected,
            ])->all();
    }

    /** @param array<string, mixed> $filters */
    public function customers(array $filters, ?User $viewer = null): array
    {
        return DB::query()
            ->fromSub($this->financialRows($filters, $viewer), 'p')
            ->leftJoin('customers', 'customers.id', '=', 'p.customer_id')
            ->whereNotNull('p.customer_id')
            ->groupBy('p.customer_id', 'customers.display_name')
            ->selectRaw('customers.display_name as name, COUNT(DISTINCT p.payment_id) as payments_count, SUM(p.net_amount) as net_revenue, SUM(p.gross_amount) as gross_collected')
            ->orderByDesc('net_revenue')
            ->limit(25)
            ->get()
            ->map(fn ($row): array => [
                'name' => $row->name ?: __('finance.report.customer'),
                'payments' => (int) $row->payments_count,
                'net_revenue' => (float) $row->net_revenue,
                'gross_collected' => (float) $row->gross_collected,
            ])->all();
    }

    /**
     * Return one financial row per Payment after filters. If a Service/Package
     * filter is active, monetary values are reduced to matching revenue lines
     * instead of incorrectly counting the whole multi-service Payment.
     *
     * @param array<string, mixed> $filters
     */
    private function financialRows(array $filters, ?User $viewer = null)
    {
        $base = $this->paymentQuery($filters, $viewer);
        $hasLineFilter = filled($filters['service_id'] ?? null)
            || filled($filters['service_package_id'] ?? null);

        if (! $hasLineFilter) {
            return (clone $base)->selectRaw(
                'payments.id as payment_id, payments.customer_id, payments.sales_staff_id, payments.net_amount, payments.tax_amount, payments.amount as gross_amount'
            );
        }

        $query = DB::table('payment_revenue_lines as lines')
            ->join('payments', 'payments.id', '=', 'lines.payment_id')
            ->whereIn('payments.id', (clone $base)->select('payments.id'));

        if (filled($filters['service_id'] ?? null)) {
            $query->where('lines.service_id', (int) $filters['service_id']);
        }

        if (filled($filters['service_package_id'] ?? null)) {
            $query->where('lines.service_package_id', (int) $filters['service_package_id']);
        }

        return $query
            ->groupBy('payments.id', 'payments.customer_id', 'payments.sales_staff_id')
            ->selectRaw('payments.id as payment_id, payments.customer_id, payments.sales_staff_id, SUM(lines.net_amount) as net_amount, SUM(lines.tax_amount) as tax_amount, SUM(lines.gross_amount) as gross_amount');
    }

    /** @param array<string, mixed> $filters */
    private function dateRange(array $filters): array
    {
        $start = filled($filters['start_date'] ?? null)
            ? Carbon::parse((string) $filters['start_date'])->startOfDay()
            : now()->startOfMonth();
        $end = filled($filters['end_date'] ?? null)
            ? Carbon::parse((string) $filters['end_date'])->endOfDay()
            : now()->endOfDay();

        return [$start, $end];
    }
}
