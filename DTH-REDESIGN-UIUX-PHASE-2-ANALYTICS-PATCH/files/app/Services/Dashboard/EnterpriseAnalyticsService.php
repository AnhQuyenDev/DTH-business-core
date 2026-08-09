<?php

namespace App\Services\Dashboard;

use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\QuotationStatus;
use App\Models\Finance\Payment;
use App\Models\Finance\PaymentRevenueLine;
use App\Models\Marketing\Campaign;
use App\Models\Marketing\LandingPageSubmission;
use App\Models\Marketing\LandingPageView;
use App\Models\Marketing\MarketingCampaign;
use App\Models\Sales\Quotation;
use App\Models\User;
use App\Support\UtmOptions;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class EnterpriseAnalyticsService
{
    public function __construct(
        private readonly DashboardScopeService $scope,
        private readonly AnalyticsPeriodService $periods,
    ) {
    }

    /** @return array<string, mixed> */
    public function overview(User $user, string $period = '30d'): array
    {
        $range = $this->periods->resolve($period);
        $currentPayments = $this->paymentsBetween($user, $range['start'], $range['end']);
        $previousPayments = $this->paymentsBetween($user, $range['previous_start'], $range['previous_end']);

        $current = $this->paymentSummary($currentPayments);
        $previous = $this->paymentSummary($previousPayments);

        $current['new_leads'] = $this->leadsBetween($user, $range['start'], $range['end'])->count();
        $previous['new_leads'] = $this->leadsBetween($user, $range['previous_start'], $range['previous_end'])->count();
        $current['customer_interactions'] = $this->customerInteractionsBetween($user, $range['start'], $range['end']);
        $previous['customer_interactions'] = $this->customerInteractionsBetween($user, $range['previous_start'], $range['previous_end']);

        $quotations = $this->scope->quotations($user);
        $current['pending_verification'] = (float) (clone $quotations)
            ->where('payment_status', PaymentStatus::PendingVerification->value)
            ->sum('grand_total');
        $current['outstanding'] = (float) (clone $quotations)
            ->where('status', QuotationStatus::Accepted->value)
            ->whereIn('payment_status', [PaymentStatus::Unpaid->value, PaymentStatus::PendingVerification->value])
            ->sum('grand_total');

        $trend = $this->revenueTrend($user, $range);
        $sources = $this->sourceRevenue($currentPayments, 7);
        $services = $this->serviceRevenue($currentPayments, 7);
        $campaigns = $this->marketingCampaignPerformance($user, $range, 6);

        return [
            'range' => $range,
            'current' => $current,
            'previous' => $previous,
            'deltas' => [
                'gross_collected' => $this->periods->delta($current['gross_collected'], $previous['gross_collected']),
                'net_revenue' => $this->periods->delta($current['net_revenue'], $previous['net_revenue']),
                'paid_customers' => $this->periods->delta($current['paid_customers'], $previous['paid_customers']),
                'new_leads' => $this->periods->delta($current['new_leads'], $previous['new_leads']),
                'customer_interactions' => $this->periods->delta($current['customer_interactions'], $previous['customer_interactions']),
            ],
            'trend' => $trend,
            'sources' => $sources,
            'services' => $services,
            'campaigns' => $campaigns,
            'insights' => $this->insights($current, $previous, $sources, $services, $campaigns),
        ];
    }

    /** @return array<string, mixed> */
    public function marketing(User $user, string $period = '30d'): array
    {
        $range = $this->periods->resolve($period);
        $landingPageIds = (clone $this->scope->landingPages($user))->select('landing_pages.id');
        $campaigns = $this->scope->marketingCampaigns($user);
        $payments = $this->paymentsBetween($user, $range['start'], $range['end']);
        $previousPayments = $this->paymentsBetween($user, $range['previous_start'], $range['previous_end']);

        $views = LandingPageView::query()
            ->whereIn('landing_page_id', clone $landingPageIds)
            ->whereBetween('viewed_at', [$range['start'], $range['end']])
            ->count();
        $previousViews = LandingPageView::query()
            ->whereIn('landing_page_id', clone $landingPageIds)
            ->whereBetween('viewed_at', [$range['previous_start'], $range['previous_end']])
            ->count();

        $submissions = $this->submissionsBetween($user, $range['start'], $range['end'])->count();
        $previousSubmissions = $this->submissionsBetween($user, $range['previous_start'], $range['previous_end'])->count();
        $leads = $this->leadsBetween($user, $range['start'], $range['end'])->count();
        $previousLeads = $this->leadsBetween($user, $range['previous_start'], $range['previous_end'])->count();
        $summary = $this->paymentSummary($payments);
        $previousSummary = $this->paymentSummary($previousPayments);

        $activeCampaigns = (clone $campaigns)->where('status', 'active')->count();
        $budget = (float) (clone $campaigns)
            ->where(function (Builder $query) use ($range): void {
                $query->whereNull('start_date')->orWhereDate('start_date', '<=', $range['end']);
            })
            ->where(function (Builder $query) use ($range): void {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', $range['start']);
            })
            ->sum('budget');

        $summary = array_merge($summary, [
            'views' => $views,
            'submissions' => $submissions,
            'leads' => $leads,
            'active_campaigns' => $activeCampaigns,
            'budget' => $budget,
            'view_to_submission' => $views > 0 ? round(($submissions / $views) * 100, 1) : 0,
            'submission_to_lead' => $submissions > 0 ? round(($leads / $submissions) * 100, 1) : 0,
            'lead_to_paid' => $leads > 0 ? round(($summary['paid_customers'] / $leads) * 100, 1) : 0,
            'roas' => $budget > 0 ? round($summary['net_revenue'] / $budget, 2) : null,
        ]);

        $previousSummary = array_merge($previousSummary, [
            'views' => $previousViews,
            'submissions' => $previousSubmissions,
            'leads' => $previousLeads,
        ]);

        return [
            'range' => $range,
            'summary' => $summary,
            'previous' => $previousSummary,
            'deltas' => [
                'views' => $this->periods->delta($views, $previousViews),
                'submissions' => $this->periods->delta($submissions, $previousSubmissions),
                'leads' => $this->periods->delta($leads, $previousLeads),
                'net_revenue' => $this->periods->delta($summary['net_revenue'], $previousSummary['net_revenue']),
                'paid_customers' => $this->periods->delta($summary['paid_customers'], $previousSummary['paid_customers']),
            ],
            'funnel' => [
                ['label' => __('analytics.landing_views'), 'value' => $views],
                ['label' => __('analytics.submissions'), 'value' => $submissions],
                ['label' => __('analytics.leads'), 'value' => $leads],
                ['label' => __('analytics.paid_customers'), 'value' => $summary['paid_customers']],
            ],
            'trend' => $this->revenueTrend($user, $range),
            'sources' => $this->sourceRevenue($payments, 8),
            'campaigns' => $this->marketingCampaignPerformance($user, $range, 10),
            'email_campaigns' => $this->emailCampaignPerformance($user, $range, 8),
            'landing_pages' => $this->landingPagePerformance($user, $range, 8),
        ];
    }

    /** @return array<string, mixed> */
    public function sales(User $user, string $period = '30d'): array
    {
        $range = $this->periods->resolve($period);
        $quotations = $this->scope->quotations($user);
        $payments = $this->paymentsBetween($user, $range['start'], $range['end']);
        $previousPayments = $this->paymentsBetween($user, $range['previous_start'], $range['previous_end']);

        $sent = (clone $quotations)->whereBetween('sent_at', [$range['start'], $range['end']])->count();
        $viewed = (clone $quotations)->whereBetween('first_viewed_at', [$range['start'], $range['end']])->count();
        $accepted = (clone $quotations)->whereBetween('accepted_at', [$range['start'], $range['end']])->count();
        $previousAccepted = (clone $quotations)->whereBetween('accepted_at', [$range['previous_start'], $range['previous_end']])->count();
        $paymentSummary = $this->paymentSummary($payments);
        $previousSummary = $this->paymentSummary($previousPayments);

        return [
            'range' => $range,
            'summary' => array_merge($paymentSummary, [
                'sent_quotations' => $sent,
                'viewed_quotations' => $viewed,
                'accepted_quotations' => $accepted,
                'acceptance_rate' => $sent > 0 ? round(($accepted / $sent) * 100, 1) : 0,
            ]),
            'deltas' => [
                'net_revenue' => $this->periods->delta($paymentSummary['net_revenue'], $previousSummary['net_revenue']),
                'paid_customers' => $this->periods->delta($paymentSummary['paid_customers'], $previousSummary['paid_customers']),
                'accepted_quotations' => $this->periods->delta($accepted, $previousAccepted),
            ],
            'funnel' => [
                ['label' => __('analytics.quotation_sent'), 'value' => $sent],
                ['label' => __('analytics.quotation_viewed'), 'value' => $viewed],
                ['label' => __('analytics.quotation_accepted'), 'value' => $accepted],
                ['label' => __('analytics.paid'), 'value' => $paymentSummary['payments']],
            ],
            'trend' => $this->revenueTrend($user, $range),
            'services' => $this->serviceRevenue($payments, 8),
            'sales_staff' => $this->salesStaffRevenue($payments, 8),
        ];
    }

    /** @return array<string, mixed> */
    public function finance(User $user, string $period = '30d'): array
    {
        $range = $this->periods->resolve($period);
        $payments = $this->paymentsBetween($user, $range['start'], $range['end']);
        $previousPayments = $this->paymentsBetween($user, $range['previous_start'], $range['previous_end']);
        $summary = $this->paymentSummary($payments);
        $previous = $this->paymentSummary($previousPayments);
        $quotations = $this->scope->quotations($user);

        $pendingAmount = (float) (clone $quotations)
            ->where('payment_status', PaymentStatus::PendingVerification->value)
            ->sum('grand_total');
        $outstanding = (float) (clone $quotations)
            ->where('status', QuotationStatus::Accepted->value)
            ->whereIn('payment_status', [PaymentStatus::Unpaid->value, PaymentStatus::PendingVerification->value])
            ->sum('grand_total');

        $verificationMinutes = (clone $payments)
            ->with('paymentNotice:id,submitted_at')
            ->get(['id', 'payment_notice_id', 'verified_at'])
            ->filter(fn (Payment $payment): bool => $payment->verified_at !== null && $payment->paymentNotice?->submitted_at !== null)
            ->map(fn (Payment $payment): int => $payment->paymentNotice->submitted_at->diffInMinutes($payment->verified_at))
            ->values();

        $summary['pending_verification'] = $pendingAmount;
        $summary['outstanding'] = $outstanding;
        $summary['avg_verification_minutes'] = $verificationMinutes->isNotEmpty() ? round((float) $verificationMinutes->avg(), 1) : 0;

        return [
            'range' => $range,
            'summary' => $summary,
            'previous' => $previous,
            'deltas' => [
                'gross_collected' => $this->periods->delta($summary['gross_collected'], $previous['gross_collected']),
                'net_revenue' => $this->periods->delta($summary['net_revenue'], $previous['net_revenue']),
                'payments' => $this->periods->delta($summary['payments'], $previous['payments']),
            ],
            'trend' => $this->revenueTrend($user, $range),
            'aging' => $this->outstandingAging($user),
            'sources' => $this->sourceRevenue($payments, 6),
        ];
    }

    /** @return array<string, mixed> */
    public function campaignAnalysis(User $user, string $period = '30d'): array
    {
        $range = $this->periods->resolve($period);
        $payments = $this->paymentsBetween($user, $range['start'], $range['end']);

        return [
            'range' => $range,
            'marketing_campaigns' => $this->marketingCampaignPerformance($user, $range, 20),
            'email_campaigns' => $this->emailCampaignPerformance($user, $range, 20),
            'sources' => $this->sourceRevenue($payments, 12),
            'landing_pages' => $this->landingPagePerformance($user, $range, 12),
            'trend' => $this->revenueTrend($user, $range),
        ];
    }

    /** @return Builder<Payment> */
    public function paymentsBetween(User $user, Carbon $start, Carbon $end): Builder
    {
        return $this->scope->payments($user)
            ->verified()
            ->whereBetween('paid_at', [$start, $end]);
    }

    /** @return Builder */
    private function leadsBetween(User $user, Carbon $start, Carbon $end): Builder
    {
        return $this->scope->leads($user)->whereBetween('created_at', [$start, $end]);
    }

    /** @return Builder */
    private function submissionsBetween(User $user, Carbon $start, Carbon $end): Builder
    {
        return $this->scope->submissions($user)->whereBetween('submitted_at', [$start, $end]);
    }

    private function customerInteractionsBetween(User $user, Carbon $start, Carbon $end): int
    {
        if ($user->isAdmin() || $user->canReadAcrossBusiness()) {
            return \App\Models\Crm\CustomerInteraction::query()->whereBetween('interaction_at', [$start, $end])->count();
        }

        if ($user->isCustomerServiceManager() && $user->staff?->department_id) {
            return \App\Models\Crm\CustomerInteraction::query()
                ->whereHas('staff', fn (Builder $q): Builder => $q->where('department_id', $user->staff->department_id))
                ->whereBetween('interaction_at', [$start, $end])
                ->count();
        }

        if ($user->isCustomerServiceStaff() && $user->staff) {
            return \App\Models\Crm\CustomerInteraction::query()
                ->where('staff_id', $user->staff->id)
                ->whereBetween('interaction_at', [$start, $end])
                ->count();
        }

        return 0;
    }

    /** @param Builder<Payment> $payments */
    private function paymentSummary(Builder $payments): array
    {
        $row = (clone $payments)
            ->selectRaw('COALESCE(SUM(amount), 0) as gross, COALESCE(SUM(net_amount), 0) as net, COALESCE(SUM(tax_amount), 0) as tax, COUNT(*) as payments, COUNT(DISTINCT customer_id) as customers')
            ->first();
        $count = (int) ($row?->payments ?? 0);
        $gross = (float) ($row?->gross ?? 0);

        return [
            'gross_collected' => $gross,
            'net_revenue' => (float) ($row?->net ?? 0),
            'tax' => (float) ($row?->tax ?? 0),
            'payments' => $count,
            'paid_customers' => (int) ($row?->customers ?? 0),
            'average_payment' => $count > 0 ? $gross / $count : 0,
        ];
    }

    /** @return array{current: array<int, array{label: string, value: float}>, previous: array<int, array{label: string, value: float}>} */
    private function revenueTrend(User $user, array $range): array
    {
        $currentBuckets = $this->periods->buckets($range['start'], $range['end']);
        $previousBuckets = $this->periods->buckets($range['previous_start'], $range['previous_end'], count($currentBuckets));
        $current = [];
        $previous = [];

        foreach ($currentBuckets as $bucket) {
            $current[] = [
                'label' => $bucket['label'],
                'value' => (float) $this->paymentsBetween($user, $bucket['start'], $bucket['end'])->sum('net_amount'),
            ];
        }

        foreach ($previousBuckets as $index => $bucket) {
            $previous[] = [
                'label' => $current[$index]['label'] ?? $bucket['label'],
                'value' => (float) $this->paymentsBetween($user, $bucket['start'], $bucket['end'])->sum('net_amount'),
            ];
        }

        return ['current' => $current, 'previous' => $previous];
    }

    /** @param Builder<Payment> $payments */
    private function sourceRevenue(Builder $payments, int $limit): array
    {
        $sourceOptions = UtmOptions::source();

        return (clone $payments)
            ->with('attribution')
            ->get(['id', 'net_amount'])
            ->groupBy(fn (Payment $payment): string => (string) (
                $payment->attribution?->utm_source
                ?: $payment->attribution?->acquisition_source
                ?: 'unknown'
            ))
            ->map(function (Collection $rows, string $source) use ($sourceOptions): array {
                return [
                    'key' => $source,
                    'label' => $sourceOptions[$source] ?? str($source)->headline()->toString(),
                    'value' => (float) $rows->sum('net_amount'),
                    'count' => $rows->count(),
                ];
            })
            ->sortByDesc('value')
            ->values()
            ->take($limit)
            ->all();
    }

    /** @param Builder<Payment> $payments */
    private function serviceRevenue(Builder $payments, int $limit): array
    {
        return PaymentRevenueLine::query()
            ->whereIn('payment_id', (clone $payments)->select('payments.id'))
            ->groupBy('service_id', 'service_name_snapshot')
            ->selectRaw('service_id, service_name_snapshot as name, COUNT(DISTINCT payment_id) as payments_count, SUM(net_amount) as net_revenue, SUM(gross_amount) as gross_collected')
            ->orderByDesc('net_revenue')
            ->limit($limit)
            ->get()
            ->map(fn (PaymentRevenueLine $row): array => [
                'label' => $row->name ?: __('common.not_available'),
                'value' => (float) $row->net_revenue,
                'gross' => (float) $row->gross_collected,
                'count' => (int) $row->payments_count,
            ])
            ->all();
    }

    /** @param Builder<Payment> $payments */
    private function salesStaffRevenue(Builder $payments, int $limit): array
    {
        return (clone $payments)
            ->with('salesStaff:id,full_name')
            ->get(['id', 'sales_staff_id', 'net_amount', 'customer_id'])
            ->groupBy('sales_staff_id')
            ->map(function (Collection $rows): array {
                $staff = $rows->first()?->salesStaff;

                return [
                    'label' => $staff?->full_name ?: __('common.not_available'),
                    'value' => (float) $rows->sum('net_amount'),
                    'count' => $rows->pluck('customer_id')->filter()->unique()->count(),
                ];
            })
            ->sortByDesc('value')
            ->values()
            ->take($limit)
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function marketingCampaignPerformance(User $user, array $range, int $limit): array
    {
        $campaigns = $this->scope->marketingCampaigns($user)
            ->orderByDesc('start_date')
            ->orderByDesc('created_at')
            ->limit(max($limit * 2, 20))
            ->get();

        return $campaigns
            ->map(function (MarketingCampaign $campaign) use ($user, $range): array {
                $submissions = $this->submissionsBetween($user, $range['start'], $range['end'])
                    ->where('marketing_campaign_id', $campaign->id)
                    ->count();
                $leads = $this->leadsBetween($user, $range['start'], $range['end'])
                    ->whereHas('submission', fn (Builder $q): Builder => $q->where('marketing_campaign_id', $campaign->id))
                    ->count();
                $payments = $this->paymentsBetween($user, $range['start'], $range['end'])
                    ->whereHas('attribution', fn (Builder $q): Builder => $q->where('marketing_campaign_id', $campaign->id));
                $summary = $this->paymentSummary($payments);
                $budget = (float) ($campaign->budget ?? 0);

                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'budget' => $budget,
                    'submissions' => $submissions,
                    'leads' => $leads,
                    'paid_customers' => $summary['paid_customers'],
                    'net_revenue' => $summary['net_revenue'],
                    'conversion_rate' => $leads > 0 ? round(($summary['paid_customers'] / $leads) * 100, 1) : 0,
                    'roas' => $budget > 0 ? round($summary['net_revenue'] / $budget, 2) : null,
                ];
            })
            ->filter(fn (array $row): bool => $row['status'] === 'active' || $row['submissions'] > 0 || $row['leads'] > 0 || $row['net_revenue'] > 0)
            ->sortByDesc('net_revenue')
            ->take($limit)
            ->values()
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function emailCampaignPerformance(User $user, array $range, int $limit): array
    {
        $campaigns = Campaign::query();
        if (! ($user->isAdmin() || $user->canReadAcrossBusiness() || $user->isMarketingManager())) {
            $campaigns->where('created_by', $user->id);
        }

        return $campaigns
            ->where(function (Builder $q) use ($range): void {
                $q->whereBetween('sent_at', [$range['start'], $range['end']])
                    ->orWhereBetween('created_at', [$range['start'], $range['end']]);
            })
            ->with('recipients')
            ->orderByDesc('sent_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(function (Campaign $campaign) use ($user, $range): array {
                $recipients = $campaign->recipients;
                $total = $recipients->count();
                $sent = $recipients->whereNotNull('sent_at')->count();
                $opened = $recipients->whereNotNull('opened_at')->count();
                $clicked = $recipients->whereNotNull('clicked_at')->count();
                $payments = $this->paymentsBetween($user, $range['start'], $range['end'])
                    ->whereHas('attribution', fn (Builder $q): Builder => $q->where('email_campaign_id', $campaign->id));
                $summary = $this->paymentSummary($payments);

                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'recipients' => $total,
                    'sent' => $sent,
                    'opened' => $opened,
                    'clicked' => $clicked,
                    'open_rate' => $sent > 0 ? round(($opened / $sent) * 100, 1) : 0,
                    'click_rate' => $sent > 0 ? round(($clicked / $sent) * 100, 1) : 0,
                    'paid_customers' => $summary['paid_customers'],
                    'net_revenue' => $summary['net_revenue'],
                ];
            })
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function landingPagePerformance(User $user, array $range, int $limit): array
    {
        $pages = $this->scope->landingPages($user)
            ->withCount([
                'views as period_views' => fn (Builder $q): Builder => $q->whereBetween('viewed_at', [$range['start'], $range['end']]),
                'submissions as period_submissions' => fn (Builder $q): Builder => $q->whereBetween('submitted_at', [$range['start'], $range['end']]),
            ])
            ->orderByDesc('period_submissions')
            ->limit($limit)
            ->get();

        return $pages->map(function ($page) use ($user, $range): array {
            $leads = $this->leadsBetween($user, $range['start'], $range['end'])
                ->whereHas('submission', fn (Builder $q): Builder => $q->where('landing_page_id', $page->id))
                ->count();
            $payments = $this->paymentsBetween($user, $range['start'], $range['end'])
                ->whereHas('attribution', fn (Builder $q): Builder => $q->where('landing_page_id', $page->id));
            $summary = $this->paymentSummary($payments);
            $views = (int) $page->period_views;
            $submissions = (int) $page->period_submissions;

            return [
                'name' => $page->name,
                'views' => $views,
                'submissions' => $submissions,
                'leads' => $leads,
                'paid_customers' => $summary['paid_customers'],
                'net_revenue' => $summary['net_revenue'],
                'conversion_rate' => $views > 0 ? round(($submissions / $views) * 100, 1) : 0,
            ];
        })->all();
    }

    /** @return array<int, array{label: string, value: float, count: int}> */
    private function outstandingAging(User $user): array
    {
        $rows = $this->scope->quotations($user)
            ->where('status', QuotationStatus::Accepted->value)
            ->whereIn('payment_status', [PaymentStatus::Unpaid->value, PaymentStatus::PendingVerification->value])
            ->get(['grand_total', 'accepted_at', 'created_at']);

        $buckets = [
            '0_7' => ['label' => __('analytics.aging_0_7'), 'value' => 0.0, 'count' => 0],
            '8_30' => ['label' => __('analytics.aging_8_30'), 'value' => 0.0, 'count' => 0],
            '31_plus' => ['label' => __('analytics.aging_31_plus'), 'value' => 0.0, 'count' => 0],
        ];

        foreach ($rows as $quotation) {
            $date = $quotation->accepted_at ?: $quotation->created_at;
            $days = $date?->diffInDays(now()) ?? 0;
            $key = $days <= 7 ? '0_7' : ($days <= 30 ? '8_30' : '31_plus');
            $buckets[$key]['value'] += (float) $quotation->grand_total;
            $buckets[$key]['count']++;
        }

        return array_values($buckets);
    }

    /** @return array<int, array{tone: string, title: string, text: string}> */
    private function insights(array $current, array $previous, array $sources, array $services, array $campaigns): array
    {
        $rows = [];
        $revenueDelta = $this->periods->delta($current['net_revenue'], $previous['net_revenue']);
        $rows[] = [
            'tone' => $revenueDelta['direction'] === 'down' ? 'warning' : 'success',
            'title' => __('analytics.insight_revenue_title'),
            'text' => __('analytics.insight_revenue_text', ['delta' => $revenueDelta['text']]),
        ];

        if (($sources[0]['label'] ?? null) !== null) {
            $rows[] = [
                'tone' => 'info',
                'title' => __('analytics.insight_source_title'),
                'text' => __('analytics.insight_source_text', [
                    'source' => $sources[0]['label'],
                    'revenue' => number_format((float) $sources[0]['value'], 0, ',', '.').' ₫',
                ]),
            ];
        }

        if (($services[0]['label'] ?? null) !== null) {
            $rows[] = [
                'tone' => 'primary',
                'title' => __('analytics.insight_service_title'),
                'text' => __('analytics.insight_service_text', [
                    'service' => $services[0]['label'],
                    'revenue' => number_format((float) $services[0]['value'], 0, ',', '.').' ₫',
                ]),
            ];
        }

        if (($campaigns[0]['name'] ?? null) !== null) {
            $roas = $campaigns[0]['roas'];
            $rows[] = [
                'tone' => 'marketing',
                'title' => __('analytics.insight_campaign_title'),
                'text' => __('analytics.insight_campaign_text', [
                    'campaign' => $campaigns[0]['name'],
                    'roas' => $roas === null ? '—' : number_format((float) $roas, 2).'x',
                ]),
            ];
        }

        return $rows;
    }
}
