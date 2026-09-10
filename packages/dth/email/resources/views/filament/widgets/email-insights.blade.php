@php
    use Dth\Email\Filament\Support\StatusColor;
    use Dth\Email\Support\UiText;

    $compact = (bool) ($compact ?? false);

    $scoreState = match (true) {
        $report->score >= 85 => 'healthy',
        $report->score >= 65 => 'warning',
        $report->score > 0 => 'critical',
        default => 'unknown',
    };

    $scoreStatusLabel = match ($scoreState) {
        'healthy' => UiText::get('insights.status.strong', 'Healthy'),
        'warning' => UiText::get('insights.status.watch', 'Monitor'),
        'critical' => UiText::get('insights.status.risk', 'Needs review'),
        default => UiText::get('insights.status.none', 'No score'),
    };

    $scoreBarClass = match ($scoreState) {
        'healthy' => 'bg-success-500',
        'warning' => 'bg-warning-500',
        'critical' => 'bg-danger-500',
        default => 'bg-gray-400',
    };

    $severityMeta = [
        'positive' => [
            'icon' => 'heroicon-m-arrow-trending-up',
            'panel' => 'border-success-200 bg-success-50/60 dark:border-success-500/20 dark:bg-success-500/10',
            'iconWrap' => 'bg-success-100 text-success-700 dark:bg-success-500/15 dark:text-success-300',
        ],
        'warning' => [
            'icon' => 'heroicon-m-exclamation-triangle',
            'panel' => 'border-warning-200 bg-warning-50/60 dark:border-warning-500/20 dark:bg-warning-500/10',
            'iconWrap' => 'bg-warning-100 text-warning-700 dark:bg-warning-500/15 dark:text-warning-300',
        ],
        'critical' => [
            'icon' => 'heroicon-m-x-circle',
            'panel' => 'border-danger-200 bg-danger-50/60 dark:border-danger-500/20 dark:bg-danger-500/10',
            'iconWrap' => 'bg-danger-100 text-danger-700 dark:bg-danger-500/15 dark:text-danger-300',
        ],
        'neutral' => [
            'icon' => 'heroicon-m-information-circle',
            'panel' => 'border-gray-200 bg-gray-50/80 dark:border-white/10 dark:bg-white/5',
            'iconWrap' => 'bg-gray-100 text-gray-700 dark:bg-white/10 dark:text-gray-300',
        ],
    ];

    $severityPriority = [
        'critical' => 0,
        'warning' => 1,
        'positive' => 2,
        'neutral' => 3,
    ];

    $items = collect($report->items)
        ->sortBy(fn ($item) => $severityPriority[$item->severity] ?? 9)
        ->values();

    $visibleItems = $compact ? $items->take(3) : $items;
    $hiddenCount = max(0, $items->count() - $visibleItems->count());
@endphp

<x-filament-widgets::widget class="fi-wi-email-insights">
    <x-filament::section
        :heading="UiText::get('insights.heading', 'Statistical insights')"
        icon="heroicon-o-light-bulb"
    >
        @if ($compact)
            <div class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_170px]">
                    <div class="min-w-0 rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-start gap-3">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-100 text-primary-600 dark:bg-primary-500/15 dark:text-primary-300">
                                <x-filament::icon icon="heroicon-m-chart-bar-square" class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ UiText::get('insights.executive_summary', 'Executive summary') }}
                                </p>
                                <p class="mt-1 text-sm font-medium leading-6 text-gray-800 dark:text-gray-100">
                                    {{ $report->summary }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ UiText::get('insights.health_score', 'Health score') }}
                            </span>
                            <x-filament::badge :color="StatusColor::for($scoreState)">
                                {{ $scoreStatusLabel }}
                            </x-filament::badge>
                        </div>

                        <div class="mt-2 flex items-end gap-1">
                            <span class="text-3xl font-bold tracking-tight text-gray-950 dark:text-white">
                                {{ $report->score }}
                            </span>
                            <span class="pb-1 text-sm font-medium text-gray-400">/100</span>
                        </div>

                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
                            <div
                                class="h-full rounded-full {{ $scoreBarClass }}"
                                style="width: {{ max(0, min(100, $report->score)) }}%"
                            ></div>
                        </div>
                    </div>
                </div>

                @if ($visibleItems->isNotEmpty())
                    <div class="space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                                {{ UiText::get('insights.key_findings', 'Key findings') }}
                            </h3>
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $items->count() }} {{ UiText::get('insights.findings', 'findings') }}
                            </span>
                        </div>

                        @foreach ($visibleItems as $item)
                            @php($meta = $severityMeta[$item->severity] ?? $severityMeta['neutral'])

                            <div class="rounded-xl border px-3.5 py-3 {{ $meta['panel'] }}">
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $meta['iconWrap'] }}">
                                        <x-filament::icon :icon="$meta['icon']" class="h-4 w-4" />
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-sm font-semibold text-gray-950 dark:text-white">
                                                {{ $item->title }}
                                            </p>
                                            <x-filament::badge :color="StatusColor::for($item->severity)">
                                                {{ UiText::get('insights.severity.'.$item->severity, ucfirst($item->severity)) }}
                                            </x-filament::badge>
                                        </div>

                                        <p class="mt-1 line-clamp-2 text-xs leading-5 text-gray-600 dark:text-gray-300">
                                            {{ $item->body }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        @if ($hiddenCount > 0)
                            <p class="pt-1 text-right text-xs font-medium text-gray-500 dark:text-gray-400">
                                {{ UiText::get('insights.more_findings', '+:count additional findings in the detailed report.', ['count' => $hiddenCount]) }}
                            </p>
                        @endif
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-gray-300 px-4 py-6 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                        {{ UiText::get('insights.no_findings', 'No significant statistical finding for the available data.') }}
                    </div>
                @endif
            </div>
        @else
            <div class="space-y-5">
                <div class="grid gap-4 xl:grid-cols-3">
                    <div class="rounded-2xl border border-gray-200 bg-gray-50/70 p-5 xl:col-span-2 dark:border-white/10 dark:bg-white/5">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary-100 text-primary-600 dark:bg-primary-500/15 dark:text-primary-300">
                                <x-filament::icon icon="heroicon-m-chart-bar-square" class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ UiText::get('insights.executive_summary', 'Executive summary') }}
                                </p>
                                <p class="mt-2 text-sm leading-6 text-gray-700 dark:text-gray-200">
                                    {{ $report->summary }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-gray-200 p-5 dark:border-white/10">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ UiText::get('insights.health_score', 'Health score') }}
                                </p>
                                <p class="mt-1 text-3xl font-bold tracking-tight text-gray-950 dark:text-white">
                                    {{ $report->score }}<span class="text-base font-medium text-gray-400">/100</span>
                                </p>
                            </div>
                            <x-filament::badge :color="StatusColor::for($scoreState)">
                                {{ $scoreStatusLabel }}
                            </x-filament::badge>
                        </div>
                        <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
                            <div class="h-full rounded-full {{ $scoreBarClass }}" style="width: {{ max(0, min(100, $report->score)) }}%"></div>
                        </div>
                    </div>
                </div>

                @if ($visibleItems->isNotEmpty())
                    <div class="space-y-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-950 dark:text-white">
                                {{ UiText::get('insights.key_findings', 'Key findings') }}
                            </h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ UiText::get('insights.key_findings_description', 'Important points extracted from the current dataset.') }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-3 xl:grid-cols-2">
                            @foreach ($visibleItems as $item)
                                @php($meta = $severityMeta[$item->severity] ?? $severityMeta['neutral'])
                                <div class="rounded-2xl border p-4 {{ $meta['panel'] }}">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $meta['iconWrap'] }}">
                                            <x-filament::icon :icon="$meta['icon']" class="h-5 w-5" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <x-filament::badge :color="StatusColor::for($item->severity)">
                                                    {{ UiText::get('insights.severity.'.$item->severity, ucfirst($item->severity)) }}
                                                </x-filament::badge>
                                                @if (filled($item->metric))
                                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">
                                                        {{ UiText::get('insights.related_metric', 'Metric') }}:
                                                        {{ UiText::get('dashboard.metrics.'.$item->metric, str($item->metric)->replace('_', ' ')->title()->toString()) }}
                                                    </span>
                                                @endif
                                            </div>
                                            <h4 class="mt-2 text-sm font-semibold leading-6 text-gray-950 dark:text-white">
                                                {{ $item->title }}
                                            </h4>
                                            <p class="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">
                                                {{ $item->body }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-gray-300 px-4 py-8 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
                        {{ UiText::get('insights.no_findings', 'No significant statistical finding for the available data.') }}
                    </div>
                @endif
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
