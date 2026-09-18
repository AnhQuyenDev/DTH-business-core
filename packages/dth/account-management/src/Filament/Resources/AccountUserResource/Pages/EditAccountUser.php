<?php
namespace Dth\AccountManagement\Filament\Resources\AccountUserResource\Pages;

use Dth\AccountManagement\Filament\Resources\AccountUserResource;
use Dth\AccountManagement\Services\EmployeeLinkService;
use Dth\AccountManagement\Support\UiText;
use Filament\Resources\Pages\EditRecord;

class EditAccountUser extends EditRecord
{
    protected static string $resource = AccountUserResource::class;
    private ?int $employeeId = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['employee_id'] = app(EmployeeLinkService::class)->employeeIdForUser((int) $this->record->id);
        $data['password'] = null;
        $data['password_confirmation'] = null;
        return $data;
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->employeeId = filled($data['employee_id'] ?? null) ? (int) $data['employee_id'] : null;
        unset($data['employee_id'], $data['password_confirmation']);
        if (blank($data['password'] ?? null)) unset($data['password']);
        return $data;
    }
    protected function afterSave(): void
    {
        $links = app(EmployeeLinkService::class);
        $links->link($this->record, $this->employeeId);
        $links->resolveIdentityRequestIfSatisfied($this->record);
    }
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()->label(UiText::get('common.actions.save', 'Lưu'))->icon('heroicon-o-check-circle')->extraAttributes(['class' => 'dth-acc-form-action dth-acc-form-action--primary']),
            $this->getCancelFormAction()->label(UiText::get('common.actions.cancel', 'Hủy'))->icon('heroicon-o-x-mark')->color('gray')->extraAttributes(['class' => 'dth-acc-form-action dth-acc-form-action--secondary']),
        ];
    }
}
