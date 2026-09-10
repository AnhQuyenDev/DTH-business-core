@php
    use Dth\Email\Filament\Support\StatusColor;
    use Dth\Email\Support\UiText;
@endphp

<x-filament::section
    :heading="UiText::get('insights.heading', 'Statistical insights')"
    icon="heroicon-o-light-bulb"
>
    <div class="space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $report->summary }}</p>
            @if ($report->score > 0)
                <div class="flex items-center gap-2">
                    <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        {{ UiText::get('insights.health_score', 'Health score') }}
                    </span>
                    <x-filament::badge :color="StatusColor::for($report->score >= 85 ? 'healthy' : ($report->score >= 65 ? 'warning' : 'critical'))">
                        {{ $report->score }}/100
                    </x-filament::badge>
                </div>
            @endif
        </div>

        @if (count($report->items))
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                @foreach ($report->items as $item)
                    <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                        <div class="mb-2 flex items-center gap-2">
                            <x-filament::badge :color="StatusColor::for($item->severity)">
                                {{ UiText::get('insights.severity.'.$item->severity, ucfirst($item->severity)) }}
                            </x-filament::badge>
                            <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ $item->title }}</span>
                        </div>
                        <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">{{ $item->body }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-xl border border-dashed border-gray-300 px-4 py-6 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                {{ UiText::get('insights.no_findings', 'No significant statistical finding for the available data.') }}
            </div>
        @endif
    </div>
</x-filament::section>
