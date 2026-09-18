<?php
namespace Dth\AccountManagement\Filament\Resources\RoleResource\Pages;
use Dth\AccountManagement\Filament\Resources\RoleResource;
use Dth\AccountManagement\Support\UiText;
use Filament\Resources\Pages\CreateRecord;
class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;
    protected array $pendingPermissionIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        [$data, $this->pendingPermissionIds] = RoleResource::splitPermissionData($data);
        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->permissions()->sync($this->pendingPermissionIds);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label(UiText::get('common.actions.save', 'Lưu'))->icon('heroicon-o-check-circle')->extraAttributes(['class' => 'dth-acc-form-action dth-acc-form-action--primary']),
            $this->getCreateAnotherFormAction()->label(UiText::get('common.actions.create_and_create_another', 'Tạo và tạo thêm'))->icon('heroicon-o-plus-circle')->color('gray')->extraAttributes(['class' => 'dth-acc-form-action dth-acc-form-action--secondary']),
            $this->getCancelFormAction()->label(UiText::get('common.actions.cancel', 'Hủy'))->icon('heroicon-o-x-mark')->color('gray')->extraAttributes(['class' => 'dth-acc-form-action dth-acc-form-action--secondary']),
        ];
    }
}

