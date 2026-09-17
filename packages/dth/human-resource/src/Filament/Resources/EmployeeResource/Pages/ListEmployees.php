<?php

namespace Dth\HumanResource\Filament\Resources\EmployeeResource\Pages;

use Dth\HumanResource\Filament\Resources\EmployeeResource;
use Dth\HumanResource\Filament\Support\HumanResourceDataActions;
use Dth\HumanResource\Filament\Support\HumanResourcePageUi;
use Dth\HumanResource\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    public function getTitle(): string|Htmlable
    {
        return HumanResourcePageUi::title(
            UiText::get('pages.employees.title', 'Employees'),
            'employee',
            'blue',
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(UiText::get('actions.new_employee', 'New employee'))
                ->icon('heroicon-o-user-plus')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-hr-entry-action dth-hr-entry-action--blue']),
            ...HumanResourceDataActions::make(
                'employees',
                fn () => $this->getFilteredTableQuery(),
                'hr-employees',
                'Employees',
            ),
        ];
    }
}
