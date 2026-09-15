@php
    $record = $getRecord();
    $status = $record?->status;
    $label = \Dth\Email\Filament\Support\CampaignUi::statusLabel($status);
    $tone = \Dth\Email\Filament\Support\CampaignUi::statusTone($status);
    $icon = \Dth\Email\Filament\Support\CampaignUi::statusIcon($status);
@endphp

<span class="dth-campaign-status" data-tone="{{ $tone }}">
    <x-filament::icon :icon="$icon" />
    <span>{{ $label }}</span>
</span>

<style>
    .dth-campaign-status {
        --status-rgb: 100, 116, 139;
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        width: max-content;
        padding: .22rem .48rem;
        color: rgb(var(--status-rgb));
        border-radius: .48rem;
        background: rgba(var(--status-rgb), .105);
        font-size: .68rem;
        line-height: 1.25;
        font-weight: 750;
        white-space: nowrap;
    }

    .dth-campaign-status[data-tone="blue"] { --status-rgb: 37, 99, 235; }
    .dth-campaign-status[data-tone="violet"] { --status-rgb: 109, 40, 217; }
    .dth-campaign-status[data-tone="green"] { --status-rgb: 22, 163, 74; }
    .dth-campaign-status[data-tone="red"] { --status-rgb: 220, 38, 38; }
    .dth-campaign-status[data-tone="gray"] { --status-rgb: 100, 116, 139; }

    .dth-campaign-status svg {
        width: .78rem;
        height: .78rem;
    }
</style>
