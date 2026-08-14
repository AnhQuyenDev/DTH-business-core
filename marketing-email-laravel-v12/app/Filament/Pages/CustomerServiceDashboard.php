<?php

namespace App\Filament\Pages;

use App\Enums\Support\TicketStatus;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\SupportTicketResource;
use App\Models\Crm\Customer;
use App\Models\Crm\CustomerInteraction;
use App\Models\Support\SupportTicket;
use App\Services\Dashboard\WorkforceAnalyticsService;
use App\Services\Dashboard\DashboardSnapshotCache;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerServiceDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?int $navigationSort = -10;
    protected static string $view = 'filament.pages.customer-service-dashboard';

    public string $period = '30d';

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.customer_care');
    }

    public static function getNavigationLabel(): string
    {
        return __('uiux.dashboard.customer_service.title');
    }

    public function getTitle(): string
    {
        return auth()->user()?->isCustomerServiceManager()
            ? __('uiux.dashboard.customer_service.title')
            : __('uiux.dashboard.customer_service.my_work');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('customer-care.view');
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $range = app(\App\Services\Dashboard\AnalyticsPeriodService::class)->resolve($this->period);

        $customers = Customer::query();
        $tickets = SupportTicket::query();
        $interactions = CustomerInteraction::query();

        if (! $user->isCustomerServiceManager()) {
            $staffId = $user->staff?->id ?? 0;

            $customers->whereHas('assignments', fn (Builder $q): Builder => $q
                ->where('staff_id', $staffId)
                ->where('status', 'active'));
            $tickets->where(function (Builder $q) use ($staffId): void {
                $q->where('assigned_staff_id', $staffId)
                    ->orWhereHas('customer.assignments', fn (Builder $assignment): Builder => $assignment
                        ->where('staff_id', $staffId)
                        ->where('status', 'active'));
            });
            $interactions->where('staff_id', $staffId);
        }

        $todayStart = today()->startOfDay();
        $todayEnd = today()->endOfDay();
        $customerStats = (clone $customers)
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as active_customers', ['active'])
            ->selectRaw('SUM(CASE WHEN converted_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_customers', [$range['start'], $range['end']])
            ->selectRaw('SUM(CASE WHEN next_follow_up_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as follow_up_today', [$todayStart, $todayEnd])
            ->selectRaw('SUM(CASE WHEN next_follow_up_at IS NOT NULL AND next_follow_up_at < ? THEN 1 ELSE 0 END) as overdue', [now()])
            ->first();
        $interactionCount = (clone $interactions)
            ->whereBetween('interaction_at', [$range['start'], $range['end']])
            ->count();
        $ticketCounts = (clone $tickets)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $openTicketStatuses = [
            TicketStatus::Open->value,
            TicketStatus::InProgress->value,
            TicketStatus::PendingCustomer->value,
        ];

        $summary = [
            'active_customers' => (int) ($customerStats?->active_customers ?? 0),
            'new_customers' => (int) ($customerStats?->new_customers ?? 0),
            'customer_interactions' => $interactionCount,
            'follow_up_today' => (int) ($customerStats?->follow_up_today ?? 0),
            'overdue' => (int) ($customerStats?->overdue ?? 0),
            'open_tickets' => collect($openTicketStatuses)->sum(fn (string $status): int => (int) $ticketCounts->get($status, 0)),
            'unassigned' => $user->isCustomerServiceManager()
                ? Customer::query()->whereDoesntHave('assignments', fn (Builder $q): Builder => $q->where('status', 'active'))->count()
                : 0,
        ];

        $statusRows = collect(TicketStatus::cases())
            ->map(fn (TicketStatus $status): array => [
                'label' => $status->label(),
                'value' => (int) $ticketCounts->get($status->value, 0),
            ])
            ->filter(fn (array $row): bool => $row['value'] > 0)
            ->values()
            ->all();

        $priority = (clone $tickets)
            ->with(['customer:id,display_name', 'assignedStaff:id,full_name'])
            ->whereIn('status', [TicketStatus::Open->value, TicketStatus::InProgress->value])
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END")
            ->orderBy('last_activity_at')
            ->limit(8)
            ->get()
            ->map(fn (SupportTicket $ticket): array => [
                'id' => $ticket->id,
                'name' => $ticket->customer?->display_name ?: $ticket->requester_name,
                'subject' => $ticket->subject,
                'status' => $ticket->status?->label() ?: '—',
                'last_activity_at' => $ticket->last_activity_at,
            ])
            ->all();

        return [
            'isManager' => $user->isCustomerServiceManager(),
            'summary' => $summary,
            'statusRows' => $statusRows,
            'priority' => $priority,
            'workforce' => $user->isCustomerServiceManager()
                ? app(DashboardSnapshotCache::class)->remember(
                    $user,
                    'workforce-customer-service',
                    $this->period,
                    fn (): array => app(WorkforceAnalyticsService::class)->report(
                        $user,
                        $this->period,
                        \App\Enums\Crm\DepartmentFunction::CustomerService,
                    ),
                )
                : null,
            'customerUrl' => CustomerResource::canViewAny() ? CustomerResource::getUrl() : null,
            'ticketUrl' => SupportTicketResource::canViewAny() ? SupportTicketResource::getUrl() : null,
            'workforceUrl' => WorkforceAnalyticsPage::canAccess() ? WorkforceAnalyticsPage::getUrl() : null,
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $data = $this->getViewData();
        $filename = 'customer-care-performance-'.now()->format('Ymd-His').'.csv';
        $labels = [
            'active_customers' => __('v1.analytics.active_customers'),
            'new_customers' => __('v1.analytics.new_customers'),
            'customer_interactions' => __('analytics.customer_interactions'),
            'follow_up_today' => __('analytics.follow_up_today'),
            'overdue' => __('analytics.overdue'),
            'open_tickets' => __('v1.analytics.open_tickets'),
            'unassigned' => __('v1.analytics.unassigned_customers'),
        ];

        return response()->streamDownload(function () use ($data, $labels): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [__('uiux.dashboard.customer_service.title')]);
            foreach ($data['summary'] as $key => $value) {
                fputcsv($out, [$labels[$key] ?? $key, $value]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
