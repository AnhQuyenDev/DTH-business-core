@php
    $record = $getRecord();
    $sent = (int) ($record?->sent_recipients_count ?? 0);
    $opened = (int) ($record?->opened_recipients_count ?? 0);
    $clicked = (int) ($record?->clicked_recipients_count ?? 0);
    $openRate = $sent > 0 ? round(($opened / $sent) * 100, 1) : null;
    $clickRate = $sent > 0 ? round(($clicked / $sent) * 100, 1) : null;
@endphp

<div class="dth-campaign-performance">
    <div>
        <strong>{{ $openRate === null ? '—' : number_format($openRate, 1, ',', '.').'%' }}</strong>
        <span>{{ \Dth\Email\Support\UiText::get('campaign.list.performance_open', 'Mở') }}</span>
    </div>
    <div>
        <strong>{{ $clickRate === null ? '—' : number_format($clickRate, 1, ',', '.').'%' }}</strong>
        <span>{{ \Dth\Email\Support\UiText::get('campaign.list.performance_click', 'Nhấp') }}</span>
    </div>
</div>

<style>
    .dth-campaign-performance {
        display: grid;
        grid-template-columns: repeat(2, minmax(2.8rem, auto));
        gap: .6rem;
        align-items: center;
    }

    .dth-campaign-performance > div {
        display: flex;
        flex-direction: column;
        gap: .05rem;
    }

    .dth-campaign-performance strong {
        color: #172033;
        font-size: .74rem;
        line-height: 1.1;
        font-weight: 800;
    }

    .dth-campaign-performance span {
        color: #98a2b3;
        font-size: .61rem;
        line-height: 1.1;
    }

    .dark .dth-campaign-performance strong {
        color: #f8fafc;
    }
</style>
