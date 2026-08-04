@props([
    'item',
    'emailBadgeColor' => null,
    'statusBadgeColor' => null,
    'iconChipColor' => null,
    'interactionTone' => null,
    'interactionIcon' => null,
])

@php
    $emailBadgeColor ??= static fn (string $type): string => match ($type) {
        'delivered' => 'success',
        'opened' => 'info',
        'clicked' => 'primary',
        'failed', 'bounced', 'complained', 'unsubscribed' => 'danger',
        default => 'gray',
    };
    $statusBadgeColor ??= static fn (?string $s): string => match ($s) {
        'completed' => 'success',
        'scheduled' => 'warning',
        'cancelled', 'no_show' => 'danger',
        'rescheduled' => 'info',
        default => 'gray',
    };
    $iconChipColor ??= static fn (string $tone): string => match ($tone) {
        'primary' => 'bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400',
        'success' => 'bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400',
        'danger' => 'bg-danger-50 text-danger-600 dark:bg-danger-500/10 dark:text-danger-400',
        'warning' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/10 dark:text-warning-400',
        'info' => 'bg-info-50 text-info-600 dark:bg-info-500/10 dark:text-info-400',
        default => 'bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-400',
    };
    $interactionTone ??= static fn (string $type): string => match ($type) {
        'complaint' => 'danger',
        'call', 'message' => 'info',
        'email' => 'primary',
        'follow_up' => 'warning',
        'support' => 'success',
        default => 'gray',
    };
    $interactionIcon ??= static fn (string $type): string => match ($type) {
        'call' => 'heroicon-m-phone',
        'message' => 'heroicon-m-chat-bubble-left-right',
        'email' => 'heroicon-m-envelope',
        'meeting' => 'heroicon-m-users',
        'support' => 'heroicon-m-check-circle',
        'complaint' => 'heroicon-m-exclamation-triangle',
        'follow_up' => 'heroicon-m-clock',
        default => 'heroicon-m-document-text',
    };

    $kind = $item['kind'];
    $data = $item['data'];
    $at = $item['at'];
@endphp

<div class="flex items-start gap-3 rounded-xl bg-gray-50 p-3 dark:bg-white/5">
    @if ($kind === 'interaction')
        <div class="{{ $iconChipColor($interactionTone($data->interaction_type)) }} flex h-8 w-8 items-center justify-center rounded-lg shrink-0">
            @svg($interactionIcon($data->interaction_type), 'h-4 w-4')
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-1.5 flex-wrap">
                <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $data->subject ?: __("enum.interaction_type.{$data->interaction_type}") }}</p>
                <x-filament::badge color="gray" size="xs" class="whitespace-nowrap">{{ __("enum.interaction_type.{$data->interaction_type}") }}</x-filament::badge>
                @if ($data->status)
                    <x-filament::badge :color="$statusBadgeColor($data->status?->value)" size="xs" class="whitespace-nowrap">{{ $data->status->label() }}</x-filament::badge>
                @endif
            </div>
            <p class="mt-1.5 text-sm whitespace-pre-wrap text-gray-700 dark:text-gray-300 line-clamp-3 break-words">{{ $data->content }}</p>
            @if ($data->outcome)
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $data->outcome }}</p>
            @endif
            <p class="mt-1.5 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                @svg('heroicon-m-clock', 'h-3.5 w-3.5')
                {{ $at->format('d/m/Y H:i') }}
                @if ($data->staff)
                    · {{ $data->staff->full_name }}
                @endif
                @if ($data->next_follow_up_at)
                    ·
                    @svg('heroicon-m-calendar-days', 'h-3.5 w-3.5')
                    {{ __('field.next_follow_up') }}: {{ $data->next_follow_up_at->format('d/m/Y H:i') }}
                @endif
            </p>
        </div>
    @elseif ($kind === 'email_event')
        @php
            $payload = $data->event_payload ?? [];
        @endphp
        <div class="{{ $iconChipColor($emailBadgeColor($data->event_type->value)) }} flex h-8 w-8 items-center justify-center rounded-lg shrink-0">
            @svg('heroicon-m-envelope', 'h-4 w-4')
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-1.5 flex-wrap">
                <p class="text-sm font-medium text-gray-950 dark:text-white truncate">{{ $payload['subject'] ?? $data->event_type->label() }}</p>
                <x-filament::badge :color="$emailBadgeColor($data->event_type->value)" size="xs" class="whitespace-nowrap">{{ $data->event_type->label() }}</x-filament::badge>
                @if ($data->campaign_id === null)
                    <x-filament::badge color="gray" size="xs" class="whitespace-nowrap">{{ __('page.customer_care.from_staff') }}</x-filament::badge>
                @endif
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $at->format('d/m/Y H:i') }}</p>
            @if (! empty($payload['original_url']))
                <p class="mt-1 flex items-center gap-1 truncate text-xs text-gray-500 dark:text-gray-400">
                    @svg('heroicon-m-arrow-up-right', 'h-3.5 w-3.5 shrink-0')
                    <span class="truncate">{{ $payload['original_url'] }}</span>
                </p>
            @endif
        </div>
    @elseif ($kind === 'quotation')
        @php
            $firstEmailLog = $data->emailLogs?->first();
        @endphp
        <div class="{{ $iconChipColor($firstEmailLog ? 'primary' : 'warning') }} flex h-8 w-8 items-center justify-center rounded-lg shrink-0">
            @svg('heroicon-m-document-text', 'h-4 w-4')
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-1.5 flex-wrap">
                <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $data->quotation_code }}</p>
                <span class="text-sm text-gray-600 dark:text-gray-300">{{ $data->title }}</span>
                <x-filament::badge :color="$data->status->color()" size="xs" class="whitespace-nowrap">{{ $data->status->label() }}</x-filament::badge>
                @if ($firstEmailLog)
                    <x-filament::badge color="primary" size="xs" class="whitespace-nowrap">
                        @svg('heroicon-m-envelope', 'h-3 w-3 inline mr-0.5 -mt-px')
                        {{ __('page.customer_care.emailed_via_template') }}
                    </x-filament::badge>
                @endif
            </div>
            <p class="mt-1.5 text-sm font-semibold text-gray-950 dark:text-white">
                {{ number_format($data->grand_total, 0, ',', '.') }} {{ $data->currency }}
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $at->format('d/m/Y H:i') }}</p>
        </div>
    @else
        <div class="{{ $iconChipColor($kind === 'assignment_end' ? 'gray' : 'success') }} flex h-8 w-8 items-center justify-center rounded-lg shrink-0">
            @svg($kind === 'assignment_end' ? 'heroicon-m-user-minus' : 'heroicon-m-user-plus', 'h-4 w-4')
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-1.5 flex-wrap">
                <p class="text-sm font-medium text-gray-950 dark:text-white">
                    {{ $kind === 'assignment_end' ? __('page.customer_care.assignment_ended') : __('page.customer_care.assignment_started') }}
                </p>
                <x-filament::badge color="gray" size="xs" class="whitespace-nowrap">{{ $data->staff?->full_name }}</x-filament::badge>
            </div>
            @if ($data->note)
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $data->note }}</p>
            @endif
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $at->format('d/m/Y H:i') }}</p>
        </div>
    @endif
</div>
