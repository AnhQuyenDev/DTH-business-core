<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('page.title.lead_pipeline') }}
        </x-slot>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
            @foreach ($this->getStages() as $stage)
                <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="mb-3 flex items-center justify-between">
                        <a
                            href="{{ $this->getTabUrl($stage['tab']) }}"
                            class="text-sm font-semibold text-primary-600 hover:underline dark:text-primary-400"
                        >
                            {{ $stage['label'] }}
                        </a>

                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            {{ $stage['count'] }}
                        </span>
                    </div>

                    <div class="space-y-2">
                        @forelse ($stage['items'] as $item)
                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-2 py-2 text-xs dark:border-gray-700 dark:bg-gray-800/50">
                                <div class="font-medium text-gray-900 dark:text-gray-100">
                                    {{ $item['name'] }}
                                </div>

                                @if ($item['email'])
                                    <div class="mt-1 truncate text-gray-600 dark:text-gray-300">
                                        {{ $item['email'] }}
                                    </div>
                                @endif

                                @if ($item['staff'])
                                    <div class="mt-1 text-gray-500 dark:text-gray-400">
                                        {{ __('field.assigned_staff') }}: {{ $item['staff'] }}
                                    </div>
                                @endif
                            </div>
                        @empty
                            <div class="rounded-lg border border-dashed border-gray-300 px-2 py-3 text-center text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
                                {{ __('table.empty') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
