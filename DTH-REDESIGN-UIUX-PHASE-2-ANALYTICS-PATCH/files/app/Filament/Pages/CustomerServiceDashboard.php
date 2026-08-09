<?php

namespace App\Filament\Pages;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\LeadIntakeStatus;
use App\Filament\Resources\LeadResource;
use App\Models\Crm\Lead;
use App\Services\Dashboard\DashboardScopeService;
use App\Services\Dashboard\WorkforceAnalyticsService;
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
        return __('navigation.group.crm');
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

        return $user !== null && ($user->isCustomerServiceManager() || $user->isCustomerServiceStaff());
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $leads = app(DashboardScopeService::class)->leads($user);
        $range = app(\App\Services\Dashboard\AnalyticsPeriodService::class)->resolve($this->period);

        $periodLeads = (clone $leads)->whereBetween('created_at', [$range['start'], $range['end']]);
        $handled = (clone $leads)->whereNotNull('assigned_staff_id')->whereBetween('assigned_at', [$range['start'], $range['end']])->count();
        $qualified = (clone $leads)->whereHas('qualification', fn (Builder $q): Builder => $q->whereBetween('qualified_at', [$range['start'], $range['end']]))->count();
        $summary = [
            'new' => (clone $periodLeads)->where('intake_status', LeadIntakeStatus::New->value)->count(),
            'handled' => $handled,
            'qualified' => $qualified,
            'qualification_rate' => $handled > 0 ? round(($qualified / $handled) * 100, 1) : 0,
            'follow_up_today' => (clone $leads)->whereHas('qualification', fn (Builder $q): Builder => $q->whereDate('next_follow_up_at', today()))->count(),
            'overdue' => (clone $leads)->whereHas('qualification', fn (Builder $q): Builder => $q
                ->where('next_follow_up_at', '<', now())
                ->whereIn('status', [ContactQualificationStatus::Assigned->value, ContactQualificationStatus::Contacting->value, ContactQualificationStatus::FollowUp->value]))->count(),
            'unassigned' => (clone $leads)->whereNull('assigned_staff_id')->whereNotIn('intake_status', [LeadIntakeStatus::Closed->value, LeadIntakeStatus::Duplicate->value, LeadIntakeStatus::Spam->value])->count(),
        ];

        $statusRows = collect(ContactQualificationStatus::cases())
            ->map(fn (ContactQualificationStatus $status): array => [
                'label' => $status->label(),
                'value' => (clone $leads)->whereHas('qualification', fn (Builder $q): Builder => $q->where('status', $status->value))->count(),
            ])
            ->filter(fn (array $row): bool => $row['value'] > 0)
            ->values()
            ->all();

        $priority = (clone $leads)
            ->with(['contact:id,full_name,email', 'company:id,legal_name', 'qualification'])
            ->whereHas('qualification', fn (Builder $q): Builder => $q
                ->whereIn('status', [ContactQualificationStatus::Assigned->value, ContactQualificationStatus::Contacting->value, ContactQualificationStatus::FollowUp->value])
                ->whereNotNull('next_follow_up_at'))
            ->limit(30)
            ->get()
            ->sortBy(fn (Lead $lead) => $lead->qualification?->next_follow_up_at?->timestamp ?? PHP_INT_MAX)
            ->take(8)
            ->values()
            ->map(fn (Lead $lead): array => [
                'id' => $lead->id,
                'name' => $lead->company?->legal_name ?: $lead->contact?->full_name ?: $lead->title,
                'follow_up_at' => $lead->qualification?->next_follow_up_at,
                'status' => $lead->qualification?->status?->label() ?: '—',
            ])
            ->all();

        return [
            'isManager' => $user->isCustomerServiceManager(),
            'summary' => $summary,
            'statusRows' => $statusRows,
            'priority' => $priority,
            'workforce' => $user->isCustomerServiceManager() ? app(WorkforceAnalyticsService::class)->report($user, $this->period) : null,
            'leadUrl' => LeadResource::canViewAny() ? LeadResource::getUrl() : null,
            'workforceUrl' => WorkforceAnalyticsPage::canAccess() ? WorkforceAnalyticsPage::getUrl() : null,
        ];
    }

    public function exportCsv(): StreamedResponse
    {
        $data = $this->getViewData();
        $filename = 'customer-service-performance-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [__('uiux.dashboard.customer_service.title')]);
            foreach ($data['summary'] as $key => $value) {
                fputcsv($out, [__('analytics.'.$key), $value]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
