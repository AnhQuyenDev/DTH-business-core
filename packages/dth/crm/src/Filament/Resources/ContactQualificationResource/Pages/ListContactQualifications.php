<?php

namespace Dth\Crm\Filament\Resources\ContactQualificationResource\Pages;

use Dth\Crm\Filament\Resources\ContactQualificationResource;
use Dth\Crm\Filament\Support\CrmPageUi;
use Dth\Crm\Models\ContactQualification;
use Dth\Crm\Models\Lead;
use Dth\Crm\Support\CrmOptions;
use Dth\Crm\Support\UiText;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class ListContactQualifications extends ListRecords
{
    protected static string $resource = ContactQualificationResource::class;

    public function getTitle(): string|Htmlable
    {
        return CrmPageUi::title(UiText::get('pages.qualifications.title', 'Lead qualification'), 'qualification', 'violet');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.qualifications.subheading', 'Evaluate Lead quality, budget, buying timeline, decision role, and follow-up progress.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createQualification')
                ->label(UiText::get('pages.qualifications.create', 'Create Lead qualification'))
                ->icon('heroicon-o-clipboard-document-check')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-crm-entry-action dth-crm-entry-action--violet'])
                ->modalHeading(UiText::get('pages.qualifications.create', 'Create Lead qualification'))
                ->modalIcon('heroicon-o-clipboard-document-check')
                ->modalWidth(Width::FourExtraLarge)
                ->modalSubmitAction(fn (Action $action): Action => $action->label(UiText::get('common.actions.save', 'Save'))->icon('heroicon-o-check'))
                ->modalCancelAction(fn (Action $action): Action => $action->label(UiText::get('common.actions.cancel', 'Cancel'))->icon('heroicon-o-x-mark')->color('gray'))
                ->schema([
                    Grid::make(3)->schema([
                        // Hàng 1: thông tin chính. Lead chiếm 2/3 chiều ngang để tên dài vẫn dễ đọc.
                        Select::make('lead_id')
                            ->label(UiText::get('models.lead', 'Lead'))
                            ->options(fn (): array => Lead::query()
                                ->whereDoesntHave('qualification')
                                ->with(['contact:id,display_name', 'company:id,legal_name'])
                                ->orderByDesc('id')
                                ->limit(500)
                                ->get()
                                ->mapWithKeys(fn (Lead $lead): array => [
                                    $lead->id => trim($lead->lead_code.' · '.($lead->contact?->display_name ?? $lead->company?->legal_name ?? $lead->title ?? '')),
                                ])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2)
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Chọn Lead chưa có bản đánh giá để tạo hồ sơ qualification.'),
                        Select::make('priority')
                            ->label(UiText::get('fields.priority', 'Priority'))
                            ->options(CrmOptions::priorities())
                            ->default('normal')
                            ->native(false)
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Mức độ ưu tiên để đội ngũ CRM biết nên chăm sóc Lead này nhanh tới đâu.'),

                        // Hàng 2: nhóm đánh giá nhanh.
                        TextInput::make('score')
                            ->label(UiText::get('fields.score', 'Score'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Điểm đánh giá tổng quát của Lead, thường trong thang 0 đến 100.'),
                        Select::make('budget_status')
                            ->label(UiText::get('fields.budget_status', 'Budget status'))
                            ->options(CrmOptions::budgetStatuses())
                            ->native(false)
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Xác nhận Lead đã có ngân sách, đang chờ duyệt hay chưa rõ ngân sách.'),
                        Select::make('purchase_timeline')
                            ->label(UiText::get('fields.purchase_timeline', 'Purchase timeline'))
                            ->options(CrmOptions::purchaseTimelines())
                            ->native(false)
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Khung thời gian dự kiến khách hàng sẽ ra quyết định mua.'),

                        // Hàng 3: nhu cầu, ngân sách và vai trò quyết định.
                        TextInput::make('service_interest')
                            ->label(UiText::get('fields.service_interest', 'Service interest'))
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Dịch vụ hoặc nhóm giải pháp mà Lead đang quan tâm nhiều nhất.'),
                        TextInput::make('budget_amount')
                            ->label(UiText::get('fields.budget_amount', 'Budget amount'))
                            ->numeric()
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Ngân sách dự kiến mà khách hàng có thể chi cho nhu cầu này.'),
                        Select::make('decision_role')
                            ->label(UiText::get('fields.decision_role', 'Decision role'))
                            ->options(CrmOptions::decisionRoles())
                            ->native(false)
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Vai trò của đầu mối hiện tại trong quá trình ra quyết định mua hàng.'),

                        // Hàng cuối: lịch theo dõi + ghi chú. Giảm textarea còn 2 dòng để modal vừa màn hình desktop.
                        DateTimePicker::make('next_follow_up_at')
                            ->label(UiText::get('fields.next_follow_up', 'Next follow up'))
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Lịch hẹn hoặc thời điểm CRM cần theo dõi tiếp Lead này.'),
                        Textarea::make('qualification_note')
                            ->label(UiText::get('fields.qualification_note', 'Qualification note'))
                            ->rows(2)
                            ->columnSpan(2)
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Ghi chú đánh giá, bối cảnh trao đổi và thông tin quan trọng cần nhớ.'),
                    ]),
                ])
                ->action(function (array $data): void {
                    $lead = Lead::query()->with('qualification')->findOrFail($data['lead_id']);
                    if ($lead->qualification) {
                        throw new \RuntimeException(UiText::get('notifications.qualification_exists', 'This Lead already has a qualification record.'));
                    }

                    unset($data['lead_id']);
                    ContactQualification::query()->create($data + [
                        'lead_id' => $lead->id,
                        'contact_id' => $lead->contact_id,
                        'assigned_agent_profile_id' => $lead->assigned_agent_profile_id,
                        'status' => 'new',
                        'service_interest' => $data['service_interest'] ?? $lead->service_interest,
                        'estimated_value' => $lead->estimated_value,
                    ]);

                    Notification::make()->success()->title(UiText::get('notifications.qualification_created', 'Lead qualification created'))->send();
                })
                ->visible(fn (): bool => ContactQualificationResource::canCreate()),
        ];
    }
}
