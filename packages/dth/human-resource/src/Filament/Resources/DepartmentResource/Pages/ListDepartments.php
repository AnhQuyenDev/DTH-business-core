<?php

namespace Dth\HumanResource\Filament\Resources\DepartmentResource\Pages;

use Dth\HumanResource\Filament\Resources\DepartmentResource;
use Dth\HumanResource\Filament\Support\HumanResourceDataActions;
use Dth\HumanResource\Filament\Support\HumanResourcePageUi;
use Dth\HumanResource\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListDepartments extends ListRecords
{
    protected static string $resource = DepartmentResource::class;

    public function getTitle(): string|Htmlable
    {
        return HumanResourcePageUi::title(
            UiText::get('pages.departments.title', 'Departments'),
            'department',
            'violet',
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(UiText::get('actions.new_department', 'New department'))
                ->icon('heroicon-o-plus-circle')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-hr-entry-action dth-hr-entry-action--violet']),
            ...HumanResourceDataActions::make(
                'departments',
                fn () => $this->getFilteredTableQuery(),
                'hr-departments',
                'Departments',
            ),
        ];
    }
}
