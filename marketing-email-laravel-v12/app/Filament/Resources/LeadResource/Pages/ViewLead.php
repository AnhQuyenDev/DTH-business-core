<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\LeadActivityType;
use App\Enums\Crm\QualificationResult;
use App\Filament\Resources\LeadResource;
use App\Models\Crm\Staff;
use App\Services\Crm\ContactQualificationWorkflowService;
use App\Services\Crm\LeadActivityService;
use App\Services\Crm\LeadAssignmentService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

            Action::make('start_contacting')
                ->label(__('action.start_contacting'))
                ->icon('heroicon-o-phone-arrow-up-right')
                ->color('info')
                ->visible(function (): bool {
                    $status = $this->record->qualification?->status?->value
                        ?? $this->record->qualification?->status;

                    return LeadResource::canProcessLead($this->record)
                        && $status === ContactQualificationStatus::Assigned->value;
                })
                ->requiresConfirmation()
                ->action(function (): void {
                    app(ContactQualificationWorkflowService::class)->transition(
                        qualification: $this->record->qualification,
                        to: ContactQualificationStatus::Contacting,
                        actorUserId: auth()->id(),
                    );

                    $this->record->refresh();

                    Notification::make()
                        ->title(__('notification.lead_contacting_started'))
                        ->success()
                        ->send();
                }),

            Action::make('record_activity')
                ->label(__('action.record_lead_activity'))
                ->icon('heroicon-o-chat-bubble-left-right')
                ->visible(fn (): bool => LeadResource::canProcessLead($this->record)
                    && $this->record->assigned_staff_id !== null
                    && ! ($this->record->qualification?->status?->isTerminal() ?? false))
                ->form([
                    Select::make('activity_type')
                        ->label(__('field.activity_type'))
                        ->options(LeadActivityType::options())
                        ->required(),
                    TextInput::make('subject')
                        ->label(__('field.subject'))
                        ->required()
                        ->maxLength(255),
                    Textarea::make('content')
                        ->label(__('field.content'))
                        ->rows(4),
                    Textarea::make('outcome')
                        ->label(__('field.outcome'))
                        ->rows(2),
                    DateTimePicker::make('activity_at')
                        ->label(__('field.activity_at'))
                        ->default(now())
                        ->required(),
                    DateTimePicker::make('next_follow_up_at')
                        ->label(__('field.next_follow_up'))
                        ->after('now'),
                ])
                ->action(function (array $data): void {
                    app(LeadActivityService::class)->record(
                        lead: $this->record,
                        data: $data,
                        actorUserId: auth()->id(),
                        staffId: auth()->user()?->staff?->id,
                    );

                    $this->record->refresh();

                    Notification::make()
                        ->title(__('notification.lead_activity_recorded'))
                        ->success()
                        ->send();
                }),

            Action::make('schedule_follow_up')
                ->label(__('action.schedule_follow_up'))
                ->icon('heroicon-o-calendar-days')
                ->color('warning')
                ->visible(function (): bool {
                    $status = $this->record->qualification?->status?->value
                        ?? $this->record->qualification?->status;

                    return LeadResource::canProcessLead($this->record)
                        && in_array($status, [
                            ContactQualificationStatus::Contacting->value,
                            ContactQualificationStatus::FollowUp->value,
                        ], true);
                })
                ->form([
                    DateTimePicker::make('next_follow_up_at')
                        ->label(__('field.next_follow_up'))
                        ->after('now')
                        ->required(),
                    Textarea::make('note')
                        ->label(__('field.note'))
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    app(LeadActivityService::class)->scheduleFollowUp(
                        lead: $this->record,
                        nextFollowUpAt: $data['next_follow_up_at'],
                        note: $data['note'] ?? null,
                        actorUserId: auth()->id(),
                        staffId: auth()->user()?->staff?->id,
                    );

                    $this->record->refresh();

                    Notification::make()
                        ->title(__('notification.follow_up_scheduled'))
                        ->success()
                        ->send();
                }),

            Action::make('mark_qualified')
                ->label(__('action.mark_qualified'))
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->visible(function (): bool {
                    $status = $this->record->qualification?->status?->value
                        ?? $this->record->qualification?->status;

                    return LeadResource::canProcessLead($this->record)
                        && in_array($status, [
                            ContactQualificationStatus::Contacting->value,
                            ContactQualificationStatus::FollowUp->value,
                        ], true);
                })
                ->form([
                    TextInput::make('service_interest')
                        ->label(__('field.service_interest'))
                        ->default($this->record->service_interest)
                        ->required()
                        ->maxLength(255),
                    TextInput::make('estimated_value')
                        ->label(__('field.estimated_value'))
                        ->default($this->record->estimated_value)
                        ->numeric()
                        ->minValue(0),
                    Select::make('priority')
                        ->label(__('field.priority'))
                        ->options([
                            'low' => __('field.priority.low'),
                            'normal' => __('field.priority.normal'),
                            'high' => __('field.priority.high'),
                            'vip' => __('field.priority.vip'),
                        ])
                        ->default($this->record->qualification?->priority ?? 'normal')
                        ->required(),
                    TextInput::make('score')
                        ->label(__('field.score'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100),
                    Textarea::make('note')
                        ->label(__('field.note'))
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    app(ContactQualificationWorkflowService::class)->transition(
                        qualification: $this->record->qualification,
                        to: ContactQualificationStatus::Qualified,
                        data: [
                            'service_interest' => $data['service_interest'],
                            'estimated_value' => $data['estimated_value'] ?? null,
                            'priority' => $data['priority'],
                            'score' => $data['score'] ?? null,
                            'qualified_by_staff_id' => auth()->user()?->staff?->id,
                        ],
                        actorUserId: auth()->id(),
                    );

                    if (filled($data['note'] ?? null)) {
                        app(LeadActivityService::class)->record(
                            lead: $this->record,
                            data: [
                                'activity_type' => LeadActivityType::Note->value,
                                'subject' => __('activity.qualification_completed'),
                                'content' => $data['note'],
                                'activity_at' => now(),
                            ],
                            actorUserId: auth()->id(),
                            staffId: auth()->user()?->staff?->id,
                        );
                    }

                    $this->record->refresh();

                    Notification::make()
                        ->title(__('notification.lead_qualified'))
                        ->success()
                        ->send();
                }),

            Action::make('mark_unqualified')
                ->label(__('action.mark_unqualified'))
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(function (): bool {
                    $status = $this->record->qualification?->status?->value
                        ?? $this->record->qualification?->status;

                    return LeadResource::canProcessLead($this->record)
                        && in_array($status, [
                            ContactQualificationStatus::Assigned->value,
                            ContactQualificationStatus::Contacting->value,
                            ContactQualificationStatus::FollowUp->value,
                            ContactQualificationStatus::Qualified->value,
                        ], true);
                })
                ->form([
                    Select::make('qualification_result')
                        ->label(__('field.qualification_result'))
                        ->options([
                            QualificationResult::NoNeed->value => QualificationResult::NoNeed->label(),
                            QualificationResult::Unreachable->value => QualificationResult::Unreachable->label(),
                            QualificationResult::InvalidInformation->value => QualificationResult::InvalidInformation->label(),
                        ])
                        ->required(),
                    Textarea::make('unqualified_reason')
                        ->label(__('field.unqualified_reason'))
                        ->required()
                        ->rows(4)
                        ->maxLength(1000),
                ])
                ->action(function (array $data): void {
                    app(ContactQualificationWorkflowService::class)->transition(
                        qualification: $this->record->qualification,
                        to: ContactQualificationStatus::Unqualified,
                        data: $data,
                        actorUserId: auth()->id(),
                    );

                    $this->record->refresh();

                    Notification::make()
                        ->title(__('notification.lead_unqualified'))
                        ->success()
                        ->send();
                }),

            Action::make('mark_duplicate')
                ->label(__('action.mark_duplicate'))
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->visible(function (): bool {
                    $status = $this->record->qualification?->status?->value
                        ?? $this->record->qualification?->status;

                    return LeadResource::canProcessLead($this->record)
                        && in_array($status, [
                            ContactQualificationStatus::New->value,
                            ContactQualificationStatus::Assigned->value,
                            ContactQualificationStatus::Contacting->value,
                            ContactQualificationStatus::FollowUp->value,
                        ], true);
                })
                ->form([
                    Textarea::make('reason')
                        ->label(__('field.reason'))
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    app(ContactQualificationWorkflowService::class)->transition(
                        qualification: $this->record->qualification,
                        to: ContactQualificationStatus::Duplicate,
                        data: $data,
                        actorUserId: auth()->id(),
                    );

                    Notification::make()
                        ->title(__('notification.lead_marked_duplicate'))
                        ->success()
                        ->send();
                }),

            Action::make('mark_spam')
                ->label(__('action.mark_spam'))
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->visible(function (): bool {
                    $status = $this->record->qualification?->status?->value
                        ?? $this->record->qualification?->status;

                    return LeadResource::canProcessLead($this->record)
                        && in_array($status, [
                            ContactQualificationStatus::New->value,
                            ContactQualificationStatus::Assigned->value,
                            ContactQualificationStatus::Contacting->value,
                            ContactQualificationStatus::FollowUp->value,
                        ], true);
                })
                ->form([
                    Textarea::make('reason')
                        ->label(__('field.reason'))
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    app(ContactQualificationWorkflowService::class)->transition(
                        qualification: $this->record->qualification,
                        to: ContactQualificationStatus::Spam,
                        data: $data,
                        actorUserId: auth()->id(),
                    );

                    Notification::make()
                        ->title(__('notification.lead_marked_spam'))
                        ->success()
                        ->send();
                }),

            Action::make('archive')
                ->label(__('action.archive'))
                ->icon('heroicon-o-archive-box')
                ->visible(function (): bool {
                    $status = $this->record->qualification?->status?->value
                        ?? $this->record->qualification?->status;

                    return LeadResource::canArchiveLead($this->record)
                        && in_array($status, [
                            ContactQualificationStatus::New->value,
                            ContactQualificationStatus::Assigned->value,
                            ContactQualificationStatus::FollowUp->value,
                            ContactQualificationStatus::Unqualified->value,
                            ContactQualificationStatus::Duplicate->value,
                            ContactQualificationStatus::Spam->value,
                        ], true);
                })
                ->requiresConfirmation()
                ->action(function (): void {
                    app(ContactQualificationWorkflowService::class)->transition(
                        qualification: $this->record->qualification,
                        to: ContactQualificationStatus::Archived,
                        actorUserId: auth()->id(),
                    );

                    Notification::make()
                        ->title(__('notification.lead_archived'))
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
