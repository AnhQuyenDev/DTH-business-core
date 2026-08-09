<x-filament-panels::page>
<style>
    /* Customer Care workspace - uses Filament's default neutral surfaces */
    .cc-shell {
        --cc-border: rgba(17, 24, 39, .10);
        --cc-muted: rgb(107 114 128);
        --cc-text: rgb(17 24 39);
        --cc-soft: rgba(17, 24, 39, .025);
        --cc-soft-hover: rgba(17, 24, 39, .045);
    }

    .dark .cc-shell {
        --cc-border: rgba(255, 255, 255, .10);
        --cc-muted: rgb(156 163 175);
        --cc-text: rgb(243 244 246);
        --cc-soft: rgba(255, 255, 255, .035);
        --cc-soft-hover: rgba(255, 255, 255, .06);
    }

    /* Header is rendered through a modal slot, so it cannot be scoped below .cc-shell. */
    .cc-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        width: 100%;

        padding-top: .125rem;
        padding-bottom: .875rem;
        padding-inline-end: 4rem;

        border-bottom: 1px solid rgba(17, 24, 39, .10);
    }

    .dark .cc-header {
        border-bottom-color: rgba(255, 255, 255, .10);
    }

    .cc-avatar {
        display: grid;
        width: 2.5rem;
        height: 2.5rem;
        flex: 0 0 auto;
        place-items: center;
        border: 1px solid rgba(17, 24, 39, .14);
        border-radius: 9999px;
        background: transparent;
        color: rgb(17 24 39);
        font-size: .875rem;
        font-weight: 700;
    }

    .dark .cc-avatar {
        border-color: rgba(255, 255, 255, .18);
        color: rgb(243 244 246);
    }

    /* The tab frame is centered and keeps Filament's own default appearance. */
    .cc-shell .cc-tabs-wrap {
        position: sticky;
        top: 0;
        z-index: 20;
        display: flex;
        justify-content: center;
        width: 100%;
        margin: .75rem 0 1rem;
        padding: .25rem 0;
        background: transparent;
    }

    .cc-shell .cc-tabs-wrap > * {
        width: max-content;
        max-width: 100%;
    }

    .cc-shell .cc-panel {
        overflow: hidden;
        border: 1px solid var(--cc-border);
        border-radius: .875rem;
        background: transparent;
        box-shadow: none;
    }

    .cc-shell .cc-panel-head {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: .625rem;
        padding: .875rem 1rem;
        border-bottom: 1px solid var(--cc-border);
        background: transparent;
    }

    .cc-shell .cc-panel-head > .fi-btn,
    .cc-shell .cc-panel-head > a.fi-btn,
    .cc-shell .cc-panel-head > button.fi-btn {
        margin-left: auto;
    }

    .cc-shell .cc-panel-body {
        padding: 1rem;
    }

    .cc-shell .cc-section-title {
        display: flex;
        align-items: center;
        gap: .625rem;
        color: var(--cc-text);
        font-weight: 700;
    }

    .cc-shell .cc-info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
    }

    @media (min-width: 768px) {
        .cc-shell .cc-info-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (min-width: 1100px) {
        .cc-shell .cc-info-grid {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }
    }

    .cc-shell .cc-info-item {
        min-width: 0;
        padding: .875rem;
        border: 1px solid var(--cc-border);
        border-radius: .75rem;
        background: var(--cc-soft);
    }

    .cc-shell .cc-info-label {
        display: flex;
        align-items: center;
        gap: .4rem;
        color: var(--cc-muted);
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .04em;
    }

    .cc-shell .cc-info-value {
        overflow: hidden;
        margin-top: .35rem;
        color: var(--cc-text);
        font-size: .875rem;
        font-weight: 650;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .cc-shell .cc-metric-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
    }

    @media (min-width: 768px) {
        .cc-shell .cc-metric-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }

    .cc-shell .cc-metric,
    .cc-shell .cc-list-item {
        border: 1px solid var(--cc-border);
        background: var(--cc-soft);
    }

    .cc-shell .cc-metric {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: 1rem;
        border-radius: .75rem;
        transition: background .15s ease, border-color .15s ease;
    }

    .cc-shell .cc-metric:hover,
    .cc-shell .cc-list-item:hover {
        background: var(--cc-soft-hover);
    }

    .cc-shell .cc-list-item {
        display: flex;
        align-items: flex-start;
        gap: .75rem;
        padding: .875rem;
        border-radius: .75rem;
    }

    .cc-shell .cc-empty {
        padding: 3rem 1rem;
        border: 1px dashed var(--cc-border);
        border-radius: .75rem;
        background: transparent;
        color: var(--cc-muted);
        text-align: center;
    }

    .cc-shell .cc-scroll {
        max-height: 72vh;
        overflow-y: auto;
        padding-right: .25rem;
        scrollbar-color: rgba(107, 114, 128, .55) transparent;
        scrollbar-width: thin;
    }

    .cc-shell .cc-composer-row {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .75rem 1rem;
        border-bottom: 1px solid var(--cc-border);
    }

    .cc-shell .cc-composer-label {
        width: 4.5rem;
        flex: 0 0 auto;
        color: var(--cc-muted);
        font-size: .7rem;
        font-weight: 700;
        letter-spacing: .04em;
    }

    .cc-shell .cc-composer-input {
        width: 100%;
        padding: 0 !important;
        border: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
        color: var(--cc-text) !important;
    }

    .cc-shell .cc-table thead {
        background: var(--cc-soft);
    }

    .cc-shell .cc-table th {
        color: var(--cc-muted);
        font-size: .7rem;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .cc-shell .cc-table tbody tr {
        border-top: 1px solid var(--cc-border);
    }

    .cc-shell .cc-table tbody tr:hover {
        background: var(--cc-soft-hover);
    }

    @media (max-width: 640px) {
        .cc-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .cc-shell .cc-tabs-wrap {
            justify-content: flex-start;
            overflow-x: auto;
        }
    }
</style>

    @php
        $counts = $this->getCustomerCounts();
        $selected = $this->getSelectedCustomer();
        $stats = $this->getStats();
        $timeline = $this->getTimeline();
        $thread = $this->getEmailThread();
        $quotations = $this->getQuotations();

        $emailBadgeColor = static fn (string $type): string => match ($type) {
            'queued', 'sent' => 'gray',
            'delivered' => 'success',
            'opened' => 'info',
            'clicked' => 'primary',
            'failed', 'bounced', 'complained', 'unsubscribed' => 'danger',
            default => 'gray',
        };
        $statusBadgeColor = static fn (?string $s): string => match ($s) {
            'completed' => 'success',
            'scheduled' => 'warning',
            'cancelled', 'no_show' => 'danger',
            'rescheduled' => 'info',
            default => 'gray',
        };
        $iconChipColor = static fn (string $tone): string => match ($tone) {
            'primary' => 'bg-primary-500/10 text-primary-400 border border-primary-500/20',
            'success' => 'bg-success-500/10 text-success-400 border border-success-500/20',
            'danger' => 'bg-danger-500/10 text-danger-400 border border-danger-500/20',
            'warning' => 'bg-warning-500/10 text-warning-400 border border-warning-500/20',
            'info' => 'bg-info-500/10 text-info-400 border border-info-500/20',
            default => 'bg-gray-50 text-gray-500 border border-gray-200 dark:bg-white/5 dark:text-gray-400 dark:border-white/10',
        };
        $interactionTone = static fn (string $type): string => match ($type) {
            'complaint' => 'danger',
            'call', 'message' => 'info',
            'email' => 'primary',
            'follow_up' => 'warning',
            'support' => 'success',
            default => 'gray',
        };
        $interactionIcon = static fn (string $type): string => match ($type) {
            'call' => 'heroicon-m-phone',
            'message' => 'heroicon-m-chat-bubble-left-right',
            'email' => 'heroicon-m-envelope',
            'meeting' => 'heroicon-m-users',
            'support' => 'heroicon-m-check-circle',
            'complaint' => 'heroicon-m-exclamation-triangle',
            'follow_up' => 'heroicon-m-clock',
            default => 'heroicon-m-document-text',
        };
    @endphp

    {{-- ─── Stat cards ─── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @foreach ([
            ['total', 'total', 'heroicon-m-users', 'primary'],
            ['needs_follow_up', 'follow_up', 'heroicon-m-bell', 'warning'],
        ] as [$key, $labelKey, $icon, $color])
            <x-filament::section :icon="$icon" :icon-color="$color" compact>
                <x-slot name="heading">{{ __("page.customer_care.stat_{$labelKey}") }}</x-slot>
                <x-slot name="description">{{ __("page.customer_care.stat_{$labelKey}_desc") }}</x-slot>
                <div class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $counts[$key] }}</div>
            </x-filament::section>
        @endforeach
    </div>

    {{-- ─── Customer table ─── --}}
    {{ $this->table }}

    {{-- ─── Care workspace modal ─── --}}
    @if ($selected)
        <x-filament::modal id="customer-care-workspace" width="7xl">
            <div class="cc-shell">
            <x-slot name="header">
                {{-- pr-10 giúp tạo khoảng trống an toàn với nút [X] đóng modal --}}
                <div class="cc-header w-full">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="cc-avatar shrink-0">
                            {{ mb_substr($selected->display_name ?? 'K', 0, 1) }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="truncate text-lg font-bold tracking-tight text-gray-950 dark:text-white">{{ $selected->display_name }}</h3>
                            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <span>{{ $selected->customer_code }}</span>
                                @if($selected->email)
                                    <span>•</span>
                                    <span class="truncate text-gray-700 dark:text-white">{{ $selected->email }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <x-filament::button color="gray" size="sm" icon="heroicon-m-eye" tag="a"
                            :href="\App\Filament\Resources\CustomerResource::getUrl('view', ['record' => $selected])">
                            {{ __('action.view') }}
                        </x-filament::button>
                        @if ($this->canRelease())
                            <x-filament::button color="warning" size="sm" outlined icon="heroicon-m-arrow-uturn-left"
                                @click="$dispatch('open-modal', { id: 'release-customer-modal' })">
                                {{ __('action.release_customer') }}
                            </x-filament::button>
                        @endif
                    </div>
                </div>
            </x-slot>

            {{-- Căn giữa thanh Tab --}}
            <div class="cc-tabs-wrap">
                <x-filament::tabs class="mx-auto w-fit max-w-full justify-center overflow-x-auto">
                    <x-filament::tabs.item icon="heroicon-m-document-text" wire:click="setTab('overview')" :active="$activeTab === 'overview'">
                        {{ __('page.customer_care.tab_overview') }}
                    </x-filament::tabs.item>
                    <x-filament::tabs.item icon="heroicon-m-envelope" wire:click="setTab('email')" :active="$activeTab === 'email'">
                        {{ __('page.customer_care.tab_email') }}
                    </x-filament::tabs.item>
                    <x-filament::tabs.item icon="heroicon-m-phone" wire:click="setTab('call')" :active="$activeTab === 'call'">
                        {{ __('page.customer_care.tab_call') }}
                    </x-filament::tabs.item>
                    <x-filament::tabs.item icon="heroicon-m-banknotes" wire:click="setTab('quotation')" :active="$activeTab === 'quotation'">
                        {{ __('page.customer_care.tab_quotation') }}
                    </x-filament::tabs.item>
                    <x-filament::tabs.item icon="heroicon-m-clock" wire:click="setTab('timeline')" :active="$activeTab === 'timeline'">
                        {{ __('page.customer_care.tab_timeline') }}
                    </x-filament::tabs.item>
                </x-filament::tabs>
            </div>

            <div class="cc-scroll">
                {{-- ── Tab: Overview ── --}}
                @if ($activeTab === 'overview')
                    @php
                        $infoRows = [
                            [__('field.customer_code'), $selected->customer_code, 'heroicon-m-hashtag'],
                            [__('field.type'), $selected->customer_type === 'business' ? __('field.type.business') : __('field.type.personal'), 'heroicon-m-user-group'],
                            [__('field.email'), $selected->email, 'heroicon-m-envelope'],
                            [__('field.phone'), $selected->phone, 'heroicon-m-phone'],
                            [__('field.lifecycle_stage'), $selected->lifecycle_stage ? \App\Enums\Crm\CustomerLifecycleStage::tryFrom($selected->lifecycle_stage)?->label() : null, 'heroicon-m-arrow-path'],
                            [__('field.priority'), $selected->priority ? __("field.priority.{$selected->priority}") : null, 'heroicon-m-flag'],
                            [__('field.acquisition_source'), $selected->acquisition_source, 'heroicon-m-globe-alt'],
                            [__('field.gender'), in_array($selected->gender, ['male', 'female', 'other'], true) ? __("field.gender.{$selected->gender}") : $selected->gender, 'heroicon-m-user'],
                            [__('field.industry'), $selected->industry, 'heroicon-m-briefcase'],
                            [__('field.conversion_reason'), $selected->conversion_reason ? __('enum.conversion_reason.' . $selected->conversion_reason) : null, 'heroicon-m-sparkles'],
                        ];
                    @endphp
                    <div class="space-y-5">
                        {{-- Customer Info Section --}}
                        <div class="cc-panel">
                            <div class="cc-panel-head">
                                @svg('heroicon-m-identification', 'h-5 w-5 text-warning-400')
                                <h4 class="text-sm font-bold text-gray-950 dark:text-white">{{ __('page.customer_care.customer_info') }}</h4>
                            </div>
                            <dl class="cc-panel-body cc-info-grid">
                                @foreach ($infoRows as [$label, $value, $icon])
                                    <div class="cc-info-item">
                                        <dt class="cc-info-label">
                                            @svg($icon, 'h-3.5 w-3.5 text-gray-500 shrink-0')
                                            <span>{{ $label }}</span>
                                        </dt>
                                        <dd class="cc-info-value">
                                            {{ $value ?? '—' }}
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>

                        {{-- Care Stats Section --}}
                        <div class="cc-panel">
                            <div class="cc-panel-head">
                                @svg('heroicon-m-chart-bar', 'h-5 w-5 text-warning-400')
                                <h4 class="text-sm font-bold text-gray-950 dark:text-white">{{ __('page.customer_care.care_stats') }}</h4>
                            </div>
                            <div class="cc-panel-body cc-metric-grid">
                                @foreach ([
                                    ['calls', $stats['calls'], 'heroicon-m-phone', 'info'],
                                    ['messages', $stats['messages'], 'heroicon-m-chat-bubble-left-right', 'success'],
                                    ['emails', $stats['emails'], 'heroicon-m-envelope', 'primary'],
                                    ['quotations', $stats['quotations'], 'heroicon-m-document-text', 'warning'],
                                ] as [$key, $value, $icon, $tone])
                                    <div class="cc-metric">
                                        <div class="{{ $iconChipColor($tone) }} flex h-10 w-10 shrink-0 items-center justify-center rounded-xl shadow-xs">
                                            @svg($icon, 'h-5 w-5')
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white leading-tight">{{ $value }}</p>
                                            <p class="mt-0.5 truncate text-xs font-medium text-gray-500 dark:text-gray-400">{{ __("page.customer_care.stat_workspace_{$key}") }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ── Tab: Timeline ── --}}
                @if ($activeTab === 'timeline')
                    <div class="cc-panel">
                        <div class="cc-panel-head">
                            @svg('heroicon-m-clock', 'h-5 w-5 text-warning-400')
                            <h4 class="text-sm font-bold text-gray-950 dark:text-white">{{ __('page.customer_care.tab_timeline') }}</h4>
                        </div>
                        <div class="cc-panel-body space-y-3">
                            @forelse ($timeline as $item)
                                @include('filament.pages.customer-care-timeline-item', [
                                    'item' => $item,
                                    'emailBadgeColor' => $emailBadgeColor,
                                    'statusBadgeColor' => $statusBadgeColor,
                                    'iconChipColor' => $iconChipColor,
                                    'interactionTone' => $interactionTone,
                                    'interactionIcon' => $interactionIcon,
                                ])
                            @empty
                                <div class="py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('page.customer_care.no_activity') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endif

                {{-- ── Tab: Email (Dark Gmail Style) ── --}}
                @if ($activeTab === 'email')
                    <div class="space-y-6">
                        @if($this->canInteractSelectedCustomer())
                        <div class="cc-panel">
                            <div class="cc-panel-head">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-m-paper-airplane', 'h-4 w-4 text-warning-400')
                                    <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ __('page.customer_care.compose_email') }}</span>
                                </div>
                            </div>

                            <div class="">
                                @php
                                    $composerSender = $this->getComposerSender();
                                @endphp
                                <div class="cc-composer-row">
                                    <span class="cc-composer-label">{{ __('page.customer_care.email_from') }}</span>
                                    @if ($composerSender['account'])
                                        <span class="cc-composer-input text-sm text-gray-700 dark:text-white">
                                            {{ $composerSender['account']->from_name }} &lt;{{ $composerSender['account']->from_email }}&gt;
                                            @if (! $composerSender['ready'])
                                                <span class="text-danger-600 dark:text-danger-400">({{ __('page.customer_care.email_account_invalid') }})</span>
                                            @endif
                                        </span>
                                    @else
                                        <span class="cc-composer-input text-sm text-danger-600 dark:text-danger-400">
                                            {{ $composerSender['error'] ?? __('page.customer_care.email_no_account') }}
                                        </span>
                                    @endif
                                </div>
                                <div class="cc-composer-row">
                                    <span class="cc-composer-label">{{ __('page.customer_care.email_to') }}</span>
                                    <input type="text" wire:model="emailTo"
                                        class="cc-composer-input text-sm placeholder-gray-500"
                                        placeholder="email@example.com" />
                                </div>
                                <div class="cc-composer-row">
                                    <span class="cc-composer-label">{{ __('page.customer_care.email_cc') }}</span>
                                    <input type="text" wire:model="emailCc"
                                        class="cc-composer-input text-sm placeholder-gray-500"
                                        placeholder="cc@example.com" />
                                </div>
                                <div class="cc-composer-row">
                                    <span class="cc-composer-label">{{ __('page.customer_care.email_bcc') }}</span>
                                    <input type="text" wire:model="emailBcc"
                                        class="cc-composer-input text-sm placeholder-gray-500"
                                        placeholder="bcc@example.com" />
                                </div>
                                <div class="cc-composer-row">
                                    <span class="cc-composer-label">{{ __('field.subject') }}</span>
                                    <input type="text" wire:model="emailSubject"
                                        class="cc-composer-input text-sm font-medium placeholder-gray-500"
                                        placeholder="{{ __('page.customer_care.email_subject_placeholder') }}" />
                                </div>
                            </div>

                            <div class="cc-panel-body">
                                <textarea wire:model="emailBody" rows="7"
                                    class="block w-full border-0 bg-transparent p-0 text-sm text-gray-950 dark:text-white placeholder-gray-500 focus:ring-0 resize-y"
                                    placeholder="{{ __('page.customer_care.email_body_placeholder') }}"></textarea>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 dark:border-white/10 px-4 py-3">
                                <div class="flex items-center gap-3" x-data="{ uploading: false }">
                                    <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-white/10">
                                        <input type="file" multiple class="hidden"
                                            x-on:change="
                                                const input = $event.target;
                                                const files = Array.from(input.files);
                                                if (files.length === 0) return;
                                                input.value = null;
                                                uploading = true;
                                                $wire.uploadMultiple('emailAttachments', files,
                                                    () => { uploading = false; },
                                                    () => { uploading = false; },
                                                    (e) => {},
                                                    () => { uploading = false; },
                                                    true
                                                );
                                            " />
                                        @svg('heroicon-m-paper-clip', 'h-4 w-4 text-gray-500 dark:text-gray-400')
                                        <span>{{ __('page.customer_care.attach_file') }}</span>
                                    </label>

                                    @if (filled($emailAttachments))
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            @foreach ($emailAttachments as $index => $file)
                                                <span class="inline-flex items-center gap-1 rounded-md bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 py-1 px-2 text-xs font-medium text-gray-700 dark:text-white">
                                                    @svg('heroicon-m-document', 'h-3.5 w-3.5 text-gray-500 dark:text-gray-400')
                                                    <span class="max-w-[120px] truncate">{{ $file->getClientOriginalName() }}</span>
                                                    <button type="button" wire:click="removeAttachment({{ $index }})" wire:loading.attr="disabled"
                                                        class="ml-1 rounded-full p-0.5 text-gray-500 dark:text-gray-400 hover:bg-gray-100 hover:text-gray-950 dark:hover:bg-white/10 dark:hover:text-white">
                                                        @svg('heroicon-m-x-mark', 'h-3.5 w-3.5')
                                                    </button>
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div x-show="uploading" class="text-xs text-gray-500 dark:text-gray-400">
                                        <span class="animate-pulse">{{ __('page.customer_care.attaching') }}</span>
                                    </div>
                                </div>

                                <x-filament::button color="warning" icon="heroicon-m-paper-airplane"
                                    wire:click="sendEmail" wire:loading.attr="disabled" wire:target="sendEmail, emailAttachments">
                                    {{ __('action.send') }}
                                </x-filament::button>
                            </div>
                        </div>
                        @else
                            <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-200">
                                Admin/Executive/Viewer chỉ có quyền xem/audit Customer Care; thao tác gửi email thuộc nhân viên CSKH được phân công.
                            </div>
                        @endif

                        {{-- Email History --}}
                        <div class="cc-panel">
                            <div class="cc-panel-head">
                                @svg('heroicon-m-envelope-open', 'h-5 w-5 text-warning-400')
                                <h4 class="text-sm font-bold text-gray-950 dark:text-white">{{ __('page.customer_care.email_history') }}</h4>
                            </div>
                            <div class="cc-panel-body space-y-3">
                                @forelse ($thread as $row)
                                    @php
                                        $event = $row['event'];
                                        $type = $row['status'];
                                    @endphp
                                    <div class="cc-list-item">
                                        <div class="{{ $iconChipColor($emailBadgeColor($type)) }} flex h-9 w-9 shrink-0 items-center justify-center rounded-xl shadow-xs">
                                            @svg('heroicon-m-envelope', 'h-4 w-4')
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2 flex-wrap justify-between">
                                                <p class="text-sm font-semibold text-gray-950 dark:text-white truncate">{{ $row['subject'] }}</p>
                                                <div class="flex items-center gap-1.5 shrink-0">
                                                    <x-filament::badge :color="$emailBadgeColor($type)" size="xs">
                                                        {{ $event->event_type->label() }}
                                                    </x-filament::badge>
                                                    @if ($row['is_care'])
                                                        <x-filament::badge color="gray" size="xs">
                                                            {{ __('page.customer_care.from_staff') }}
                                                        </x-filament::badge>
                                                    @endif
                                                </div>
                                            </div>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $event->occurred_at?->format('d/m/Y H:i') }}</p>
                                            @if (! empty($event->event_payload['original_url']))
                                                <p class="mt-1 flex items-center gap-1 truncate text-xs text-warning-400 hover:underline">
                                                    @svg('heroicon-m-arrow-up-right', 'h-3.5 w-3.5 shrink-0')
                                                    <a href="{{ $event->event_payload['original_url'] }}" target="_blank" class="truncate">{{ $event->event_payload['original_url'] }}</a>
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="cc-empty text-sm">
                                        {{ __('page.customer_care.no_emails') }}
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ── Tab: Call & Message ── --}}
                @if ($activeTab === 'call')
                    <div class="space-y-6">
                        @if($this->canInteractSelectedCustomer())
                        <x-filament::section icon="heroicon-m-phone" icon-color="primary" compact>
                            <x-slot name="heading">{{ __('page.customer_care.log_call') }}</x-slot>
                            <x-slot name="description">{{ __('page.customer_care.log_call_desc') }}</x-slot>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-white">{{ __('field.method') }}</label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input.select wire:model="callType">
                                            <option value="call">{{ __('enum.interaction_type.call') }}</option>
                                            <option value="message">{{ __('enum.interaction_type.message') }}</option>
                                        </x-filament::input.select>
                                    </x-filament::input.wrapper>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-white">{{ __('field.status') }}</label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input.select wire:model="callStatus">
                                            @foreach (\App\Enums\Crm\InteractionStatus::options() as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </x-filament::input.select>
                                    </x-filament::input.wrapper>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-white">{{ __('field.next_follow_up') }}</label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input type="datetime-local" wire:model="callNextFollowUp" />
                                    </x-filament::input.wrapper>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-white">{{ __('field.content') }}</label>
                                    <x-filament::input.wrapper>
                                        <textarea wire:model="callContent" rows="3"
                                            class="fi-input block w-full border-none bg-transparent py-1.5 ps-3 pe-3 text-sm text-gray-950 dark:text-white placeholder-gray-500 focus:ring-0"></textarea>
                                    </x-filament::input.wrapper>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-white">{{ __('field.outcome') }}</label>
                                    <x-filament::input.wrapper>
                                        <x-filament::input type="text" wire:model="callOutcome"
                                            :placeholder="__('page.customer_care.outcome_placeholder')" />
                                    </x-filament::input.wrapper>
                                </div>
                            </div>
                            <div class="mt-4 flex justify-end">
                                <x-filament::button color="warning" icon="heroicon-m-check" wire:click="logCall">
                                    {{ __('page.customer_care.save_record') }}
                                </x-filament::button>
                            </div>
                        </x-filament::section>

                        @else
                            <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800 dark:border-blue-500/20 dark:bg-blue-500/10 dark:text-blue-200">
                                Bạn đang ở chế độ chỉ xem; thao tác ghi nhận cuộc gọi/tin nhắn thuộc nhân viên CSKH được phân công.
                            </div>
                        @endif

                        <div class="cc-panel">
                            <div class="cc-panel-head">
                                @svg('heroicon-m-clock', 'h-5 w-5 text-warning-400')
                                <h4 class="text-sm font-bold text-gray-950 dark:text-white">{{ __('page.customer_care.call_history') }}</h4>
                            </div>
                            <div class="cc-panel-body space-y-3">
                                @forelse ($selected->interactions->whereIn('interaction_type', ['call', 'message'])->sortByDesc('interaction_at') as $interaction)
                                    <div class="cc-list-item">
                                        <div class="{{ $iconChipColor($interactionTone($interaction->interaction_type)) }} flex h-9 w-9 shrink-0 items-center justify-center rounded-xl shadow-xs">
                                            @svg($interactionIcon($interaction->interaction_type), 'h-4 w-4')
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <p class="text-sm font-semibold text-gray-950 dark:text-white">{{ $interaction->subject }}</p>
                                                <x-filament::badge color="gray" size="xs">
                                                    {{ __("enum.interaction_type.{$interaction->interaction_type}") }}
                                                </x-filament::badge>
                                                <x-filament::badge :color="$statusBadgeColor($interaction->status?->value)" size="xs">
                                                    {{ $interaction->status->label() }}
                                                </x-filament::badge>
                                            </div>
                                            <p class="mt-1.5 text-sm whitespace-pre-wrap text-gray-700 dark:text-white">{{ $interaction->content }}</p>
                                            @if ($interaction->outcome)
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $interaction->outcome }}</p>
                                            @endif
                                            <p class="mt-1.5 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                                @svg('heroicon-m-clock', 'h-3.5 w-3.5')
                                                {{ $interaction->interaction_at?->format('d/m/Y H:i') }}
                                                · {{ $interaction->staff?->full_name }}
                                                @if ($interaction->next_follow_up_at)
                                                    ·
                                                    @svg('heroicon-m-calendar-days', 'h-3.5 w-3.5')
                                                    {{ __('field.next_follow_up') }}: {{ $interaction->next_follow_up_at->format('d/m/Y H:i') }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                @empty
                                    <div class="cc-empty text-sm">
                                        {{ __('page.customer_care.no_calls') }}
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ── Tab: Quotation ── --}}
                @if ($activeTab === 'quotation')
                    <div class="cc-panel">
                        <div class="cc-panel-head">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-m-document-text', 'h-5 w-5 text-warning-400')
                                <h4 class="text-sm font-bold text-gray-950 dark:text-white">{{ __('page.customer_care.tab_quotation') }}</h4>
                            </div>
                        </div>

                        <div class="cc-panel-body overflow-x-auto">
                            <table class="cc-table w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-white/10 text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        <th class="py-3 px-3">{{ __('field.quotation_code') }}</th>
                                        <th class="py-3 px-3">{{ __('field.title') }}</th>
                                        <th class="py-3 px-3">{{ __('field.quotation_date') }}</th>
                                        <th class="py-3 px-3 text-right">{{ __('field.grand_total') }}</th>
                                        <th class="py-3 px-3">{{ __('field.status') }}</th>
                                        <th class="py-3 px-3">{{ __('page.customer_care.sent_at') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-800/60">
                                    @forelse ($quotations as $quotation)
                                        <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                                            <td class="py-3 px-3 font-mono text-xs font-semibold text-gray-950 dark:text-white">{{ $quotation->quotation_code }}</td>
                                            <td class="py-3 px-3 font-medium text-gray-800 dark:text-gray-200">{{ $quotation->title }}</td>
                                            <td class="py-3 px-3 text-gray-500 dark:text-gray-400">{{ $quotation->quotation_date?->format('d/m/Y') }}</td>
                                            <td class="py-3 px-3 text-right font-mono font-semibold text-gray-950 dark:text-white">
                                                {{ number_format($quotation->grand_total, 0, ',', '.') }} <span class="text-xs font-sans text-gray-500 dark:text-gray-400">{{ $quotation->currency }}</span>
                                            </td>
                                            <td class="py-3 px-3">
                                                <x-filament::badge :color="$quotation->status->color()" size="xs">
                                                    {{ $quotation->status->label() }}
                                                </x-filament::badge>
                                            </td>
                                            <td class="py-3 px-3 text-gray-500 dark:text-gray-400">{{ $quotation->sent_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                                {{ __('page.customer_care.no_quotations') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
            </div>
        </x-filament::modal>

        {{-- ─── Release customer modal ─── --}}
        @if ($this->canRelease())
            <x-filament::modal id="release-customer-modal" width="md">
                <x-slot name="heading">{{ __('action.release_customer_confirm') }}</x-slot>
                <x-slot name="description">{{ __('action.release_customer_desc') }}</x-slot>

                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-xs font-medium text-gray-700 dark:text-white">{{ __('field.release_reason') }}</label>
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model="releaseReason">
                                <option value="">—</option>
                                <option value="customer_refused">{{ __('enum.assignment_reason.customer_refused') }}</option>
                                <option value="wrong_owner">{{ __('enum.assignment_reason.wrong_owner') }}</option>
                                <option value="no_response">{{ __('enum.assignment_reason.no_response') }}</option>
                                <option value="overload">{{ __('enum.assignment_reason.overload') }}</option>
                                <option value="other">{{ __('enum.assignment_reason.other') }}</option>
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>
                    <div>
                        <label class="mb-2 block text-xs font-medium text-gray-700 dark:text-white">{{ __('field.note') }}</label>
                        <x-filament::input.wrapper>
                            <textarea wire:model="releaseNote" rows="3"
                                class="fi-input block w-full border-none bg-transparent py-1.5 ps-3 pe-3 text-sm text-gray-950 dark:text-white placeholder-gray-500 focus:ring-0"></textarea>
                        </x-filament::input.wrapper>
                    </div>
                </div>

                <x-slot name="footer">
                    <x-filament::button color="gray" @click="$dispatch('close-modal', { id: 'release-customer-modal' })">
                        {{ __('action.cancel') }}
                    </x-filament::button>
                    <x-filament::button color="warning" icon="heroicon-m-arrow-uturn-left"
                        wire:click="releaseCustomer" @click="$dispatch('close-modal', { id: 'release-customer-modal' })">
                        {{ __('action.release_customer') }}
                    </x-filament::button>
                </x-slot>
            </x-filament::modal>
        @endif
    @endif
</x-filament-panels::page>