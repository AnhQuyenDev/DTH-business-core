<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ \Dth\Email\Support\UiText::get('reports.dashboard_title', 'Email Analytics Report') }}</title>
    <style>
        @page { margin: 28px 30px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #111827; font-size: 10px; line-height: 1.45; margin: 0; }
        .muted { color: #6b7280; }
        .brand { font-size: 10px; font-weight: 700; letter-spacing: .08em; color: #b45309; text-transform: uppercase; }
        .title { margin: 3px 0 0; font-size: 22px; line-height: 1.2; font-weight: 700; }
        .header-table, .grid-table, .data-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .header-meta { text-align: right; color: #4b5563; line-height: 1.7; }
        .divider { border-top: 2px solid #f59e0b; margin: 14px 0 16px; }
        .section-title { font-size: 13px; font-weight: 700; margin: 0 0 9px; }
        .section { margin-top: 16px; }
        .grid-table { border-spacing: 7px; border-collapse: separate; margin: 0 -7px; }
        .metric { border: 1px solid #e5e7eb; border-radius: 7px; padding: 10px 11px; background: #ffffff; vertical-align: top; }
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
        .page-break { page-break-before: always; }
        .footer { position: fixed; bottom: -13px; left: 0; right: 0; color: #9ca3af; font-size: 7.5px; text-align: center; }
    </style>
</head>
<body>
@php
    $snapshot = $overview->current;
    $range = $filters->range;
    $funnelBase = max($funnel->recipients, 1);
    $pct = static fn (int $value): float => min(100, round(($value / $funnelBase) * 100, 1));
@endphp

<table class="header-table">
    <tr>
        <td>
            <div class="brand">DTH Business Core</div>
            <h1 class="title">{{ \Dth\Email\Support\UiText::get('reports.dashboard_title', 'Email Analytics Report') }}</h1>
        </td>
        <td class="header-meta">
            <div><strong>{{ \Dth\Email\Support\UiText::get('reports.reporting_period', 'Reporting period') }}:</strong> {{ $range->start->format('d/m/Y') }} - {{ $range->end->format('d/m/Y') }}</div>
            <div><strong>{{ \Dth\Email\Support\UiText::get('reports.generated_at', 'Generated at') }}:</strong> {{ $generatedAt->format('d/m/Y H:i') }}</div>
        </td>
    </tr>
</table>
<div class="divider"></div>

<div class="section-title">{{ \Dth\Email\Support\UiText::get('reports.executive_metrics', 'Executive metrics') }}</div>
<table class="grid-table">
    <tr>
        <td class="metric"><div class="metric-label">{{ \Dth\Email\Support\UiText::get('dashboard.metrics.campaigns', 'Campaigns') }}</div><div class="metric-value">{{ number_format($snapshot->campaigns) }}</div></td>
        <td class="metric"><div class="metric-label">{{ \Dth\Email\Support\UiText::get('dashboard.metrics.recipients', 'Recipients') }}</div><div class="metric-value">{{ number_format($snapshot->recipients) }}</div></td>
        <td class="metric"><div class="metric-label">{{ \Dth\Email\Support\UiText::get('dashboard.metrics.sent', 'Sent') }}</div><div class="metric-value">{{ number_format($snapshot->sent) }}</div></td>
        <td class="metric"><div class="metric-label">{{ \Dth\Email\Support\UiText::get('dashboard.metrics.open_rate', 'Open rate') }}</div><div class="metric-value">{{ number_format($snapshot->openRate, 1) }}%</div><div class="metric-note">{{ number_format($snapshot->uniqueOpened) }} {{ \Dth\Email\Support\UiText::get('dashboard.metrics.unique_opens', 'unique opens') }}</div></td>
    </tr>
    <tr>
        <td class="metric"><div class="metric-label">{{ \Dth\Email\Support\UiText::get('dashboard.metrics.click_rate', 'Click rate') }}</div><div class="metric-value">{{ number_format($snapshot->clickRate, 1) }}%</div><div class="metric-note">{{ number_format($snapshot->uniqueClicked) }} {{ \Dth\Email\Support\UiText::get('dashboard.metrics.unique_clicks', 'unique clicks') }}</div></td>
        <td class="metric"><div class="metric-label">{{ \Dth\Email\Support\UiText::get('dashboard.metrics.ctor', 'CTOR') }}</div><div class="metric-value">{{ number_format($snapshot->clickToOpenRate, 1) }}%</div></td>
        <td class="metric"><div class="metric-label">{{ \Dth\Email\Support\UiText::get('dashboard.metrics.unsubscribe_rate', 'Unsubscribe rate') }}</div><div class="metric-value">{{ number_format($snapshot->unsubscribeRate, 1) }}%</div><div class="metric-note">{{ number_format($snapshot->unsubscribed) }} {{ \Dth\Email\Support\UiText::get('analytics.unsubscribed', 'unsubscribed') }}</div></td>
        <td class="metric"><div class="metric-label">{{ \Dth\Email\Support\UiText::get('dashboard.metrics.failure_rate', 'Failure rate') }}</div><div class="metric-value">{{ number_format($snapshot->failureRate, 1) }}%</div><div class="metric-note">{{ number_format($snapshot->failed) }} {{ \Dth\Email\Support\UiText::get('reports.failed_messages', 'failed') }}</div></td>
    </tr>
</table>

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
                        <div class="funnel-row">
                            <table class="funnel-head"><tr><td>{{ $label }}</td><td>{{ number_format($value) }}</td></tr></table>
                            <div class="bar-track"><div class="bar {{ $class }}" style="width: {{ $pct((int) $value) }}%"></div></div>
                        </div>
                    @endforeach
                </div>
            </td>
            <td>
                <div class="panel">
                    <div class="section-title">{{ \Dth\Email\Support\UiText::get('reports.infrastructure', 'Email infrastructure') }}</div>
                    <table class="data-table">
                        <tr><td>{{ \Dth\Email\Support\UiText::get('dashboard.infrastructure.accounts', 'Sending accounts') }}</td><td class="num"><strong>{{ $health->activeSendingAccounts }}/{{ $health->sendingAccounts }}</strong></td></tr>
                        <tr><td>{{ \Dth\Email\Support\UiText::get('dashboard.infrastructure.domains', 'Verified domains') }}</td><td class="num"><strong>{{ $health->verifiedSendingDomains }}/{{ $health->sendingDomains }}</strong></td></tr>
                        <tr><td>{{ \Dth\Email\Support\UiText::get('dashboard.infrastructure.pending_jobs', 'Pending email jobs') }}</td><td class="num"><strong>{{ $health->pendingEmailJobs ?? 'N/A' }}</strong></td></tr>
                        <tr><td>{{ \Dth\Email\Support\UiText::get('dashboard.infrastructure.failed_jobs', 'Failed jobs') }}</td><td class="num"><strong>{{ $health->failedJobs ?? 'N/A' }}</strong></td></tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>
</div>

<div class="page-break"></div>
<div class="section-title">{{ \Dth\Email\Support\UiText::get('reports.top_campaigns', 'Campaign performance') }}</div>
<table class="data-table">
    <thead>
    <tr>
        <th>{{ \Dth\Email\Support\UiText::get('reports.columns.campaign', 'Campaign') }}</th>
        <th>{{ \Dth\Email\Support\UiText::get('reports.columns.status', 'Status') }}</th>
        <th class="num">{{ \Dth\Email\Support\UiText::get('reports.columns.sent', 'Sent') }}</th>
        <th class="num">{{ \Dth\Email\Support\UiText::get('reports.columns.open_rate', 'Open rate') }}</th>
        <th class="num">{{ \Dth\Email\Support\UiText::get('reports.columns.click_rate', 'Click rate') }}</th>
        <th class="num">{{ \Dth\Email\Support\UiText::get('reports.columns.ctor', 'CTOR') }}</th>
        <th class="num">{{ \Dth\Email\Support\UiText::get('reports.columns.unsubscribed', 'Unsubscribed') }}</th>
    </tr>
    </thead>
    <tbody>
    @forelse ($topCampaigns as $campaignRow)
        <tr>
            <td><strong>{{ $campaignRow->name }}</strong><br><span class="muted">{{ $campaignRow->subject }}</span></td>
            <td>{{ \Dth\Email\Support\UiText::status($campaignRow->status) }}</td>
            <td class="num">{{ number_format($campaignRow->sent) }}</td>
            <td class="num">{{ number_format($campaignRow->openRate, 1) }}%</td>
            <td class="num">{{ number_format($campaignRow->clickRate, 1) }}%</td>
            <td class="num">{{ number_format($campaignRow->clickToOpenRate, 1) }}%</td>
            <td class="num">{{ number_format($campaignRow->unsubscribed) }}</td>
        </tr>
    @empty
        <tr><td colspan="7" class="muted">{{ \Dth\Email\Support\UiText::get('reports.no_data', 'No data') }}</td></tr>
    @endforelse
    </tbody>
</table>

<div class="section">
    <div class="section-title">{{ \Dth\Email\Support\UiText::get('reports.top_links', 'Top clicked links') }}</div>
    <table class="data-table">
        <thead><tr><th>{{ \Dth\Email\Support\UiText::get('reports.columns.url', 'URL') }}</th><th class="num">{{ \Dth\Email\Support\UiText::get('reports.columns.unique_clickers', 'Unique clickers') }}</th><th class="num">{{ \Dth\Email\Support\UiText::get('reports.columns.total_clicks', 'Total clicks') }}</th><th class="num">{{ \Dth\Email\Support\UiText::get('reports.columns.click_share', 'Click share') }}</th></tr></thead>
        <tbody>
        @forelse ($topLinks as $link)
            <tr><td class="url">{{ $link->url }}</td><td class="num">{{ number_format($link->uniqueClickers) }}</td><td class="num">{{ number_format($link->totalClicks) }}</td><td class="num">{{ number_format($link->clickShare, 1) }}%</td></tr>
        @empty
            <tr><td colspan="4" class="muted">{{ \Dth\Email\Support\UiText::get('reports.no_data', 'No data') }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="footer">DTH Business Core · {{ \Dth\Email\Support\UiText::get('reports.dashboard_title', 'Email Analytics Report') }}</div>
</body>
</html>
