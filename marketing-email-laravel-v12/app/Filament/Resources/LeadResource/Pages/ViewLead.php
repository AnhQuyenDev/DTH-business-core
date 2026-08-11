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
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use App\Enums\UserRole;
use App\Services\Sales\OpportunityCreationService;
use Filament\Forms\Components\DatePicker;

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
                    DateTimePicker::make('next_follow_up_at')
                        ->label(__('field.next_follow_up'))
                        ->timezone(config('business_flow.timezone'))
                        ->native(false)
                        ->displayFormat('d/m/Y H:i')
                        ->seconds(false)
                        ->after('now'),
                ])
                ->action(function (array $data): void {
                    $data['activity_at'] = now();

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
                        ->timezone(config('business_flow.timezone'))
                        ->native(false)
                        ->displayFormat('d/m/Y H:i')
                        ->seconds(false)
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
                    Placeholder::make('service_interest_display')
                        ->label(__('field.service_interest'))
                        ->content(function (): string {
                            return data_get(
                                $this->record->metadata,
                                'service_context.display_label'
                            )
                                ?? data_get(
                                    $this->record->metadata,
                                    'service_interest_label'
                                )
                                ?? $this->record->service_interest
                                ?? '—';
                        }),

                    Hidden::make('service_interest')
                        ->default(
                            fn (): ?string => $this->record->service_interest
                        ),

                    TextInput::make('estimated_value')
                        ->label(__('field.estimated_value'))
                        ->default($this->record->estimated_value)
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₫')
                        ->helperText(__('helper.opportunity_estimated_value')),

                    Select::make('budget_status')
                        ->label(__('field.budget_status'))
                        ->options([
                            'confirmed_fit' => __('field.budget_fit_confirmed'),
                            'confirmed_unfit' => __('field.budget_unfit_confirmed'),
                            'unknown' => __('common.unknown'),
                        ])
                        ->required(),

                    TextInput::make('budget_amount')
                        ->label(__('field.expected_budget'))
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₫')
                        ->helperText(__('helper.budget_amount_optional')),

                    Select::make('purchase_timeline')
                        ->label(__('field.expected_purchase_time'))
                        ->options([
                            'within_7_days' => __('field.timeline_within_7_days'),
                            'within_30_days' => __('field.timeline_within_30_days'),
                            'within_3_months' => __('field.timeline_within_3_months'),
                            'over_3_months' => __('field.timeline_over_3_months'),
                            'unknown' => __('common.unknown'),
                        ])
                        ->required(),

                    Select::make('decision_role')
                        ->label(__('field.contact_decision_role'))
                        ->options([
                            'decision_maker' => __('field.decision_maker'),
                            'influencer' => __('field.influencer'),
                            'information_gatherer' => __('field.information_gatherer'),
                            'unknown' => __('common.unknown'),
                        ])
                        ->required(),

                    Select::make('priority')
                        ->label(__('field.priority'))
                        ->options([
                            'low' => __('field.priority.low'),
                            'normal' => __('field.priority.normal'),
                            'high' => __('field.priority.high'),
                            'vip' => __('field.priority.vip'),
                        ])
                        ->default(
                            $this->record->qualification?->priority ?? 'normal'
                        )
                        ->required(),

                    TextInput::make('score')
                        ->label(__('field.lead_score'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->helperText(__('helper.lead_score_optional')),

                    Textarea::make('qualification_note')
                        ->label(__('field.qualification_note'))
                        ->placeholder(__('helper.qualification_note_example'))
                        ->rows(5)
                        ->maxLength(2000)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    app(ContactQualificationWorkflowService::class)->transition(
                        qualification: $this->record->qualification,
                        to: ContactQualificationStatus::Qualified,
                        data: [
                            'service_interest' => $data['service_interest'],

                            'estimated_value' =>
                                $data['estimated_value'] ?? null,

                            'budget_status' =>
                                $data['budget_status'],

                            'budget_amount' =>
                                $data['budget_amount'] ?? null,

                            'purchase_timeline' =>
                                $data['purchase_timeline'],

                            'decision_role' =>
                                $data['decision_role'],

                            'qualification_note' =>
                                $data['qualification_note'],

                            'priority' =>
                                $data['priority'],

                            'score' =>
                                $data['score'] ?? null,

                            'qualified_by_staff_id' =>
                                auth()->user()?->staff?->id,
                        ],
                        actorUserId: auth()->id(),
                    );

                    app(LeadActivityService::class)->record(
                        lead: $this->record,
                        data: [
                            'activity_type' => LeadActivityType::Note->value,
                            'subject' => __('activity.qualification_completed'),
                            'content' => $data['qualification_note'],
                            'activity_at' => now(),
                        ],
                        actorUserId: auth()->id(),
                        staffId: auth()->user()?->staff?->id,
                    );

                    $this->record->refresh();

                    Notification::make()
                        ->title(__('notification.lead_qualified'))
                        ->success()
                        ->send();
                }),
            
            Action::make('handoff_to_sales')
                ->label(__('field.handoff_to_sales'))
                ->icon('heroicon-o-arrow-right-circle')
                ->color('success')
                ->visible(function (): bool {
                    $user = auth()->user();

                    if (
                        $user === null
                        || ! $user->isSalesStaff()
                    ) {
                        return false;
                    }

                    $status =
                        $this->record
                            ->qualification
                            ?->status
                            ?->value
                        ?? $this->record
                            ->qualification
                            ?->status;

                    return $status
                            === ContactQualificationStatus::Qualified->value
                        && $this->record->opportunity === null;
                })

                ->form([
                    Placeholder::make('lead_display')
                        ->label(__('resource.lead.singular'))
                        ->content(
                            fn (): string =>
                                $this->record->lead_code
                                .' — '
                                .($this->record->contact?->full_name
                                    ?? $this->record->title)
                        ),

                    Placeholder::make('company_display')
                        ->label(__('field.company'))
                        ->content(
                            fn (): string =>
                                $this->record->company?->legal_name
                                ?? __('common.personal_customer')
                        ),

                    Placeholder::make('service_display')
                        ->label(__('field.service_interest'))
                        ->content(
                            fn (): string =>
                                data_get(
                                    $this->record->metadata,
                                    'service_context.display_label'
                                )
                                ?? data_get(
                                    $this->record->metadata,
                                    'service_interest_label'
                                )
                                ?? $this->record->service_interest
                                ?? '—'
                        ),

                    Placeholder::make('estimated_value_display')
                        ->label(__('field.estimated_value'))
                        ->content(
                            fn (): string =>
                                $this->record->estimated_value !== null
                                    ? number_format(
                                        (float) $this->record->estimated_value,
                                        0,
                                        ',',
                                        '.'
).' '.__('field.currency_vnd_short')
                                    : '—'
                        ),

                    TextInput::make('title')
                        ->label(__('field.opportunity_name'))
                        ->default(function (): string {
                            $service = data_get(
                                $this->record->metadata,
                                'service_context.display_label'
                            )
                                ?? $this->record->service_interest
                                ?? __('field.opportunity');

                            $account =
                                $this->record->company?->legal_name
                                ?? $this->record->contact?->full_name
                                ?? $this->record->lead_code;

                            return $service.' - '.$account;
                        })
                        ->required()
                        ->maxLength(255),

                    Select::make('sales_staff_id')
                        ->label(__('field.sales_owner'))
                        ->options(function (): array {
                            $query = Staff::query()->eligibleForOpportunityOwnership();

                            if (! auth()->user()?->isSalesManager()) {
                                $query->whereKey(auth()->user()?->staff?->id ?? 0);
                            }

                            return $query
                                ->orderBy('full_name')
                                ->get()
                                ->mapWithKeys(
                                    fn (Staff $staff): array => [
                                        $staff->id => $staff->full_name.' ('.$staff->employee_code.')',
                                    ]
                                )
                                ->all();
                        })
                        ->default(fn (): ?int => auth()->user()?->staff?->id)
                        ->searchable()
                        ->preload()
                        ->required(),

                    TextInput::make('probability')
                        ->label(__('field.win_probability'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->default(50)
                        ->required(),

                    DatePicker::make('expected_close_date')
                        ->label(__('field.expected_close_date'))
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->minDate(now()->toDateString())
                        ->default(
                            now()->addDays(21)->toDateString()
                        ),

                    Textarea::make('handoff_note')
                        ->label(__('field.handoff_note'))
                        ->rows(3)
                        ->placeholder(__('helper.sales_additional_info'))
                        ->maxLength(2000),
                ])

                ->action(function (array $data): void {
                    $opportunity = app(
                        OpportunityCreationService::class
                    )->createFromQualifiedLead(
                        lead: $this->record,
                        salesOwner: Staff::query()
                            ->findOrFail($data['sales_staff_id']),
                        data: $data,
                        actorUserId: auth()->id(),
                    );

                    $this->record->refresh();

                    Notification::make()
                        ->title(__('notification.handoff_to_sales_complete'))
                        ->body(
                            __('notification.opportunity_created').' '
                            .$opportunity->opportunity_code.' '
                            .__('notification.opportunity_assigned_to', [
                                'staff' => $opportunity->assignedStaff?->full_name ?? '—',
                            ]).'.'
                        )
                        ->success()
                        ->send();
                }),

            ActionGroup::make([
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
            ])
                ->label(__('action.more_actions'))
                ->icon('heroicon-o-ellipsis-horizontal')
                ->button()
                ->color('gray'),
        ];
    }
}
