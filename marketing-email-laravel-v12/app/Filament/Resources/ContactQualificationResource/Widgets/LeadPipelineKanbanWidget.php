<?php

namespace App\Filament\Resources\ContactQualificationResource\Widgets;

use App\Enums\Crm\ContactQualificationStatus;
use App\Filament\Resources\ContactQualificationResource;
use App\Models\Crm\ContactQualification;
use Filament\Widgets\Widget;

class LeadPipelineKanbanWidget extends Widget
{
    protected static string $view = 'filament.widgets.lead-pipeline-kanban-widget';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return ($user?->isAdmin() || $user?->isMarketingManager() || $user?->isCustomerServiceManager() || $user?->isCustomerServiceStaff()) ?? false;
    }

    public function getStages(): array
    {
        $map = [
            'not_contacted' => [
                ContactQualificationStatus::New,
                ContactQualificationStatus::Assigned,
            ],
            'in_progress' => [
                ContactQualificationStatus::Contacting,
                ContactQualificationStatus::FollowUp,
            ],
            'qualified' => [ContactQualificationStatus::Qualified],
            'converted' => [ContactQualificationStatus::Converted],
        ];

        $stages = [];

        foreach ($map as $tab => $statuses) {
            $values = array_map(static fn (ContactQualificationStatus $s) => $s->value, $statuses);

            $query = ContactQualification::query()
                ->with(['contact', 'assignedStaff'])
                ->whereIn('status', $values);

            $stages[] = [
                'tab' => $tab,
                'label' => $statuses[0]->label(),
                'count' => (clone $query)->count(),
                'items' => (clone $query)
                    ->latest('updated_at')
                    ->limit(5)
                    ->get()
                    ->map(function (ContactQualification $lead): array {
                        $name = $lead->contact?->full_name
                            ?? $lead->contact?->company_name
                            ?? $lead->contact?->email
                            ?? ('#'.$lead->contact_id);

                        return [
                            'id' => $lead->id,
                            'name' => $name,
                            'email' => $lead->contact?->email,
                            'staff' => $lead->assignedStaff?->full_name,
                        ];
                    })
                    ->all(),
            ];
        }

        return $stages;
    }

    public function getTabUrl(string $tab): string
    {
        return ContactQualificationResource::getUrl('index', ['activeTab' => $tab]);
    }
}
