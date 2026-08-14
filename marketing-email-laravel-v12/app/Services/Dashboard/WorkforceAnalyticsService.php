<?php

namespace App\Services\Dashboard;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\DepartmentFunction;
use App\Enums\Crm\StaffEmploymentStatus;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerInteraction;
use App\Models\Crm\Lead;
use App\Models\Crm\Staff;
use App\Models\Finance\Payment;
use App\Models\Finance\PaymentRevenueLine;
use App\Models\Marketing\Campaign;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageSubmission;
use App\Models\Marketing\MarketingCampaign;
use App\Models\Sales\Quotation;
use App\Models\Support\SupportTicket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class WorkforceAnalyticsService
{
    public function __construct(private readonly AnalyticsPeriodService $periods)
    {
    }

    /** @return array<string, mixed> */
    public function report(
        User $viewer,
        string $period = '30d',
        DepartmentFunction|string|null $function = null,
    ): array {
        $range = $this->periods->resolve($period);
        $resolvedFunction = $function instanceof DepartmentFunction
            ? $function
            : (is_string($function) ? DepartmentFunction::tryFrom($function) : null);

        $staffQuery = Staff::query()
            ->with([
                'user:id,name,email,is_active',
                'department:id,name,function_key,color',
                'position:id,title',
                'businessFunctions',
            ])
            ->where('employment_status', StaffEmploymentStatus::Active->value)
            ->whereHas('user', fn (Builder $q): Builder => $q->where('is_active', true));

        if ($resolvedFunction !== null) {
            $staffQuery->withBusinessFunction($resolvedFunction);

            if (! ($viewer->isAdmin() || $viewer->canReadAcrossBusiness())) {
                if (! $viewer->hasBusinessManagerAuthority($resolvedFunction)) {
                    $staffQuery->whereRaw('1 = 0');
                }
            }
        } elseif (! ($viewer->isAdmin() || $viewer->canReadAcrossBusiness())) {
            $managedFunctions = $viewer->managedBusinessFunctions();

            if ($managedFunctions === []) {
                $staffQuery->whereRaw('1 = 0');
            } else {
                $staffQuery->where(function (Builder $query) use ($managedFunctions): void {
                    foreach ($managedFunctions as $function) {
                        $query->orWhere(fn (Builder $subQuery): Builder =>
                            $subQuery->withBusinessFunction($function)
                        );
                    }
                });
            }
        }

        $staff = $staffQuery->orderBy('department_id')->orderBy('full_name')->get();
        $rows = $this->batchedStaffMetrics($staff, $range, $resolvedFunction);
        $rows = $this->withActivityIndex($rows);

        $departments = $rows
            ->groupBy('department_key')
            ->map(function (Collection $members, string $key): array {
                return [
                    'key' => $key,
                    'name' => $members->first()['department'] ?? __('common.not_available'),
                    'staff' => $members->count(),
                    'activity' => (int) $members->sum('activity_count'),
                    'impact_value' => (float) $members->sum('impact_value'),
                    'customers' => (int) $members->sum('customers'),
                    'avg_index' => round((float) $members->avg('activity_index'), 1),
                ];
            })
            ->sortByDesc('impact_value')
            ->values()
            ->all();

        return [
            'range' => $range,
            'summary' => [
                'active_staff' => $rows->count(),
                'departments' => $rows->pluck('department_key')->filter()->unique()->count(),
                'activities' => (int) $rows->sum('activity_count'),
                'revenue_impact' => (float) $rows->sum(fn (array $row): float => in_array($row['department_key'], ['sales', 'marketing'], true) ? $row['impact_value'] : 0),
                'customers_touched' => (int) $rows->sum('customers'),
            ],
            'departments' => $departments,
            'staff' => $rows->sortByDesc('activity_index')->values()->all(),
            'top_staff' => $rows->sortByDesc('activity_index')->take(8)->values()->all(),
        ];
    }

    /**
     * Build every staff row from a fixed set of grouped queries. Query count is
     * bounded by the number of business functions, not the number of staff.
     *
     * @param  Collection<int, Staff>  $staff
     * @return Collection<int, array<string, mixed>>
     */
    private function batchedStaffMetrics(
        Collection $staff,
        array $range,
        ?DepartmentFunction $businessFunction,
    ): Collection {
        $groups = $staff->groupBy(fn (Staff $member): string => $businessFunction?->value
            ?: ($member->primaryBusinessFunction()?->value
                ?: ($member->department?->function_key ?: 'other'))
        );

        return $groups->flatMap(fn (Collection $members, string $function): Collection => match ($function) {
            'marketing' => $this->marketingMetricsBatch($members, $range),
            'customer_service' => $this->customerServiceMetricsBatch($members, $range),
            'sales' => $this->salesMetricsBatch($members, $range),
            'finance' => $this->financeMetricsBatch($members, $range),
            default => $this->genericMetricsBatch($members, $range, $function),
        })->values();
    }

    /** @param Collection<int, Staff> $staff */
    private function marketingMetricsBatch(Collection $staff, array $range): Collection
    {
        $userIds = $staff->pluck('user_id')->filter()->values()->all();
        if ($userIds === []) {
            return $staff->map(fn (Staff $member): array => $this->marketingRow($member));
        }

        $campaigns = MarketingCampaign::query()
            ->whereIn('created_by', $userIds)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->selectRaw('created_by, COUNT(*) as aggregate')
            ->groupBy('created_by')->pluck('aggregate', 'created_by');
        $emailCampaigns = Campaign::query()
            ->whereIn('created_by', $userIds)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->selectRaw('created_by, COUNT(*) as aggregate')
            ->groupBy('created_by')->pluck('aggregate', 'created_by');
        $landingPages = LandingPage::query()
            ->whereIn('created_by', $userIds)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->selectRaw('created_by, COUNT(*) as aggregate')
            ->groupBy('created_by')->pluck('aggregate', 'created_by');
        $submissions = LandingPageSubmission::query()
            ->join('landing_pages as workforce_page', 'workforce_page.id', '=', 'landing_page_submissions.landing_page_id')
            ->whereIn('workforce_page.created_by', $userIds)
            ->whereBetween('landing_page_submissions.submitted_at', [$range['start'], $range['end']])
            ->selectRaw('workforce_page.created_by, COUNT(*) as aggregate')
            ->groupBy('workforce_page.created_by')->pluck('aggregate', 'created_by');
        $leads = Lead::query()
            ->join('landing_page_submissions as workforce_submission', 'workforce_submission.id', '=', 'leads.submission_id')
            ->join('landing_pages as workforce_page', 'workforce_page.id', '=', 'workforce_submission.landing_page_id')
            ->whereIn('workforce_page.created_by', $userIds)
            ->whereBetween('leads.created_at', [$range['start'], $range['end']])
            ->selectRaw('workforce_page.created_by, COUNT(*) as aggregate')
            ->groupBy('workforce_page.created_by')->pluck('aggregate', 'created_by');

        // A payment may legitimately point to both a campaign and a landing
        // page created by different users. UNION keeps the former per-user OR
        // semantics while de-duplicating the same payment/user pair.
        $campaignOwners = DB::table('payment_attributions as workforce_attribution')
            ->join('marketing_campaigns as workforce_campaign', 'workforce_campaign.id', '=', 'workforce_attribution.marketing_campaign_id')
            ->whereIn('workforce_campaign.created_by', $userIds)
            ->selectRaw('workforce_attribution.payment_id, workforce_campaign.created_by as owner_user_id');
        $landingPageOwners = DB::table('payment_attributions as workforce_attribution')
            ->join('landing_pages as workforce_page', 'workforce_page.id', '=', 'workforce_attribution.landing_page_id')
            ->whereIn('workforce_page.created_by', $userIds)
            ->selectRaw('workforce_attribution.payment_id, workforce_page.created_by as owner_user_id');
        $paymentOwners = $campaignOwners->union($landingPageOwners);

        $payments = Payment::query()->verified()
            ->joinSub($paymentOwners, 'workforce_owner', fn ($join) => $join->on('workforce_owner.payment_id', '=', 'payments.id'))
            ->whereBetween('payments.paid_at', [$range['start'], $range['end']])
            ->selectRaw('workforce_owner.owner_user_id')
            ->selectRaw('COALESCE(SUM(payments.net_amount), 0) as revenue, COUNT(DISTINCT payments.customer_id) as customers_count')
            ->groupBy('workforce_owner.owner_user_id')
            ->get()->keyBy('owner_user_id');

        return $staff->map(function (Staff $member) use ($campaigns, $emailCampaigns, $landingPages, $submissions, $leads, $payments): array {
            $userId = $member->user_id;
            $campaignCount = (int) $campaigns->get($userId, 0);
            $emailCampaignCount = (int) $emailCampaigns->get($userId, 0);
            $landingPageCount = (int) $landingPages->get($userId, 0);
            $submissionCount = (int) $submissions->get($userId, 0);
            $leadCount = (int) $leads->get($userId, 0);
            $payment = $payments->get($userId);
            $revenue = (float) ($payment?->revenue ?? 0);
            $customers = (int) ($payment?->customers_count ?? 0);

            return array_merge($this->base($member, 'marketing'), [
                'activity_count' => $campaignCount + $emailCampaignCount + $landingPageCount + $submissionCount,
                'customers' => $customers,
                'impact_value' => $revenue,
                'metric_1_label' => __('analytics.leads_generated'),
                'metric_1_value' => $leadCount,
                'metric_2_label' => __('analytics.paid_customers'),
                'metric_2_value' => $customers,
                'metric_3_label' => __('analytics.attributed_revenue'),
                'metric_3_value' => $revenue,
                'highlight' => __('analytics.workforce_marketing_highlight', ['campaigns' => $campaignCount + $emailCampaignCount, 'landing_pages' => $landingPageCount]),
                '_score_a' => $revenue,
                '_score_b' => $leadCount,
                '_score_c' => $customers,
                '_score_d' => $campaignCount + $emailCampaignCount + $landingPageCount,
            ]);
        });
    }

    private function marketingRow(Staff $staff): array
    {
        return array_merge($this->base($staff, 'marketing'), [
            'metric_1_label' => __('analytics.leads_generated'),
            'metric_2_label' => __('analytics.paid_customers'),
            'metric_3_label' => __('analytics.attributed_revenue'),
            'highlight' => __('analytics.workforce_marketing_highlight', ['campaigns' => 0, 'landing_pages' => 0]),
        ]);
    }

    /** @param Collection<int, Staff> $staff */
    private function customerServiceMetricsBatch(Collection $staff, array $range): Collection
    {
        $staffIds = $staff->pluck('id')->all();
        $interactions = CustomerInteraction::query()
            ->whereIn('staff_id', $staffIds)
            ->whereBetween('interaction_at', [$range['start'], $range['end']])
            ->selectRaw('staff_id, COUNT(*) as interactions_count, COUNT(DISTINCT customer_id) as customers_count')
            ->groupBy('staff_id')->get()->keyBy('staff_id');
        $ticketActivity = SupportTicket::query()
            ->whereIn('assigned_staff_id', $staffIds)
            ->whereBetween('last_activity_at', [$range['start'], $range['end']])
            ->selectRaw('assigned_staff_id, COUNT(*) as aggregate')
            ->groupBy('assigned_staff_id')->pluck('aggregate', 'assigned_staff_id');
        $resolvedTickets = SupportTicket::query()
            ->whereIn('assigned_staff_id', $staffIds)
            ->whereBetween('resolved_at', [$range['start'], $range['end']])
            ->selectRaw('assigned_staff_id, COUNT(*) as aggregate')
            ->groupBy('assigned_staff_id')->pluck('aggregate', 'assigned_staff_id');
        $overdue = Customer::query()
            ->join('customer_assignments as workforce_assignment', 'workforce_assignment.customer_id', '=', 'customers.id')
            ->whereIn('workforce_assignment.staff_id', $staffIds)
            ->where('workforce_assignment.status', 'active')
            ->whereNotNull('customers.next_follow_up_at')
            ->where('customers.next_follow_up_at', '<', now())
            ->selectRaw('workforce_assignment.staff_id, COUNT(DISTINCT customers.id) as aggregate')
            ->groupBy('workforce_assignment.staff_id')->pluck('aggregate', 'staff_id');
        $recentCustomers = CustomerInteraction::query()
            ->join('customers as workforce_customer', 'workforce_customer.id', '=', 'customer_interactions.customer_id')
            ->whereIn('customer_interactions.staff_id', $staffIds)
            ->whereBetween('customer_interactions.interaction_at', [$range['start'], $range['end']])
            ->selectRaw('customer_interactions.staff_id, workforce_customer.id as customer_id, workforce_customer.display_name, MAX(customer_interactions.interaction_at) as last_interaction_at')
            ->groupBy('customer_interactions.staff_id', 'workforce_customer.id', 'workforce_customer.display_name')
            ->orderByDesc('last_interaction_at')
            ->get()->groupBy('staff_id');

        return $staff->map(function (Staff $member) use ($interactions, $ticketActivity, $resolvedTickets, $overdue, $recentCustomers): array {
            $interaction = $interactions->get($member->id);
            $interactionCount = (int) ($interaction?->interactions_count ?? 0);
            $customers = (int) ($interaction?->customers_count ?? 0);
            $tickets = (int) $ticketActivity->get($member->id, 0);
            $resolved = (int) $resolvedTickets->get($member->id, 0);
            $customerNames = $recentCustomers->get($member->id, collect())->pluck('display_name')->filter()->take(3)->implode(', ');

            return array_merge($this->base($member, 'customer_service'), [
                'activity_count' => $interactionCount + $tickets,
                'customers' => $customers,
                'impact_value' => (float) $resolved,
                'metric_1_label' => __('analytics.customer_interactions'),
                'metric_1_value' => $interactionCount,
                'metric_2_label' => __('v1.analytics.customers_cared'),
                'metric_2_value' => $customers,
                'metric_3_label' => __('v1.analytics.tickets_resolved'),
                'metric_3_value' => $resolved,
                'highlight' => $customerNames !== '' ? __('analytics.workforce_customer_highlight', ['customers' => $customerNames]) : __('analytics.no_customer_activity'),
                'overdue' => (int) $overdue->get($member->id, 0),
                '_score_a' => $interactionCount,
                '_score_b' => $resolved,
                '_score_c' => $customers,
                '_score_d' => $tickets,
            ]);
        });
    }

    /** @param Collection<int, Staff> $staff */
    private function salesMetricsBatch(Collection $staff, array $range): Collection
    {
        $staffIds = $staff->pluck('id')->all();
        $quotations = Quotation::query()
            ->whereIn('assigned_staff_id', $staffIds)
            ->where(function (Builder $query) use ($range): void {
                $query->whereBetween('created_at', [$range['start'], $range['end']])
                    ->orWhereBetween('accepted_at', [$range['start'], $range['end']]);
            })
            ->selectRaw('assigned_staff_id')
            ->selectRaw('SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as quotations_count', [$range['start'], $range['end']])
            ->selectRaw('SUM(CASE WHEN accepted_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as accepted_count', [$range['start'], $range['end']])
            ->groupBy('assigned_staff_id')->get()->keyBy('assigned_staff_id');
        $payments = Payment::query()->verified()
            ->whereIn('sales_staff_id', $staffIds)
            ->whereBetween('paid_at', [$range['start'], $range['end']])
            ->selectRaw('sales_staff_id, COUNT(*) as payments_count, COUNT(DISTINCT customer_id) as customers_count, COALESCE(SUM(net_amount), 0) as revenue')
            ->groupBy('sales_staff_id')->get()->keyBy('sales_staff_id');
        $services = PaymentRevenueLine::query()
            ->join('payments as workforce_payment', 'workforce_payment.id', '=', 'payment_revenue_lines.payment_id')
            ->where('workforce_payment.status', Payment::STATUS_VERIFIED)
            ->whereIn('workforce_payment.sales_staff_id', $staffIds)
            ->whereBetween('workforce_payment.paid_at', [$range['start'], $range['end']])
            ->selectRaw('workforce_payment.sales_staff_id, payment_revenue_lines.service_name_snapshot, SUM(payment_revenue_lines.net_amount) as total')
            ->groupBy('workforce_payment.sales_staff_id', 'payment_revenue_lines.service_name_snapshot')
            ->orderByDesc('total')->get()->groupBy('sales_staff_id');

        return $staff->map(function (Staff $member) use ($quotations, $payments, $services): array {
            $quotation = $quotations->get($member->id);
            $payment = $payments->get($member->id);
            $quotationCount = (int) ($quotation?->quotations_count ?? 0);
            $accepted = (int) ($quotation?->accepted_count ?? 0);
            $paymentCount = (int) ($payment?->payments_count ?? 0);
            $customers = (int) ($payment?->customers_count ?? 0);
            $revenue = (float) ($payment?->revenue ?? 0);
            $topService = $services->get($member->id, collect())->first()?->service_name_snapshot;

            return array_merge($this->base($member, 'sales'), [
                'activity_count' => $quotationCount + $accepted + $paymentCount,
                'customers' => $customers,
                'impact_value' => $revenue,
                'metric_1_label' => __('analytics.accepted_quotations'),
                'metric_1_value' => $accepted,
                'metric_2_label' => __('analytics.paid_customers'),
                'metric_2_value' => $customers,
                'metric_3_label' => __('analytics.net_revenue'),
                'metric_3_value' => $revenue,
                'highlight' => $topService ? __('analytics.workforce_sales_highlight', ['service' => $topService]) : __('analytics.no_sales_activity'),
                '_score_a' => $revenue,
                '_score_b' => $customers,
                '_score_c' => $accepted,
                '_score_d' => $quotationCount,
            ]);
        });
    }

    /** @param Collection<int, Staff> $staff */
    private function financeMetricsBatch(Collection $staff, array $range): Collection
    {
        $userIds = $staff->pluck('user_id')->filter()->values()->all();
        $payments = $userIds === [] ? collect() : Payment::query()->verified()
            ->whereIn('verified_by_user_id', $userIds)
            ->whereBetween('verified_at', [$range['start'], $range['end']])
            ->with('paymentNotice:id,submitted_at')
            ->get(['id', 'amount', 'payment_notice_id', 'verified_at', 'verified_by_user_id', 'customer_id'])
            ->groupBy('verified_by_user_id');

        return $staff->map(function (Staff $member) use ($payments): array {
            $collection = $payments->get($member->user_id, collect());
            $count = $collection->count();
            $amount = (float) $collection->sum('amount');
            $customers = $collection->pluck('customer_id')->filter()->unique()->count();
            $minutes = $collection
                ->filter(fn (Payment $payment): bool => $payment->verified_at !== null && $payment->paymentNotice?->submitted_at !== null)
                ->map(fn (Payment $payment): int => $payment->paymentNotice->submitted_at->diffInMinutes($payment->verified_at));
            $avgMinutes = $minutes->isNotEmpty() ? round((float) $minutes->avg(), 1) : 0;

            return array_merge($this->base($member, 'finance'), [
                'activity_count' => $count,
                'customers' => $customers,
                'impact_value' => $amount,
                'metric_1_label' => __('analytics.verified_payments'),
                'metric_1_value' => $count,
                'metric_2_label' => __('analytics.verified_amount'),
                'metric_2_value' => $amount,
                'metric_3_label' => __('analytics.avg_verification_time'),
                'metric_3_value' => $avgMinutes,
                'highlight' => __('analytics.workforce_finance_highlight', ['minutes' => number_format($avgMinutes, 1)]),
                '_score_a' => $amount,
                '_score_b' => $count,
                '_score_c' => $avgMinutes > 0 ? 1 / $avgMinutes : 0,
                '_score_d' => $customers,
            ]);
        });
    }

    /** @param Collection<int, Staff> $staff */
    private function genericMetricsBatch(Collection $staff, array $range, string $function): Collection
    {
        $actions = \App\Models\Marketing\AuditLog::query()
            ->whereIn('user_id', $staff->pluck('user_id')->filter()->values()->all())
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->selectRaw('user_id, COUNT(*) as aggregate')
            ->groupBy('user_id')->pluck('aggregate', 'user_id');

        return $staff->map(function (Staff $member) use ($actions, $function): array {
            $count = (int) $actions->get($member->user_id, 0);

            return array_merge($this->base($member, $function), [
                'activity_count' => $count,
                'metric_1_label' => __('analytics.system_actions'),
                'metric_1_value' => $count,
                'highlight' => __('analytics.workforce_generic_highlight'),
                '_score_a' => $count,
            ]);
        });
    }

    /** @return array<string, mixed> */
    private function base(Staff $staff, string $departmentKey): array
    {
        return [
            'staff_id' => $staff->id,
            'name' => $staff->full_name,
            'employee_code' => $staff->employee_code,
            'department' => $staff->department?->name ?: __('common.not_available'),
            'department_key' => $departmentKey,
            'position' => $staff->position?->title ?: __('common.not_available'),
            'activity_count' => 0,
            'customers' => 0,
            'impact_value' => 0.0,
            'metric_1_label' => __('analytics.activity'),
            'metric_1_value' => 0,
            'metric_2_label' => __('analytics.customers'),
            'metric_2_value' => 0,
            'metric_3_label' => __('analytics.impact'),
            'metric_3_value' => 0,
            'highlight' => '—',
            'overdue' => 0,
            'activity_index' => 0.0,
            '_score_a' => 0.0,
            '_score_b' => 0.0,
            '_score_c' => 0.0,
            '_score_d' => 0.0,
        ];
    }

    private function withActivityIndex(Collection $rows): Collection
    {
        return $rows->groupBy('department_key')->flatMap(function (Collection $members): Collection {
            $maxA = max(1.0, (float) $members->max('_score_a'));
            $maxB = max(1.0, (float) $members->max('_score_b'));
            $maxC = max(1.0, (float) $members->max('_score_c'));
            $maxD = max(1.0, (float) $members->max('_score_d'));
            $key = (string) ($members->first()['department_key'] ?? 'other');

            $weights = match ($key) {
                'sales' => [.55, .20, .15, .10],
                'marketing' => [.40, .30, .20, .10],
                'customer_service' => [.35, .25, .25, .15],
                'finance' => [.55, .25, .15, .05],
                default => [.70, .10, .10, .10],
            };

            return $members->map(function (array $row) use ($maxA, $maxB, $maxC, $maxD, $weights, $key): array {
                $score = (($row['_score_a'] / $maxA) * $weights[0])
                    + (($row['_score_b'] / $maxB) * $weights[1])
                    + (($row['_score_c'] / $maxC) * $weights[2])
                    + (($row['_score_d'] / $maxD) * $weights[3]);

                if ($key === 'customer_service' && ($row['overdue'] ?? 0) > 0) {
                    $score *= max(.75, 1 - min(.25, ((int) $row['overdue']) * .03));
                }

                $row['activity_index'] = round(min(100, max(0, $score * 100)), 1);
                unset($row['_score_a'], $row['_score_b'], $row['_score_c'], $row['_score_d']);

                return $row;
            });
        })->values();
    }
}
