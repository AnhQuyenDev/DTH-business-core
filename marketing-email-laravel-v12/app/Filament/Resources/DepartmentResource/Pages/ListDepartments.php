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

    public function getSubheading(): ?string
    {
        return __('configuration.department.list_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('configuration.department.create'))
                ->icon('heroicon-o-plus-circle')
                ->modalIcon('heroicon-o-building-office-2')
                ->modalIconColor('primary')
                ->modalHeading(__('configuration.department.create'))
                ->modalDescription(__('configuration.department.create_subheading'))
                ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->icon('heroicon-o-plus-circle'))
                ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->icon('heroicon-o-x-mark'))
                ->modalWidth('5xl')
                ->extraModalFooterActions(fn (Actions\CreateAction $action): array => [
                    $action->makeModalSubmitAction('createAnother', arguments: ['another' => true])
                        ->label(__('filament-actions::create.single.modal.actions.create_another.label'))
                        ->icon('heroicon-o-plus'),
                ]),
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
