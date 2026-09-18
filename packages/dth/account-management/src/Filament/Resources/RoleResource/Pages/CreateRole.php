<?php
namespace Dth\AccountManagement\Filament\Resources\RoleResource\Pages;
use Dth\AccountManagement\Filament\Resources\RoleResource;
use Dth\AccountManagement\Support\UiText;
use Filament\Resources\Pages\CreateRecord;
class CreateRole extends CreateRecord { protected static string $resource = RoleResource::class; protected function getFormActions(): array { return [$this->getCreateFormAction()->label(UiText::get('common.actions.save','Lưu'))->icon('heroicon-o-check-circle')->extraAttributes(['class'=>'dth-acc-form-action dth-acc-form-action--primary']),$this->getCancelFormAction()->label(UiText::get('common.actions.cancel','Hủy'))->icon('heroicon-o-x-mark')->color('gray')->extraAttributes(['class'=>'dth-acc-form-action dth-acc-form-action--secondary'])]; } }
