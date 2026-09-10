<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ \Dth\Email\Support\UiText::get('reports.campaign_title', 'Campaign Report') }} - {{ $campaign->name }}</title>
    <style>
        @page { margin: 28px 30px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #111827; font-size: 10px; line-height: 1.45; margin: 0; }
        .muted { color: #6b7280; }
        .brand { font-size: 10px; font-weight: 700; letter-spacing: .08em; color: #b45309; text-transform: uppercase; }
        .title { margin: 3px 0 0; font-size: 21px; line-height: 1.2; font-weight: 700; }
        .header-table, .grid-table, .data-table, .info-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .header-meta { text-align: right; color: #4b5563; line-height: 1.7; }
        .divider { border-top: 2px solid #f59e0b; margin: 14px 0 16px; }
        .section-title { font-size: 13px; font-weight: 700; margin: 0 0 9px; }
        .section { margin-top: 16px; }
        .info-panel { border: 1px solid #e5e7eb; border-radius: 7px; padding: 10px 12px; background: #f9fafb; }
        .info-table td { padding: 4px 8px 4px 0; vertical-align: top; width: 25%; }
        .info-label { color: #6b7280; font-size: 8px; text-transform: uppercase; letter-spacing: .04em; display: block; }
        .info-value { font-weight: 600; margin-top: 2px; display: block; }
        .grid-table { border-spacing: 7px; border-collapse: separate; margin: 0 -7px; }
        .metric { border: 1px solid #e5e7eb; border-radius: 7px; padding: 10px 11px; vertical-align: top; }
        .metric-label { color: #6b7280; font-size: 8.5px; text-transform: uppercase; letter-spacing: .05em; }
        .metric-value { font-size: 18px; font-weight: 700; margin-top: 3px; }
        .metric-note { color: #6b7280; font-size: 8px; margin-top: 2px; }
        .panel { border: 1px solid #e5e7eb; border-radius: 7px; padding: 12px; }
        .chart { width: 100%; max-height: 235px; }
        .split { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px; }
        .split > tbody > tr > td { width: 50%; vertical-align: top; }
        .funnel-row { margin: 8px 0; }
        .funnel-head { width: 100%; border-collapse: collapse; margin-bottom: 3px; }
        .funnel-head td:last-child { text-align: right; font-weight: 700; }
        .bar-track { height: 9px; background: #f3f4f6; border-radius: 9px; overflow: hidden; }
        .bar { height: 9px; background: #f59e0b; border-radius: 9px; }
        .bar.open { background: #3b82f6; }
        .bar.click { background: #10b981; }
        .bar.unsub { background: #9ca3af; }
        .data-table th { background: #f9fafb; color: #374151; font-weight: 700; text-align: left; border-bottom: 1px solid #d1d5db; padding: 7px 6px; }
        .data-table td { border-bottom: 1px solid #eef0f2; padding: 7px 6px; vertical-align: top; }
        .data-table .num { text-align: right; white-space: nowrap; }
        .url { word-break: break-all; font-size: 8.5px; }
        .insight { border-left: 3px solid #9ca3af; padding: 7px 9px; margin: 7px 0; background: #f9fafb; }
        .insight-title { font-weight: 700; margin-bottom: 2px; }
        .footer { position: fixed; bottom: -13px; left: 0; right: 0; color: #9ca3af; font-size: 7.5px; text-align: center; }
    </style>
</head>
<body>
@php
    $funnelBase = max($funnel->recipients, 1);
    $pct = static fn (int $value): float => min(100, round(($value / $funnelBase) * 100, 1));
@endphp

<table class="header-table">
    <tr>
        <td>
            <div class="brand">DTH Business Core</div>
            <h1 class="title">{{ \Dth\Email\Support\UiText::get('reports.campaign_title', 'Campaign Report') }}: {{ $campaign->name }}</h1>
            <div class="muted" style="margin-top:4px">{{ $campaign->subject }}</div>
        </td>
        <td class="header-meta">
            <div><strong>{{ \Dth\Email\Support\UiText::get('reports.columns.status', 'Status') }}:</strong> {{ \Dth\Email\Support\UiText::status($campaign->status) }}</div>
            <div><strong>{{ \Dth\Email\Support\UiText::get('reports.generated_at', 'Generated at') }}:</strong> {{ $generatedAt->format('d/m/Y H:i') }}</div>
        </td>
    </tr>
</table>
<div class="divider"></div>

<div class="info-panel">
    <table class="info-table">
        <tr>
            <td><span class="info-label">{{ \Dth\Email\Support\UiText::get('reports.sending_account', 'Sending account') }}</span><span class="info-value">{{ $campaign->sendingAccount?->name ?? '—' }}</span></td>
            <td><span class="info-label">{{ \Dth\Email\Support\UiText::get('reports.template', 'Template') }}</span><span class="info-value">{{ $campaign->template?->name ?? '—' }}</span></td>
            <td><span class="info-label">{{ \Dth\Email\Support\UiText::get('reports.started_at', 'Started at') }}</span><span class="info-value">{{ $campaign->started_at?->format('d/m/Y H:i') ?? '—' }}</span></td>
            <td><span class="info-label">{{ \Dth\Email\Support\UiText::get('reports.completed_at', 'Completed at') }}</span><span class="info-value">{{ $campaign->completed_at?->format('d/m/Y H:i') ?? '—' }}</span></td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">{{ \Dth\Email\Support\UiText::get('reports.executive_metrics', 'Executive metrics') }}</div>
    <table class="grid-table">
        <tr>
            <td class="metric"><div class="metric-label">{{ \Dth\Email\Support\UiText::get('analytics.recipients', 'Recipients') }}</div><div class="metric-value">{{ number_format($metrics->total) }}</div></td>
            <td class="metric"><div class="metric-label">{{ \Dth\Email\Support\UiText::get('analytics.sent', 'Sent') }}</div><div class="metric-value">{{ number_format($metrics->sent) }}</div></td>
            <td class="metric"><div class="metric-label">{{ \Dth\Email\Support\UiText::get('reports.unique_opens', 'Unique opens') }}</div><div class="metric-value">{{ number_format($metrics->opened) }}</div><div class="metric-note">{{ number_format($metrics->totalOpens) }} {{ \Dth\Email\Support\UiText::get('reports.total_opens', 'total opens') }}</div></td>
            <td class="metric"><div class="metric-label">{{ \Dth\Email\Support\UiText::get('dashboard.metrics.open_rate', 'Open rate') }}</div><div class="metric-value">{{ number_format($metrics->openRate, 1) }}%</div></td>
        </tr>
        <tr>
            <td class="metric"><div class="metric-label">{{ \Dth\Email\Support\UiText::get('reports.unique_clicks', 'Unique clicks') }}</div><div class="metric-value">{{ number_format($metrics->clicked) }}</div><div class="metric-note">{{ number_format($metrics->totalClicks) }} {{ \Dth\Email\Support\UiText::get('reports.total_clicks', 'total clicks') }}</div></td>
            <td class="metric"><div class="metric-label">{{ \Dth\Email\Support\UiText::get('dashboard.metrics.click_rate', 'Click rate') }}</div><div class="metric-value">{{ number_format($metrics->clickRate, 1) }}%</div></td>
            <td class="metric"><div class="metric-label">{{ \Dth\Email\Support\UiText::get('dashboard.metrics.ctor', 'CTOR') }}</div><div class="metric-value">{{ number_format($metrics->clickToOpenRate, 1) }}%</div></td>
            <td class="metric"><div class="metric-label">{{ \Dth\Email\Support\UiText::get('analytics.unsubscribed', 'Unsubscribed') }}</div><div class="metric-value">{{ number_format($metrics->unsubscribed) }}</div><div class="metric-note">{{ number_format($metrics->unsubscribeRate, 1) }}%</div></td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">{{ \Dth\Email\Support\UiText::get('reports.performance_trend', 'Performance trend') }}</div>
    <div class="panel"><img class="chart" src="{{ $trendChart }}" alt=""></div>
</div>

<div class="section">
    <table class="split">
        <tr>
            <td>
                <div class="panel">
                    <div class="section-title">{{ \Dth\Email\Support\UiText::get('reports.engagement_funnel', 'Engagement funnel') }}</div>
                    @foreach ([
                        [\Dth\Email\Support\UiText::get('dashboard.metrics.recipients', 'Recipients'), $funnel->recipients, ''],
                        [\Dth\Email\Support\UiText::get('dashboard.metrics.sent', 'Sent'), $funnel->sent, ''],
                        [\Dth\Email\Support\UiText::get('dashboard.metrics.opened', 'Opened'), $funnel->opened, 'open'],
                        [\Dth\Email\Support\UiText::get('dashboard.metrics.clicked', 'Clicked'), $funnel->clicked, 'click'],
                        [\Dth\Email\Support\UiText::get('analytics.unsubscribed', 'Unsubscribed'), $funnel->unsubscribed, 'unsub'],
                    ] as [$label, $value, $class])
                        <div class="funnel-row"><table class="funnel-head"><tr><td>{{ $label }}</td><td>{{ number_format($value) }}</td></tr></table><div class="bar-track"><div class="bar {{ $class }}" style="width: {{ $pct((int) $value) }}%"></div></div></div>
                    @endforeach
                </div>
            </td>
            <td>
                <div class="panel">
                    <div class="section-title">{{ \Dth\Email\Support\UiText::get('reports.delivery_quality', 'Delivery quality') }}</div>
                    <table class="data-table">
                        <tr><td>{{ \Dth\Email\Support\UiText::get('reports.failed_messages', 'Failed') }}</td><td class="num"><strong>{{ number_format($metrics->failed) }} ({{ number_format($metrics->failureRate, 1) }}%)</strong></td></tr>
                        <tr><td>{{ \Dth\Email\Support\UiText::get('analytics.delivered', 'Delivered') }}</td><td class="num"><strong>{{ $metrics->capabilities->delivery ? number_format($metrics->delivered) : \Dth\Email\Support\UiText::get('analytics.not_available', 'N/A') }}</strong></td></tr>
                        <tr><td>{{ \Dth\Email\Support\UiText::get('reports.bounced', 'Bounced') }}</td><td class="num"><strong>{{ $metrics->capabilities->bounce ? number_format($metrics->bounced) : \Dth\Email\Support\UiText::get('analytics.not_available', 'N/A') }}</strong></td></tr>
                        <tr><td>{{ \Dth\Email\Support\UiText::get('reports.complained', 'Complained') }}</td><td class="num"><strong>{{ $metrics->capabilities->complaint ? number_format($metrics->complained) : \Dth\Email\Support\UiText::get('analytics.not_available', 'N/A') }}</strong></td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">{{ \Dth\Email\Support\UiText::get('insights.heading', 'Statistical insights') }} · {{ $insights->score }}/100</div>
    <div class="panel">
        <div style="font-weight:700;margin-bottom:7px;">{{ $insights->summary }}</div>
        @forelse ($insights->items as $item)
            <div class="insight">
                <div class="insight-title">{{ \Dth\Email\Support\UiText::get('insights.severity.'.$item->severity, ucfirst($item->severity)) }} · {{ $item->title }}</div>
                <div class="muted">{{ $item->body }}</div>
            </div>
        @empty
            <div class="muted">{{ \Dth\Email\Support\UiText::get('insights.no_findings', 'No significant statistical finding for the available data.') }}</div>
        @endforelse
    </div>
</div>

<div class="section">
    <div class="section-title">{{ \Dth\Email\Support\UiText::get('reports.top_links', 'Link performance') }}</div>
    <table class="data-table">
        <thead><tr><th>{{ \Dth\Email\Support\UiText::get('reports.columns.url', 'URL') }}</th><th class="num">{{ \Dth\Email\Support\UiText::get('reports.columns.unique_clickers', 'Unique clickers') }}</th><th class="num">{{ \Dth\Email\Support\UiText::get('reports.columns.total_clicks', 'Total clicks') }}</th><th class="num">{{ \Dth\Email\Support\UiText::get('reports.columns.click_share', 'Click share') }}</th><th class="num">{{ \Dth\Email\Support\UiText::get('reports.columns.last_click', 'Last click') }}</th></tr></thead>
        <tbody>
        @forelse ($topLinks as $link)
            <tr><td class="url">{{ $link->url }}</td><td class="num">{{ number_format($link->uniqueClickers) }}</td><td class="num">{{ number_format($link->totalClicks) }}</td><td class="num">{{ number_format($link->clickShare, 1) }}%</td><td class="num">{{ $link->lastClickedAt?->format('d/m/Y H:i') ?? '—' }}</td></tr>
        @empty
            <tr><td colspan="5" class="muted">{{ \Dth\Email\Support\UiText::get('reports.no_data', 'No data') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="footer">DTH Business Core · {{ \Dth\Email\Support\UiText::get('reports.campaign_title', 'Campaign Report') }}</div>
</body>
</html>
