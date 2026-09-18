<?php
namespace Dth\AccountManagement\Filament\Resources\AccountUserResource\Pages;

use Dth\AccountManagement\Filament\Resources\AccountUserResource;
use Dth\AccountManagement\Services\EmployeeLinkService;
use Dth\AccountManagement\Support\UiText;
use Filament\Resources\Pages\CreateRecord;

class CreateAccountUser extends CreateRecord
{
    protected static string $resource = AccountUserResource::class;
    private ?int $employeeId = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->employeeId = filled($data['employee_id'] ?? null) ? (int) $data['employee_id'] : null;
        unset($data['employee_id'], $data['password_confirmation']);
        return $data;
    }
    protected function afterCreate(): void { app(EmployeeLinkService::class)->link($this->record, $this->employeeId); }
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label(UiText::get('common.actions.save', 'Lưu'))->icon('heroicon-o-check-circle')->extraAttributes(['class' => 'dth-acc-form-action dth-acc-form-action--primary']),
            $this->getCancelFormAction()->label(UiText::get('common.actions.cancel', 'Hủy'))->icon('heroicon-o-x-mark')->color('gray')->extraAttributes(['class' => 'dth-acc-form-action dth-acc-form-action--secondary']),
        ];
    }
}
