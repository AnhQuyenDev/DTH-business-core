<x-filament-panels::page>
    <div class="space-y-6">
        
        <x-filament::section class="overflow-hidden">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-medium tracking-tight text-gray-950 dark:text-white">{{ __('page.campaign_report.title') }}</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('page.campaign_report.description') }}</p>
                </div>
                <div class="w-full sm:w-72">
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="campaignId" class="font-medium">
                            @foreach ($this->campaignOptions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            </div>
        </x-filament::section>

        <div class="grid gap-4 grid-cols-1 md:grid-cols-2 xl:grid-cols-4">
            <div class="p-6 bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-white/10 flex items-center justify-between transition hover:shadow-md">
                <div class="space-y-2">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('report.total_recipients') }}</span>
                    <div class="text-3xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $this->stats['total'] }}</div>
                </div>
                <div class="p-3 bg-gray-50 rounded-xl dark:bg-white/5 text-gray-400">
                    <x-filament::icon icon="heroicon-m-users" class="h-6 w-6" />
                </div>
            </div>

            <div class="p-6 bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-white/10 flex items-center justify-between transition hover:shadow-md">
                <div class="space-y-2">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('report.sent') }}</span>
                    <div class="text-3xl font-bold tracking-tight text-emerald-600 dark:text-emerald-400">{{ $this->stats['sent'] }}</div>
                </div>
                <div class="p-3 bg-emerald-50 rounded-xl dark:bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                    <x-filament::icon icon="heroicon-m-paper-airplane" class="h-6 w-6" />
                </div>
            </div>

            <div class="p-6 bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-white/10 flex items-center justify-between transition hover:shadow-md">
                <div class="space-y-2">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('report.failed') }}</span>
                    <div class="text-3xl font-bold tracking-tight text-danger-600 dark:text-danger-400">{{ $this->stats['failed'] }}</div>
                </div>
                <div class="p-3 bg-danger-50 rounded-xl dark:bg-danger-500/10 text-danger-600 dark:text-danger-400">
                    <x-filament::icon icon="heroicon-m-exclamation-circle" class="h-6 w-6" />
                </div>
            </div>

            <div class="p-6 bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-white/10 flex items-center justify-between transition hover:shadow-md">
                <div class="space-y-2">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('report.unsubscribed') }}</span>
                    <div class="text-3xl font-bold tracking-tight text-amber-600 dark:text-amber-400">{{ $this->stats['unsubscribed'] }}</div>
                </div>
                <div class="p-3 bg-amber-50 rounded-xl dark:bg-amber-500/10 text-amber-600 dark:text-amber-400">
                    <x-filament::icon icon="heroicon-m-user-minus" class="h-6 w-6" />
                </div>
            </div>
        </div>

        <div class="grid gap-4 grid-cols-1 md:grid-cols-3">
            <div class="p-6 bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-white/10 flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('report.opened') }}</span>
                    <div class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $this->stats['opened'] }}</div>
                </div>
                <div class="p-2.5 bg-gray-50 rounded-lg dark:bg-white/5 text-gray-500 dark:text-gray-400">
                    <x-filament::icon icon="heroicon-m-envelope-open" class="h-5 w-5" />
                </div>
            </div>

            <div class="p-6 bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-white/10 flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('report.clicked') }}</span>
                    <div class="text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $this->stats['clicked'] }}</div>
                </div>
                <div class="p-2.5 bg-gray-50 rounded-lg dark:bg-white/5 text-gray-500 dark:text-gray-400">
                    <x-filament::icon icon="heroicon-m-cursor-arrow-rays" class="h-5 w-5" />
                </div>
            </div>

            <div class="p-6 bg-white rounded-xl border border-gray-200 shadow-sm dark:bg-gray-900 dark:border-white/10 flex items-center justify-between">
                <div class="space-y-1">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('report.open_click_rate') }}</span>
                    <div class="text-2xl font-semibold tracking-tight text-primary-600 dark:text-primary-400">
                        {{ $this->stats['open_rate'] }}% <span class="text-gray-300 dark:text-gray-700 text-xl font-normal mx-0.5">/</span> {{ $this->stats['click_rate'] }}%
                    </div>
                </div>
                <div class="p-2.5 bg-primary-50 rounded-lg dark:bg-primary-500/10 text-primary-600 dark:text-primary-400">
                    <x-filament::icon icon="heroicon-m-chart-bar" class="h-5 w-5" />
                </div>
            </div>
        </div>

        @if ($this->selectedCampaign)
            <x-filament::section class="overflow-hidden">
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-m-list-bullet" class="h-5 w-5 text-gray-400" />
                        <span>{{ __('report.recipient_list') }}</span>
                    </div>
                </x-slot>

                <div class="-mx-6 -my-4 overflow-x-auto">
                    <table class="w-full text-left divide-y divide-gray-200 dark:divide-white/5 text-sm">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('report.contact') }}</th>
                                <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('report.email') }}</th>
                                <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('report.status') }}</th>
                                <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('report.sent_at') }}</th>
                                <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('report.opened_at') }}</th>
                                <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('report.clicked_at') }}</th>
                                <th scope="col" class="px-6 py-3.5 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ __('report.failure_reason') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5 bg-white dark:bg-gray-900">
                            @foreach ($this->selectedCampaign->recipients as $recipient)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-white/5 transition">
                                    <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900 dark:text-white">
                                        {{ $recipient->contact?->full_name ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600 dark:text-gray-300 font-mono text-xs">
                                        {{ $recipient->email }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $status = strtolower($recipient->status);
                                            
                                            $badgeColor = match(true) {
                                                str_contains($status, 'sent') || str_contains($status, 'success') || str_contains($status, 'deliver') => 'success',
                                                str_contains($status, 'fail') || str_contains($status, 'bounce') || str_contains($status, 'error') => 'danger',
                                                str_contains($status, 'open') => 'info',
                                                str_contains($status, 'click') => 'primary',
                                                str_contains($status, 'unsub') => 'warning',
                                                default => 'gray'
                                            };
                                        @endphp
                                        
                                        <x-filament::badge :color="$badgeColor">
                                            {{ $recipient->status }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400 text-xs">
                                        {{ $recipient->sent_at ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400 text-xs">
                                        {{ $recipient->opened_at ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-500 dark:text-gray-400 text-xs">
                                        {{ $recipient->clicked_at ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-xs text-rose-600 dark:text-rose-400 max-w-xs truncate" title="{{ $recipient->failure_reason }}">
                                        {{ $recipient->failure_reason ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @else
            <div class="flex flex-col items-center justify-center p-12 bg-white rounded-xl border border-dashed border-gray-300 dark:bg-gray-900 dark:border-white/20 text-center">
                <div class="p-3 bg-gray-50 rounded-full dark:bg-white/5 text-gray-400 mb-4">
                    <x-filament::icon icon="heroicon-o-inbox" class="h-8 w-8" />
                </div>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('report.empty_title') }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400 max-w-xs">{{ __('report.empty_description') }}</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>
