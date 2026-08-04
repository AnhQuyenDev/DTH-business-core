<x-filament-widgets::widget>
    <style>
        #utm-report-lp {
            background-color: #111827;
            color: #ffffff;
        }
        #utm-report-lp option {
            background-color: #ffffff;
            color: #111827;
        }
    </style>

    <x-filament::section>
        <x-slot name="heading">
            {{ __('action.utm_report') }}
        </x-slot>

        <div class="flex flex-wrap items-end gap-6">
            <div class="flex-1 min-w-0">
                <label for="utm-report-lp" class="mb-1 block text-base font-semibold text-white">
                    {{ __('field.landing_page') }}
                </label>
                <select
                    id="utm-report-lp"
                    wire:model.live="landingPageId"
                    class="fi-select-input block w-full rounded-lg border-gray-300 bg-gray-950 text-sm text-white shadow-sm outline-none transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 disabled:opacity-70"
                >
                    <option value="">{{ __('helper.utm_select_prompt') }}</option>
                    @foreach ($this->getLandingPageOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <x-filament::button
                color="info"
                icon="heroicon-o-arrow-down-tray"
                wire:click="export"
                wire:loading.attr="disabled"
                :disabled="! $landingPageId"
            >
                {{ __('action.export_utm') }}
            </x-filament::button>
        </div>

        <div class="mt-2">
            @if ($landingPageId)
                {!! $this->getReport() !!}
            @else
                <div class="rounded-lg bg-amber-50 px-4 py-3 text-center text-sm text-gray-600 dark:bg-amber-400/10 dark:text-gray-400">
                    {{ __('helper.utm_select_prompt') }}
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
