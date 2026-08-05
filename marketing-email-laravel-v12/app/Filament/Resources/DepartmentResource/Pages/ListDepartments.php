<?php

namespace App\Filament\Resources\DepartmentResource\Pages;

use App\Filament\Resources\DepartmentResource;
use App\Models\Crm\Department;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDepartments extends ListRecords
{
    protected static string $resource = DepartmentResource::class;

    protected static string $view = 'filament.resources.department-resource.pages.list-departments';

    public ?int $selectedDepartmentId = null;

    public function mount(): void
    {
        parent::mount();

        $requested = (int) request()->query('department');
        if ($requested > 0) {
            $this->selectedDepartmentId = $requested;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label(__('action.create_department')),
        ];
    }

    public function selectDepartment(?int $departmentId): void
    {
        $this->selectedDepartmentId = $departmentId;
    }

    public function getSelectedDepartment(): ?Department
    {
        return $this->selectedDepartmentId
            ? Department::query()->find($this->selectedDepartmentId)
            : null;
    }
}
