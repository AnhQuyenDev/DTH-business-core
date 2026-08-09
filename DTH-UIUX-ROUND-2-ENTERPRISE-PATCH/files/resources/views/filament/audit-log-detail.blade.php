@php
    $changes = $presenter::changes($log);
@endphp

<div class="space-y-5">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
        <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('uiux.audit.actor') }}</div>
            <div class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $presenter::actor($log) }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('uiux.audit.time') }}</div>
            <div class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $log->created_at?->format('d/m/Y H:i:s') }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('uiux.audit.module_label') }}</div>
            <div class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $presenter::module($log) }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('uiux.audit.subject') }}</div>
            <div class="mt-1 font-semibold text-gray-950 dark:text-white">{{ $presenter::subject($log) }}</div>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
        <div class="text-sm font-semibold text-gray-950 dark:text-white">{{ $presenter::activity($log) }}</div>
        <div class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $presenter::description($log) }}</div>
    </div>

    @if($changes !== [])
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
            <div class="border-b border-gray-200 px-4 py-3 text-sm font-semibold dark:border-white/10">{{ __('uiux.audit.changes') }}</div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-white/5 dark:text-gray-400">
                        <tr>
                            <th class="px-4 py-3">{{ __('field.field') }}</th>
                            <th class="px-4 py-3">{{ __('uiux.audit.before') }}</th>
                            <th class="px-4 py-3">{{ __('uiux.audit.after') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach($changes as $change)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $change['field'] }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $change['before'] }}</td>
                                <td class="px-4 py-3 text-gray-950 dark:text-white">{{ $change['after'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
