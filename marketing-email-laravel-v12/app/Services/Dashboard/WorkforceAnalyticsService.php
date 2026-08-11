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
        $rows = $staff->map(fn (Staff $member): array => $this->staffMetrics(
            $member,
            $range,
            $resolvedFunction,
        ))->values();
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

    /** @return array<string, mixed> */
    private function staffMetrics(
        Staff $staff,
        array $range,
        ?DepartmentFunction $businessFunction = null,
    ): array {
        $function = $businessFunction?->value
            ?: ($staff->primaryBusinessFunction()?->value
                ?: ($staff->department?->function_key ?: 'other'));

        return match ($function) {
            'marketing' => $this->marketingMetrics($staff, $range),
            'customer_service' => $this->customerServiceMetrics($staff, $range),
            'sales' => $this->salesMetrics($staff, $range),
            'finance' => $this->financeMetrics($staff, $range),
            default => $this->genericMetrics($staff, $range),
        };
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

    /** @return array<string, mixed> */
    private function marketingMetrics(Staff $staff, array $range): array
    {
        $row = $this->base($staff, 'marketing');
        $userId = $staff->user_id;
        $campaigns = MarketingCampaign::query()->where('created_by', $userId)->whereBetween('created_at', [$range['start'], $range['end']])->count();
        $emailCampaigns = Campaign::query()->where('created_by', $userId)->whereBetween('created_at', [$range['start'], $range['end']])->count();
        $landingPages = LandingPage::query()->where('created_by', $userId)->whereBetween('created_at', [$range['start'], $range['end']])->count();
        $submissions = LandingPageSubmission::query()
            ->whereHas('landingPage', fn (Builder $q): Builder => $q->where('created_by', $userId))
            ->whereBetween('submitted_at', [$range['start'], $range['end']])
            ->count();
        $leads = Lead::query()
            ->whereHas('submission.landingPage', fn (Builder $q): Builder => $q->where('created_by', $userId))
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->count();
        $payments = Payment::query()->verified()
            ->whereBetween('paid_at', [$range['start'], $range['end']])
            ->where(function (Builder $q) use ($userId): void {
                $q->whereHas('attribution.landingPage', fn (Builder $sub): Builder => $sub->where('created_by', $userId))
                    ->orWhereHas('attribution.marketingCampaign', fn (Builder $sub): Builder => $sub->where('created_by', $userId));
            });
        $revenue = (float) (clone $payments)->sum('net_amount');
        $customers = (clone $payments)->whereNotNull('customer_id')->distinct('customer_id')->count('customer_id');

        return array_merge($row, [
            'activity_count' => $campaigns + $emailCampaigns + $landingPages + $submissions,
            'customers' => $customers,
            'impact_value' => $revenue,
            'metric_1_label' => __('analytics.leads_generated'),
            'metric_1_value' => $leads,
            'metric_2_label' => __('analytics.paid_customers'),
            'metric_2_value' => $customers,
            'metric_3_label' => __('analytics.attributed_revenue'),
            'metric_3_value' => $revenue,
            'highlight' => __('analytics.workforce_marketing_highlight', ['campaigns' => $campaigns + $emailCampaigns, 'landing_pages' => $landingPages]),
            '_score_a' => $revenue,
            '_score_b' => $leads,
            '_score_c' => $customers,
            '_score_d' => $campaigns + $emailCampaigns + $landingPages,
        ]);
    }

    /** @return array<string, mixed> */
    private function customerServiceMetrics(Staff $staff, array $range): array
    {
        $row = $this->base($staff, 'customer_service');

        $interactions = CustomerInteraction::query()
            ->where('staff_id', $staff->id)
            ->whereBetween('interaction_at', [$range['start'], $range['end']]);
        $interactionCount = (clone $interactions)->count();
        $customers = (clone $interactions)->distinct('customer_id')->count('customer_id');

        $tickets = SupportTicket::query()->where('assigned_staff_id', $staff->id);
        $ticketActivity = (clone $tickets)
            ->whereBetween('last_activity_at', [$range['start'], $range['end']])
            ->count();
        $resolved = (clone $tickets)
            ->whereNotNull('resolved_at')
            ->whereBetween('resolved_at', [$range['start'], $range['end']])
            ->count();

        $overdue = Customer::query()
            ->whereHas('assignments', fn (Builder $q): Builder => $q
                ->where('staff_id', $staff->id)
                ->where('status', 'active'))
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<', now())
            ->count();

        $customerNames = Customer::query()
            ->whereHas('interactions', fn (Builder $q): Builder => $q
                ->where('staff_id', $staff->id)
                ->whereBetween('interaction_at', [$range['start'], $range['end']]))
            ->orderByDesc('updated_at')
            ->limit(3)
            ->pluck('display_name')
            ->filter()
            ->implode(', ');

        return array_merge($row, [
            'activity_count' => $interactionCount + $ticketActivity,
            'customers' => $customers,
            'impact_value' => (float) $resolved,
            'metric_1_label' => __('analytics.customer_interactions'),
            'metric_1_value' => $interactionCount,
            'metric_2_label' => __('v1.analytics.customers_cared'),
            'metric_2_value' => $customers,
            'metric_3_label' => __('v1.analytics.tickets_resolved'),
            'metric_3_value' => $resolved,
            'highlight' => $customerNames !== ''
                ? __('analytics.workforce_customer_highlight', ['customers' => $customerNames])
                : __('analytics.no_customer_activity'),
            'overdue' => $overdue,
            '_score_a' => $interactionCount,
            '_score_b' => $resolved,
            '_score_c' => $customers,
            '_score_d' => $ticketActivity,
        ]);
    }

    /** @return array<string, mixed> */
    private function salesMetrics(Staff $staff, array $range): array
    {
        $row = $this->base($staff, 'sales');
        $quotations = Quotation::query()
            ->where('assigned_staff_id', $staff->id)
            ->whereBetween('created_at', [$range['start'], $range['end']]);
        $quotationCount = (clone $quotations)->count();
        $accepted = Quotation::query()
            ->where('assigned_staff_id', $staff->id)
            ->whereBetween('accepted_at', [$range['start'], $range['end']])
            ->count();
        $payments = Payment::query()->verified()
            ->where('sales_staff_id', $staff->id)
            ->whereBetween('paid_at', [$range['start'], $range['end']]);
        $revenue = (float) (clone $payments)->sum('net_amount');
        $customers = (clone $payments)->whereNotNull('customer_id')->distinct('customer_id')->count('customer_id');
        $paymentCount = (clone $payments)->count();
        $topServiceRow = PaymentRevenueLine::query()
            ->whereIn('payment_id', (clone $payments)->select('payments.id'))
            ->groupBy('service_name_snapshot')
            ->selectRaw('service_name_snapshot, SUM(net_amount) as total')
            ->orderByDesc('total')
            ->first();
        $topService = $topServiceRow?->service_name_snapshot;

        return array_merge($row, [
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
    }

    /** @return array<string, mixed> */
    private function financeMetrics(Staff $staff, array $range): array
    {
        $row = $this->base($staff, 'finance');
        $payments = Payment::query()->verified()
            ->where('verified_by_user_id', $staff->user_id)
            ->whereBetween('verified_at', [$range['start'], $range['end']]);
        $collection = (clone $payments)->with('paymentNotice:id,submitted_at')->get(['id', 'amount', 'payment_notice_id', 'verified_at', 'customer_id']);
        $count = $collection->count();
        $amount = (float) $collection->sum('amount');
        $customers = $collection->pluck('customer_id')->filter()->unique()->count();
        $minutes = $collection
            ->filter(fn (Payment $payment): bool => $payment->verified_at !== null && $payment->paymentNotice?->submitted_at !== null)
            ->map(fn (Payment $payment): int => $payment->paymentNotice->submitted_at->diffInMinutes($payment->verified_at));
        $avgMinutes = $minutes->isNotEmpty() ? round((float) $minutes->avg(), 1) : 0;

        return array_merge($row, [
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
    }

    /** @return array<string, mixed> */
    private function genericMetrics(Staff $staff, array $range): array
    {
        $row = $this->base($staff, $staff->department?->function_key ?: 'other');
        $actions = $staff->user_id
            ? $staff->activityLogs()->whereBetween('created_at', [$range['start'], $range['end']])->count()
            : 0;

        return array_merge($row, [
            'activity_count' => $actions,
            'metric_1_label' => __('analytics.system_actions'),
            'metric_1_value' => $actions,
            'highlight' => __('analytics.workforce_generic_highlight'),
            '_score_a' => $actions,
        ]);
    }

    /** @param Collection<int, array<string, mixed>> $rows */
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
