<?php

namespace Dth\Crm\Filament\Resources\CrmAgentProfileResource\Pages;

use Dth\Crm\Filament\Resources\CrmAgentProfileResource;
use Dth\Crm\Filament\Support\CrmPageUi;
use Dth\Crm\Models\CrmAgentProfile;
use Dth\Crm\Support\UiText;
use Dth\HumanResource\Enums\EmploymentStatus;
use Dth\HumanResource\Models\Employee;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class ListCrmAgentProfiles extends ListRecords
{
    protected static string $resource = CrmAgentProfileResource::class;

    public function getTitle(): string|Htmlable
    {
        return CrmPageUi::title(UiText::get('pages.agent_profiles.title', 'CRM team settings'), 'agent', 'violet');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.agent_profiles.subheading', 'Connect HR employees to CRM assignment capacity without duplicating employee master data.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createAgentProfile')
                ->label(UiText::get('pages.agent_profiles.create', 'Create CRM staff configuration'))
                ->icon('heroicon-o-user-plus')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-crm-entry-action dth-crm-entry-action--violet'])
                ->modalHeading(UiText::get('pages.agent_profiles.create', 'Create CRM staff configuration'))
                ->modalIcon('heroicon-o-identification')
                ->modalWidth(Width::ThreeExtraLarge)
                ->modalSubmitAction(fn (Action $action): Action => $action->label(UiText::get('common.actions.save', 'Save'))->icon('heroicon-o-check'))
                ->modalCancelAction(fn (Action $action): Action => $action->label(UiText::get('common.actions.cancel', 'Cancel'))->icon('heroicon-o-x-mark')->color('gray'))
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('employee_id')
                            ->label(UiText::get('fields.hr_employee', 'HR employee'))
                            ->options(fn (): array => Employee::query()
                                ->where('employment_status', '!=', EmploymentStatus::Resigned->value)
                                ->whereNotIn('id', CrmAgentProfile::query()->select('employee_id'))
                                ->orderBy('full_name')
                                ->get(['id', 'employee_code', 'full_name'])
                                ->mapWithKeys(fn (Employee $employee): array => [$employee->id => $employee->displayLabel()])
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull()
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Chọn nhân viên HR sẽ tham gia CRM. Dữ liệu nhân sự gốc vẫn nằm ở module Human Resource.'),
                        Toggle::make('assignment_enabled')
                            ->label(UiText::get('fields.assignment_enabled', 'Can receive CRM assignments'))
                            ->default(true)
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Bật khi nhân sự này được phép nhận Lead hoặc khách hàng từ CRM.'),
                        TextInput::make('lead_capacity')
                            ->label(UiText::get('fields.lead_capacity', 'Lead capacity'))
                            ->numeric()
                            ->default(50)
                            ->minValue(0)
                            ->required()
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Số lượng Lead tối đa mà nhân sự này có thể phụ trách cùng lúc.'),
                        TextInput::make('customer_capacity')
                            ->label(UiText::get('fields.customer_capacity', 'Customer capacity'))
                            ->numeric()
                            ->minValue(0)
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Số lượng khách hàng tối đa mà nhân sự này có thể chăm sóc.'),
                        TextInput::make('distribution_weight')
                            ->label(UiText::get('fields.distribution_weight', 'Distribution weight'))
                            ->numeric()
                            ->default(1)
                            ->minValue(0.01)
                            ->required()
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Trọng số dùng trong chiến lược phân phối tự động. Giá trị càng lớn thì càng dễ nhận phân công hơn.'),
                    ]),
                ])
                ->action(function (array $data): void {
                    CrmAgentProfile::query()->create($data);
                    Notification::make()->success()->title(UiText::get('notifications.agent_profile_created', 'CRM staff configuration created'))->send();
                })
                ->visible(fn (): bool => CrmAgentProfileResource::canCreate()),
        ];
    }
}
