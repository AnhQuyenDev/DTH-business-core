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
                        ->label('Dịch vụ quan tâm')
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
                        ->label('Giá trị ước tính')
                        ->default($this->record->estimated_value)
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₫')
                        ->helperText(
                            'Giá trị dự kiến của cơ hội bán hàng. '
                            .'Có thể để trống nếu chưa đủ thông tin.'
                        ),

                    Select::make('budget_status')
                        ->label('Tình trạng ngân sách')
                        ->options([
                            'confirmed_fit' => 'Đã xác nhận - phù hợp',
                            'confirmed_unfit' => 'Đã xác nhận - chưa phù hợp',
                            'unknown' => 'Chưa xác định',
                        ])
                        ->required(),

                    TextInput::make('budget_amount')
                        ->label('Ngân sách dự kiến của khách')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('₫')
                        ->helperText(
                            'Không bắt buộc nếu khách chưa cung cấp con số cụ thể.'
                        ),

                    Select::make('purchase_timeline')
                        ->label('Thời gian dự kiến mua')
                        ->options([
                            'within_7_days' => 'Trong 7 ngày',
                            'within_30_days' => 'Trong 30 ngày',
                            'within_3_months' => 'Trong 3 tháng',
                            'over_3_months' => 'Trên 3 tháng',
                            'unknown' => 'Chưa xác định',
                        ])
                        ->required(),

                    Select::make('decision_role')
                        ->label('Vai trò của người liên hệ')
                        ->options([
                            'decision_maker' => 'Người quyết định',
                            'influencer' => 'Người ảnh hưởng / đề xuất',
                            'information_gatherer' => 'Người thu thập thông tin',
                            'unknown' => 'Chưa xác định',
                        ])
                        ->required(),

                    Select::make('priority')
                        ->label('Ưu tiên')
                        ->options([
                            'low' => 'Thấp',
                            'normal' => 'Bình thường',
                            'high' => 'Cao',
                            'vip' => 'VIP',
                        ])
                        ->default(
                            $this->record->qualification?->priority ?? 'normal'
                        )
                        ->required(),

                    TextInput::make('score')
                        ->label('Điểm Lead')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->helperText(
                            'Tùy chọn. Chỉ sử dụng nếu doanh nghiệp có quy tắc chấm điểm.'
                        ),

                    Textarea::make('qualification_note')
                        ->label('Ghi chú đánh giá')
                        ->placeholder(
                            'Ví dụ: Khách xác nhận nhu cầu chuyển 5 website, '
                            .'ngân sách khoảng 5 triệu, muốn triển khai trong '
                            .'tháng 8/2026...'
                        )
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
                ->label('Bàn giao sang Sales')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('success')
                ->visible(function (): bool {
                    $user = auth()->user();

                    if (
                        $user === null
                        || ! $user->hasRole(
                            UserRole::CustomerServiceManager
                        )
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
                        ->label('Lead')
                        ->content(
                            fn (): string =>
                                $this->record->lead_code
                                .' — '
                                .($this->record->contact?->full_name
                                    ?? $this->record->title)
                        ),

                    Placeholder::make('company_display')
                        ->label('Công ty')
                        ->content(
                            fn (): string =>
                                $this->record->company?->legal_name
                                ?? 'Khách hàng cá nhân'
                        ),

                    Placeholder::make('service_display')
                        ->label('Dịch vụ quan tâm')
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
                        ->label('Giá trị ước tính')
                        ->content(
                            fn (): string =>
                                $this->record->estimated_value !== null
                                    ? number_format(
                                        (float) $this->record->estimated_value,
                                        0,
                                        ',',
                                        '.'
                                    ).' đ'
                                    : '—'
                        ),

                    TextInput::make('title')
                        ->label('Tên cơ hội')
                        ->default(function (): string {
                            $service = data_get(
                                $this->record->metadata,
                                'service_context.display_label'
                            )
                                ?? $this->record->service_interest
                                ?? 'Cơ hội';

                            $account =
                                $this->record->company?->legal_name
                                ?? $this->record->contact?->full_name
                                ?? $this->record->lead_code;

                            return $service.' - '.$account;
                        })
                        ->required()
                        ->maxLength(255),

                    Select::make('sales_staff_id')
                        ->label('Sales phụ trách')
                        ->options(
                            fn (): array => Staff::query()
                                ->eligibleForOpportunityOwnership()
                                ->orderBy('full_name')
                                ->get()
                                ->mapWithKeys(
                                    fn (Staff $staff): array => [
                                        $staff->id =>
                                            $staff->full_name
                                            .' ('
                                            .$staff->employee_code
                                            .')',
                                    ]
                                )
                                ->all()
                        )
                        ->searchable()
                        ->preload()
                        ->required(),

                    TextInput::make('probability')
                        ->label('Xác suất thành công')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->default(50)
                        ->required(),

                    DatePicker::make('expected_close_date')
                        ->label('Ngày dự kiến chốt')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->minDate(now()->toDateString())
                        ->default(
                            now()->addDays(21)->toDateString()
                        ),

                    Textarea::make('handoff_note')
                        ->label('Ghi chú bàn giao')
                        ->rows(3)
                        ->placeholder(
                            'Thông tin bổ sung dành cho Sales nếu có.'
                        )
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
                        ->title('Đã bàn giao sang Sales')
                        ->body(
                            'Đã tạo cơ hội '
                            .$opportunity->opportunity_code
                            .' và giao cho '
                            .$opportunity->assignedStaff?->full_name
                            .'.'
                        )
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
