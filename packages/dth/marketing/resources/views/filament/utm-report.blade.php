@php
    $ui = \Dth\Marketing\Support\UiText::class;
@endphp
<div style="display:grid;gap:20px">
    <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px">
        @foreach ([
            [$ui::get('utm_report.views', 'Views'), $report['totals']['views']],
            [$ui::get('utm_report.submissions', 'Submissions'), $report['totals']['submissions']],
            [$ui::get('utm_report.conversion', 'Conversion'), number_format($report['totals']['conversion_rate'], 2).'%'],
            [$ui::get('utm_report.generated_links', 'Generated links'), $report['generated_links']],
        ] as [$label, $value])
            <div style="padding:14px;border:1px solid #d1d5db;border-radius:10px">
                <div style="font-size:12px;opacity:.7">{{ $label }}</div>
                <div style="font-size:22px;font-weight:700;margin-top:4px">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div style="overflow-x:auto">
        <h3 style="font-weight:700;margin-bottom:8px">{{ $ui::get('utm_report.attribution', 'Attribution by UTM') }}</h3>
        <table style="width:100%;border-collapse:collapse;font-size:14px">
            <thead><tr>@foreach ([
                $ui::get('utm_report.source', 'Source'),
                $ui::get('utm_report.medium', 'Medium'),
                $ui::get('utm_report.campaign', 'Campaign'),
                $ui::get('utm_report.views', 'Views'),
                $ui::get('utm_report.submissions', 'Submissions'),
                $ui::get('utm_report.conversion', 'Conversion'),
            ] as $h)<th style="text-align:left;padding:9px;border-bottom:1px solid #d1d5db">{{ $h }}</th>@endforeach</tr></thead>
            <tbody>
            @forelse ($report['sources'] as $row)
                <tr>
                    <td style="padding:9px;border-bottom:1px solid #e5e7eb">{{ $row['source'] }}</td>
                    <td style="padding:9px;border-bottom:1px solid #e5e7eb">{{ $row['medium'] }}</td>
                    <td style="padding:9px;border-bottom:1px solid #e5e7eb">{{ $row['campaign'] }}</td>
                    <td style="padding:9px;border-bottom:1px solid #e5e7eb">{{ $row['views'] }}</td>
                    <td style="padding:9px;border-bottom:1px solid #e5e7eb">{{ $row['submissions'] }}</td>
                    <td style="padding:9px;border-bottom:1px solid #e5e7eb">{{ number_format($row['conversion_rate'], 2) }}%</td>
                </tr>
            @empty
                <tr><td colspan="6" style="padding:16px;text-align:center">{{ $ui::get('utm_report.no_traffic', 'No tracked UTM traffic yet.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="overflow-x:auto">
        <h3 style="font-weight:700;margin-bottom:8px">{{ $ui::get('utm_report.links', 'Generated UTM links') }}</h3>
        <table style="width:100%;border-collapse:collapse;font-size:14px">
            <thead><tr>@foreach ([
                $ui::get('common.fields.name', 'Name'),
                $ui::get('utm_report.source', 'Source'),
                $ui::get('utm_report.medium', 'Medium'),
                $ui::get('utm_report.campaign', 'Campaign'),
                $ui::get('utm_report.url', 'URL'),
                $ui::get('common.fields.created_at', 'Created'),
            ] as $h)<th style="text-align:left;padding:9px;border-bottom:1px solid #d1d5db">{{ $h }}</th>@endforeach</tr></thead>
            <tbody>
            @forelse ($links as $link)
                <tr>
                    <td style="padding:9px;border-bottom:1px solid #e5e7eb">{{ $link->name ?: '—' }}</td>
                    <td style="padding:9px;border-bottom:1px solid #e5e7eb">{{ $link->utm_source ?: '—' }}</td>
                    <td style="padding:9px;border-bottom:1px solid #e5e7eb">{{ $link->utm_medium ?: '—' }}</td>
                    <td style="padding:9px;border-bottom:1px solid #e5e7eb">{{ $link->utm_campaign ?: '—' }}</td>
                    <td style="padding:9px;border-bottom:1px solid #e5e7eb;max-width:480px;word-break:break-all"><a href="{{ $link->url }}" target="_blank" rel="noopener">{{ $link->url }}</a></td>
                    <td style="padding:9px;border-bottom:1px solid #e5e7eb">{{ optional($link->created_at)->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="6" style="padding:16px;text-align:center">{{ $ui::get('utm_report.no_links', 'No generated links.') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
