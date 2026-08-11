<x-filament-panels::page>
    <x-filament::section>
        <div class="overflow-x-auto">
            <table class="dth-config-matrix">
                <thead>
                    <tr>
                        <th>{{ __('field.system_role') }}</th>
                        <th>{{ __('field.role_code') }}</th>
                        <th>{{ __('field.staff_required') }}</th>
                        <th>{{ __('field.permission_summary') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        <tr>
                            <td>
                                <x-filament::badge :color="$role['color'] ?? 'gray'">
                                    {{ $role['label'] }}
                                </x-filament::badge>
                            </td>
                            <td class="font-mono text-xs">{{ $role['code'] }}</td>
                            <td>{{ $role['staff_required'] }}</td>
                            <td>{{ $role['summary'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
