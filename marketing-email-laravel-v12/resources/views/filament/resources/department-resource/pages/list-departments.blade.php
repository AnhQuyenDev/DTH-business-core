<x-filament-panels::page>
    {{ $this->table }}

    @if ($this->getSelectedDepartment())
        <div class="mt-6">
            <x-filament::section>
                <x-slot name="heading">
                    {{ $this->getSelectedDepartment()->name }}
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">
                        ({{ $this->getSelectedDepartment()->code }})
                    </span>
                </x-slot>

                <div x-data="{ tab: 'positions' }">
                    <div class="flex gap-1 border-b border-gray-200 dark:border-white/10">
                        <button
                            type="button"
                            @click="tab = 'positions'"
                            :class="tab === 'positions'
                                ? 'border-primary-600 text-primary-600 border-b-2 font-medium dark:text-primary-400 dark:border-primary-400'
                                : 'border-b-2 border-transparent text-gray-500 dark:text-gray-400'"
                            class="px-4 py-2 text-sm"
                        >
                            {{ __('resource.position.plural') }}
                        </button>
                        <button
                            type="button"
                            @click="tab = 'staff'"
                            :class="tab === 'staff'
                                ? 'border-primary-600 text-primary-600 border-b-2 font-medium dark:text-primary-400 dark:border-primary-400'
                                : 'border-b-2 border-transparent text-gray-500 dark:text-gray-400'"
                            class="px-4 py-2 text-sm"
                        >
                            {{ __('resource.staff.plural') }}
                        </button>
                    </div>

                    <div x-show="tab === 'positions'" class="pt-4">
                        <livewire:department-positions-table
                            :department-id="$this->selectedDepartmentId"
                            :key="'positions-' . $this->selectedDepartmentId"
                        />
                    </div>

                    <div x-show="tab === 'staff'" class="pt-4">
                        <livewire:department-staff-table
                            :department-id="$this->selectedDepartmentId"
                            :key="'staff-' . $this->selectedDepartmentId"
                        />
                    </div>
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>
