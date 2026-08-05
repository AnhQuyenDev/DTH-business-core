<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Models\Crm\Staff;
use App\Services\Crm\LeadAssignmentService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

    public function getTitle(): string
    {
        return $this->record->lead_code;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('assign')
                ->label(__('action.assign_lead'))
                ->icon('heroicon-o-user-plus')
                ->form(LeadResource::assignmentForm())
                ->visible(
                    fn (): bool => $this->record->assigned_staff_id
                        === null
                        && LeadResource::canAssignLeads()
                )
                ->action(function (array $data): void {
                    app(LeadAssignmentService::class)->assign(
                        lead: $this->record,
                        staff: Staff::query()->findOrFail(
                            $data['staff_id']
                        ),
                        assignedByUserId: auth()->id(),
                        reason: $data['reason'] ?? null,
                    );

                    $this->refreshFormData([
                        'assigned_staff_id',
                        'assigned_at',
                        'intake_status',
                    ]);

                    Notification::make()
                        ->title(__('notification.lead_assigned'))
                        ->success()
                        ->send();
                }),

            Action::make('reassign')
                ->label(__('action.reassign_lead'))
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->form(LeadResource::assignmentForm(true))
                ->visible(
                    fn (): bool => $this->record->assigned_staff_id
                        !== null
                        && LeadResource::canReassignLeads()
                )
                ->action(function (array $data): void {
                    app(LeadAssignmentService::class)->assign(
                        lead: $this->record,
                        staff: Staff::query()->findOrFail(
                            $data['staff_id']
                        ),
                        assignedByUserId: auth()->id(),
                        reason: $data['reason'],
                        force: true,
                        transferCompanyOwner: (bool) ($data['transfer_company_owner'] ?? false),
                    );

                    $this->refreshFormData([
                        'assigned_staff_id',
                        'assigned_at',
                        'intake_status',
                    ]);

                    Notification::make()
                        ->title(__('notification.lead_reassigned'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
