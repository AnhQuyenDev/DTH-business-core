<?php

namespace Dth\HumanResource\Filament\Resources\EmployeeResource\Pages;

use Dth\HumanResource\Filament\Resources\EmployeeResource;
use Dth\HumanResource\Filament\Support\HumanResourcePageUi;
use Dth\HumanResource\Support\UiText;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    public function getTitle(): string|Htmlable
    {
        return HumanResourcePageUi::title(
            UiText::get('pages.employee_create.title', 'Create employee'),
            'employee',
            'blue',
        );
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label(UiText::get('common.actions.save', 'Save'))
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->extraAttributes(['class' => 'dth-hr-form-action dth-hr-form-action--primary']),
            $this->getCancelFormAction()
                ->label(UiText::get('common.actions.cancel', 'Cancel'))
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-hr-form-action dth-hr-form-action--secondary']),
        ];
    }
}
