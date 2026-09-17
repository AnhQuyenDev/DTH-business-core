<?php

namespace Dth\Crm\Filament\Resources\CompanyResource\Pages;

use Dth\Crm\Enums\CompanyLifecycleStage;
use Dth\Crm\Filament\Resources\CompanyResource;
use Dth\Crm\Filament\Support\CrmDataActions;
use Dth\Crm\Filament\Support\CrmPageUi;
use Dth\Crm\Models\Company;
use Dth\Crm\Support\UiText;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class ListCompanys extends ListRecords
{
    protected static string $resource = CompanyResource::class;

    public function getTitle(): string|Htmlable
    {
        return CrmPageUi::title(UiText::get('pages.companies.title', 'Companies'), 'company', 'blue');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.companies.subheading', 'Manage company master data, tax identity, lifecycle stage, and CRM relationships.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createCompany')
                ->label(UiText::get('pages.companies.create', 'Add company'))
                ->icon('heroicon-o-building-office-2')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-crm-entry-action dth-crm-entry-action--blue'])
                ->modalHeading(UiText::get('pages.companies.create', 'Add company'))
                ->modalIcon('heroicon-o-building-office-2')
                ->modalWidth(Width::FourExtraLarge)
                ->modalSubmitAction(fn (Action $action): Action => $action->label(UiText::get('common.actions.save', 'Save'))->icon('heroicon-o-check'))
                ->modalCancelAction(fn (Action $action): Action => $action->label(UiText::get('common.actions.cancel', 'Cancel'))->icon('heroicon-o-x-mark')->color('gray'))
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('legal_name')
                            ->label(UiText::get('fields.legal_name', 'Legal name'))
                            ->required()
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Tên pháp lý hoặc tên đầy đủ của doanh nghiệp.')
                            ->columnSpanFull(),
                        TextInput::make('tax_code')
                            ->label(UiText::get('fields.tax_code', 'Tax code'))
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Mã số thuế dùng để nhận diện và đối soát doanh nghiệp.'),
                        TextInput::make('email_domain')
                            ->label(UiText::get('fields.email_domain', 'Email domain'))
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Tên miền email chung của doanh nghiệp, ví dụ company.com.'),
                        TextInput::make('phone')
                            ->label(UiText::get('fields.phone', 'Phone'))
                            ->tel()
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Số điện thoại tổng đài hoặc số liên hệ chính của doanh nghiệp.'),
                        TextInput::make('industry')
                            ->label(UiText::get('fields.industry', 'Industry'))
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Ngành nghề hoặc lĩnh vực hoạt động chính của doanh nghiệp.'),
                        Select::make('lifecycle_stage')
                            ->label(UiText::get('fields.lifecycle_stage', 'Lifecycle stage'))
                            ->options(CompanyLifecycleStage::options())
                            ->native(false)
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Giai đoạn hiện tại của doanh nghiệp trong vòng đời CRM.'),
                        Textarea::make('address')
                            ->label(UiText::get('fields.address', 'Address'))
                            ->rows(3)
                            ->columnSpanFull()
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Địa chỉ doanh nghiệp dùng cho liên hệ, hồ sơ và báo giá.'),
                    ]),
                ])
                ->action(function (array $data): void {
                    Company::query()->create($data);
                    Notification::make()->success()->title(UiText::get('notifications.company_created', 'Company created'))->send();
                })
                ->visible(fn (): bool => CompanyResource::canCreate()),
            ...CrmDataActions::make('companies', fn () => $this->getFilteredTableQuery(), 'crm-companies', 'Companies'),
        ];
    }
}
