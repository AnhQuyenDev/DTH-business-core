<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('role_permissions.heading') }}
        </x-slot>

        <x-slot name="description">
            {{ __('role_permissions.description') }}
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left dark:border-white/10">
                        <th class="px-4 py-3">{{ __('field.system_role') }}</th>
                        <th class="px-4 py-3">{{ __('field.role_code') }}</th>
                        <th class="px-4 py-3">{{ __('field.staff_required') }}</th>
                        <th class="px-4 py-3">{{ __('field.permission_summary') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr class="border-b border-gray-100 align-top dark:border-white/5">
                            <td class="px-4 py-3 font-medium">{{ $role['label'] }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $role['code'] }}</td>
                            <td class="px-4 py-3">{{ $role['staff_required'] }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $role['summary'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4 rounded-lg bg-gray-50 p-4 text-sm text-gray-600 dark:bg-white/5 dark:text-gray-300">
            {{ __('role_permissions.organization_rule_note') }}
        </div>
    </x-filament::section>
</x-filament-panels::page>
