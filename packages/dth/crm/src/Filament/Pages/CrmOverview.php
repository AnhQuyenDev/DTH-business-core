<?php

namespace Dth\Crm\Filament\Pages;

use Dth\Crm\Enums\ContactType;
use Dth\Crm\Enums\LeadIntakeStatus;
use Dth\Crm\Filament\Navigation\CrmNavigationGroup;
use Dth\Crm\Filament\Resources\ContactResource;
use Dth\Crm\Filament\Resources\LeadResource;
use Dth\Crm\Filament\Support\CrmPageUi;
use Dth\Crm\Models\Contact;
use Dth\Crm\Models\CrmAgentProfile;
use Dth\Crm\Models\Lead;
use Dth\Crm\Services\CrmAnalyticsService;
use Dth\Crm\Support\CrmAuthorization;
use Dth\Crm\Support\UiText;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class CrmOverview extends Page
{
    protected string $view = 'dth-crm::filament.pages.crm-overview';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string|\UnitEnum|null $navigationGroup = CrmNavigationGroup::Crm;
    protected static ?int $navigationSort = 0;

    /** @var array<string, mixed> */
    public array $snapshot = [];

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.dashboard', 'CRM overview', context: 'navigation');
    }

    public function getTitle(): string|Htmlable
    {
        return CrmPageUi::title(UiText::get('dashboard.title', 'CRM Analytics'), 'report');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get(
            'dashboard.subheading',
            'Track contacts, leads, qualification, conversion, customers and team workload in one place.',
        );
    }

    public function mount(): void
    {
        $this->snapshot = app(CrmAnalyticsService::class)->snapshot();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('newContact')
                ->label(UiText::get('actions.new_contact', 'New contact'))
                ->icon('heroicon-o-user-plus')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-crm-entry-action dth-crm-entry-action--teal'])
                ->modalHeading(UiText::get('pages.contacts.create', 'Add contact'))
                ->modalIcon('heroicon-o-user-plus')
                ->modalWidth(Width::ThreeExtraLarge)
                ->modalSubmitAction(fn (Action $action): Action => $action
                    ->label(UiText::get('common.actions.save', 'Save'))
                    ->icon('heroicon-o-check-circle'))
                ->modalCancelAction(fn (Action $action): Action => $action
                    ->label(UiText::get('common.actions.cancel', 'Cancel'))
                    ->icon('heroicon-o-x-mark')
                    ->color('gray'))
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('contact_code')
                            ->label(UiText::get('fields.contact_code', 'Contact code'))
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Mã nhận diện nội bộ của liên hệ trong CRM.'),
                        Select::make('type')
                            ->label(UiText::get('fields.contact_type', 'Contact type'))
                            ->options(ContactType::options())
                            ->native(false)
                            ->required()
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Chọn liên hệ cá nhân hoặc liên hệ doanh nghiệp để phân loại đúng dữ liệu.'),
                        TextInput::make('display_name')
                            ->label(UiText::get('fields.display_name', 'Display name'))
                            ->required()
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Tên hiển thị chính dùng khi tìm kiếm và liên kết dữ liệu CRM.')
                            ->columnSpanFull(),
                        TextInput::make('email')
                            ->label(UiText::get('common.fields.email', 'Email'))
                            ->email()
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Email dùng để liên hệ và đối soát với các module liên quan.'),
                        TextInput::make('phone')
                            ->label(UiText::get('fields.phone', 'Phone'))
                            ->tel()
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Số điện thoại dùng cho chăm sóc, gọi ra và xác thực thông tin.'),
                        TextInput::make('source')
                            ->label(UiText::get('common.fields.source', 'Source'))
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Nguồn phát sinh liên hệ, ví dụ Website, Form, Sales hoặc Event.'),
                    ]),
                ])
                ->action(function (array $data): void {
                    Contact::query()->create($data);

                    Notification::make()
                        ->success()
                        ->title(UiText::get('notifications.contact_created', 'Contact created'))
                        ->send();

                    $this->snapshot = app(CrmAnalyticsService::class)->snapshot();
                })
                ->visible(fn (): bool => ContactResource::canCreate()),

            Action::make('newLead')
                ->label(UiText::get('actions.new_lead', 'New Lead'))
                ->icon('heroicon-o-plus-circle')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-crm-entry-action dth-crm-entry-action--amber'])
                ->modalHeading(UiText::get('pages.leads.create', 'Add Lead'))
                ->modalIcon('heroicon-o-funnel')
                ->modalWidth(Width::FourExtraLarge)
                ->modalSubmitAction(fn (Action $action): Action => $action
                    ->label(UiText::get('common.actions.save', 'Save'))
                    ->icon('heroicon-o-check-circle'))
                ->modalCancelAction(fn (Action $action): Action => $action
                    ->label(UiText::get('common.actions.cancel', 'Cancel'))
                    ->icon('heroicon-o-x-mark')
                    ->color('gray'))
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

                    Notification::make()
                        ->success()
                        ->title(UiText::get('notifications.lead_created', 'Lead created'))
                        ->send();

                    $this->snapshot = app(CrmAnalyticsService::class)->snapshot();
                })
                ->visible(fn (): bool => LeadResource::canCreate()),
        ];
    }

    public static function canAccess(): bool
    {
        return app(CrmAuthorization::class)->allows('crm.view');
    }
}
