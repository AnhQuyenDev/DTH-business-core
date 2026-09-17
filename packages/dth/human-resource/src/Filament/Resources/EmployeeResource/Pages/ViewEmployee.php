<?php

namespace Dth\HumanResource\Filament\Resources\EmployeeResource\Pages;

use Dth\HumanResource\Filament\Resources\EmployeeResource;
use Dth\HumanResource\Filament\Support\HumanResourcePageUi;
use Dth\HumanResource\Support\UiText;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewEmployee extends ViewRecord
{
    protected static string $resource = EmployeeResource::class;

    public function getTitle(): string|Htmlable
    {
        return HumanResourcePageUi::title(
            UiText::get('pages.employee_view.title', 'Employee details'),
            'employee',
            'blue',
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label(UiText::get('common.actions.edit', 'Edit'))
                ->icon('heroicon-o-pencil-square')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-hr-entry-action dth-hr-entry-action--blue']),
        ];
    }
}
