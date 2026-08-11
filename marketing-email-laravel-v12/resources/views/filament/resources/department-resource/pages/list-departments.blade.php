<x-filament-panels::page>
    {{ $this->table }}

    @if ($this->getSelectedDepartment())
        <div class="mt-5" id="department-structure">
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('configuration.department.staff_tab') }} · {{ $this->getSelectedDepartment()->name }}
                </x-slot>
                <x-slot name="description">
                    {{ $this->getSelectedDepartment()->code }}
                </x-slot>

                <livewire:department-staff-table
                    :department-id="$this->selectedDepartmentId"
                    :key="'staff-' . $this->selectedDepartmentId"
                />
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>
