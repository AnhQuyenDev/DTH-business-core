<?php

namespace App\Filament\Pages;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\LeadIntakeStatus;
use App\Filament\Resources\LeadResource;
use App\Models\Crm\Lead;
use App\Services\Dashboard\DashboardScopeService;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class CustomerServiceDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?int $navigationSort = -10;
    protected static string $view = 'filament.pages.customer-service-dashboard';

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
        $scope = app(DashboardScopeService::class);
        $leads = $scope->leads($user);

        $qualificationBase = fn (): Builder => (clone $leads)->whereHas('qualification');

        $statusRows = collect(ContactQualificationStatus::cases())
            ->map(function (ContactQualificationStatus $status) use ($qualificationBase): array {
                return [
                    'label' => $status->label(),
                    'value' => $qualificationBase()->whereHas('qualification', fn (Builder $q): Builder => $q->where('status', $status->value))->count(),
                    'color' => $status->color(),
                ];
            })
            ->filter(fn (array $row): bool => $row['value'] > 0)
            ->values()
            ->all();

        $workload = [];
        if ($user->isCustomerServiceManager()) {
            $workload = Lead::query()
                ->whereNotNull('assigned_staff_id')
                ->where('intake_status', LeadIntakeStatus::Active->value)
                ->selectRaw('assigned_staff_id, count(*) as total')
                ->groupBy('assigned_staff_id')
                ->with('assignedStaff:id,full_name,employee_code')
                ->orderByDesc('total')
                ->limit(10)
                ->get()
                ->map(fn (Lead $row): array => [
                    'name' => $row->assignedStaff?->full_name ?: __('common.not_available'),
                    'code' => $row->assignedStaff?->employee_code,
                    'total' => (int) $row->getAttribute('total'),
                ])
                ->all();
        }

        $priority = (clone $leads)
            ->with(['contact:id,full_name,email', 'company:id,legal_name', 'qualification'])
            ->whereHas('qualification', fn (Builder $q): Builder => $q
                ->whereIn('status', [
                    ContactQualificationStatus::Assigned->value,
                    ContactQualificationStatus::Contacting->value,
                    ContactQualificationStatus::FollowUp->value,
                ])
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
            'summary' => [
                'new' => (clone $leads)->where('intake_status', LeadIntakeStatus::New->value)->count(),
                'unassigned' => (clone $leads)->whereNull('assigned_staff_id')->whereNotIn('intake_status', [LeadIntakeStatus::Closed->value, LeadIntakeStatus::Duplicate->value, LeadIntakeStatus::Spam->value])->count(),
                'in_progress' => (clone $leads)->whereHas('qualification', fn (Builder $q): Builder => $q->whereIn('status', [ContactQualificationStatus::Assigned->value, ContactQualificationStatus::Contacting->value, ContactQualificationStatus::FollowUp->value]))->count(),
                'follow_up_today' => (clone $leads)->whereHas('qualification', fn (Builder $q): Builder => $q->whereDate('next_follow_up_at', today()))->count(),
                'overdue' => (clone $leads)->whereHas('qualification', fn (Builder $q): Builder => $q->where('next_follow_up_at', '<', now())->whereIn('status', [ContactQualificationStatus::Assigned->value, ContactQualificationStatus::Contacting->value, ContactQualificationStatus::FollowUp->value]))->count(),
                'qualified' => (clone $leads)->whereHas('qualification', fn (Builder $q): Builder => $q->where('status', ContactQualificationStatus::Qualified->value))->count(),
            ],
            'statusRows' => $statusRows,
            'workload' => $workload,
            'priority' => $priority,
            'leadUrl' => LeadResource::canViewAny() ? LeadResource::getUrl() : null,
        ];
    }


    public function exportCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = $this->getViewData();
        $filename = 'customer-service-dashboard-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [__('uiux.dashboard.customer_service.title')]);
            foreach ($data['summary'] as $key => $value) {
                $labelKey = match ($key) {
                    'new' => 'new_leads',
                    default => $key,
                };
                fputcsv($out, [__('uiux.dashboard.customer_service.'.$labelKey), $value]);
            }
            fputcsv($out, []);
            fputcsv($out, [__('uiux.dashboard.customer_service.staff_workload')]);
            fputcsv($out, [__('field.staff'), __('field.employee_code'), __('uiux.dashboard.common.count')]);
            foreach ($data['workload'] as $row) {
                fputcsv($out, [$row['name'], $row['code'], $row['total']]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

}
