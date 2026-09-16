<?php

namespace Dth\Crm\Filament\Resources\LeadResource\Pages;

use Dth\Crm\Enums\LeadIntakeStatus;
use Dth\Crm\Filament\Resources\LeadResource;
use Dth\Crm\Filament\Support\CrmDataActions;
use Dth\Crm\Filament\Support\CrmPageUi;
use Dth\Crm\Models\CrmAgentProfile;
use Dth\Crm\Models\Lead;
use Dth\Crm\Support\UiText;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    public function getTitle(): string|Htmlable
    {
        return CrmPageUi::title(UiText::get('pages.leads.title', 'Leads'), 'lead', 'amber');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.leads.subheading', 'Track incoming opportunities, ownership, service interest, and intake status throughout qualification.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createLead')
                ->label(UiText::get('pages.leads.create', 'Add Lead'))
                ->icon('heroicon-o-plus-circle')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-crm-entry-action dth-crm-entry-action--amber'])
                ->modalHeading(UiText::get('pages.leads.create', 'Add Lead'))
                ->modalIcon('heroicon-o-funnel')
                ->modalWidth(Width::FourExtraLarge)
                ->modalSubmitAction(fn (Action $action): Action => $action->label(UiText::get('common.actions.save', 'Save'))->icon('heroicon-o-check'))
                ->modalCancelAction(fn (Action $action): Action => $action->label(UiText::get('common.actions.cancel', 'Cancel'))->icon('heroicon-o-x-mark')->color('gray'))
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('lead_code')
                            ->label(UiText::get('fields.lead_code', 'Lead code'))
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Mã Lead nội bộ giúp tra cứu và đối soát nhanh trong CRM.'),
                        TextInput::make('title')
                            ->label(UiText::get('fields.title', 'Title'))
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Tiêu đề ngắn mô tả nhu cầu hoặc cơ hội bán hàng.')
                            ->columnSpanFull(),
                        TextInput::make('source')
                            ->label(UiText::get('common.fields.source', 'Source'))
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Nguồn tạo ra Lead như Landing Page, telesales, referral hoặc event.'),
                        TextInput::make('service_interest')
                            ->label(UiText::get('fields.service_interest', 'Service interest'))
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Dịch vụ hoặc nhóm dịch vụ mà Lead đang quan tâm.'),
                        TextInput::make('service_reference')
                            ->label(UiText::get('fields.service_reference', 'Service reference'))
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Mã sản phẩm hoặc mã gói dịch vụ nếu doanh nghiệp đang dùng danh mục mã nội bộ.'),
                        TextInput::make('estimated_value')
                            ->label(UiText::get('fields.estimated_value', 'Estimated value'))
                            ->numeric()
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Giá trị doanh thu dự kiến của cơ hội này.'),
                        Select::make('assigned_agent_profile_id')
                            ->label(UiText::get('fields.assigned_agent', 'CRM assignee'))
                            ->options(fn (): array => CrmAgentProfile::options(assignmentEnabledOnly: true))
                            ->searchable()
                            ->preload()
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Chọn nhân sự CRM chịu trách nhiệm theo dõi Lead ngay từ đầu.'),
                        Select::make('intake_status')
                            ->label(UiText::get('common.fields.status', 'Status'))
                            ->options(LeadIntakeStatus::options())
                            ->native(false)
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Trạng thái tiếp nhận hiện tại của Lead trong phễu CRM.'),
                    ]),
                ])
                ->action(function (array $data): void {
                    Lead::query()->create($data);
                    Notification::make()->success()->title(UiText::get('notifications.lead_created', 'Lead created'))->send();
                })
                ->visible(fn (): bool => LeadResource::canCreate()),
            ...CrmDataActions::make('leads', fn () => $this->getFilteredTableQuery(), 'crm-leads', 'Leads'),
        ];
    }
}
