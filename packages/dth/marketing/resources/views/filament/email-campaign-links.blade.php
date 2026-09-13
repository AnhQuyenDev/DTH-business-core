<div style="display:grid;gap:16px">
    @if (! $available)
        <div style="padding:12px;border:1px solid #f59e0b;border-radius:10px">
            {{ \Dth\Marketing\Support\UiText::get('email_bridge.unavailable', 'Email bridge is unavailable. Existing manual references remain visible.') }}
        </div>
    @endif

    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:14px">
            <thead>
                <tr>
                    @foreach ([
                        \Dth\Marketing\Support\UiText::get('common.fields.name', 'Name'),
                        \Dth\Marketing\Support\UiText::get('email_bridge.reference', 'Reference'),
                        \Dth\Marketing\Support\UiText::get('common.fields.status', 'Status'),
                        \Dth\Marketing\Support\UiText::get('email_bridge.metrics', 'Metrics'),
                        \Dth\Marketing\Support\UiText::get('email_bridge.module', 'Email Module'),
                    ] as $h)
                        <th style="text-align:left;padding:10px;border-bottom:1px solid #d1d5db">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @forelse ($links as $link)
                @php($url = $service->adminUrl($link))
                <tr>
                    <td style="padding:10px;border-bottom:1px solid #e5e7eb">{{ $link->display_name ?: '—' }}</td>
                    <td style="padding:10px;border-bottom:1px solid #e5e7eb">{{ $link->email_campaign_reference }}</td>
                    <td style="padding:10px;border-bottom:1px solid #e5e7eb">{{ $link->status_snapshot ?: 'N/A' }}</td>
                    <td style="padding:10px;border-bottom:1px solid #e5e7eb;white-space:pre-wrap">{{ ! empty($link->metrics_snapshot) ? json_encode($link->metrics_snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : 'N/A' }}</td>
                    <td style="padding:10px;border-bottom:1px solid #e5e7eb">
                        @if ($url)
                            <a href="{{ $url }}" target="_blank" rel="noopener">{{ \Dth\Marketing\Support\UiText::get('email_bridge.open', 'Open Email') }}</a>
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" style="padding:18px;text-align:center">{{ \Dth\Marketing\Support\UiText::get('email_bridge.no_links', 'No Email Campaign is linked.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
