@php
    $s = $report['summary'];
    $money = static fn($value, $currency = 'VND') => $value === null ? 'N/A' : number_format((float)$value, 0, ',', '.').' '.$currency;
    $metric = static fn($value) => $value === null ? 'N/A' : number_format((float)$value, 0, ',', '.');
    $serviceNames = collect((array)($campaign->service_snapshot ?? []))->pluck('name')->filter()->values();
    if ($serviceNames->isEmpty()) { $serviceNames = collect((array)($campaign->service_references ?? []))->filter()->values(); }
@endphp

<style>
    .dth-camp-report{display:flex;flex-direction:column;gap:14px}
    .dth-camp-head{display:grid;grid-template-columns:2fr repeat(3,minmax(0,1fr));gap:12px}
    .dth-camp-card{position:relative;overflow:hidden;border:1px solid #e6eaf2;border-radius:15px;padding:15px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.04)}
    .dth-camp-card:first-child{background:linear-gradient(135deg,#f0f1ff,#fff)}
    .dth-camp-card span{font-size:.68rem;color:#7e899b;text-transform:uppercase;font-weight:760;letter-spacing:.035em}.dth-camp-card strong{display:block;font-size:1.28rem;margin-top:.3rem;color:#172033;font-weight:820;letter-spacing:-.025em}
    .dth-camp-grid2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
    .dth-camp-filter{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.8rem;align-items:end}.dth-camp-filter label{display:block;margin-bottom:.35rem;color:#59667c;font-size:.7rem;font-weight:700}
    .dth-camp-table-wrap{overflow-x:auto}.dth-camp-table{width:100%;border-collapse:collapse;font-size:.76rem}.dth-camp-table th{padding:.62rem .65rem;border-bottom:1px solid #edf1f6;text-align:left;white-space:nowrap;color:#8a95a8;font-size:.64rem;text-transform:uppercase;letter-spacing:.035em;font-weight:760}.dth-camp-table td{padding:.68rem .65rem;border-bottom:1px solid #f0f3f7;text-align:left;white-space:nowrap;color:#475467}.dth-camp-table tr:last-child td{border-bottom:0}.dth-camp-table strong{color:#253047}.dth-camp-num{text-align:right!important}
    .dth-camp-insight{position:relative;padding:.72rem .8rem .72rem 1rem;margin:.5rem 0;border:1px solid #edf0f5;border-radius:11px;background:#fbfcfe;color:#475467}.dth-camp-insight:before{content:"";position:absolute;left:0;top:.6rem;bottom:.6rem;width:4px;border-radius:999px;background:#5b5cf0}.dth-camp-insight strong{display:block;color:#273143;font-size:.73rem;margin-bottom:.15rem}.dth-camp-insight .text-sm{font-size:.67rem!important;line-height:1.4}
    @media(max-width:1100px){.dth-camp-head{grid-template-columns:repeat(2,minmax(0,1fr))}.dth-camp-grid2{grid-template-columns:1fr}.dth-camp-filter{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:650px){.dth-camp-head,.dth-camp-filter{grid-template-columns:1fr}}
</style>

<div class="dth-camp-report">
    <x-filament::section :heading="\Dth\Marketing\Support\UiText::get('analytics.filters.heading','Report filters')" icon="heroicon-o-adjustments-horizontal" collapsible>
        <form method="GET" action="{{ $actionUrl }}" class="dth-camp-filter">
            <input type="hidden" name="period" value="custom">
            <div><label class="text-sm font-medium">{{ \Dth\Marketing\Support\UiText::get('analytics.filters.start','Start date') }}</label><x-filament::input.wrapper><x-filament::input type="date" name="start" value="{{ $filter->start->toDateString() }}" required /></x-filament::input.wrapper></div>
            <div><label class="text-sm font-medium">{{ \Dth\Marketing\Support\UiText::get('analytics.filters.end','End date') }}</label><x-filament::input.wrapper><x-filament::input type="date" name="end" value="{{ $filter->end->toDateString() }}" required /></x-filament::input.wrapper></div>
            <div><label class="text-sm font-medium">{{ \Dth\Marketing\Support\UiText::get('analytics.filters.source','Source') }}</label><x-filament::input.wrapper><x-filament::input type="text" name="source" value="{{ $filter->source }}" placeholder="{{ \Dth\Marketing\Support\UiText::get('analytics.filters.all_sources','All sources') }}" /></x-filament::input.wrapper></div>
            <x-filament::button type="submit" icon="heroicon-o-funnel">{{ \Dth\Marketing\Support\UiText::get('analytics.filters.apply','Apply filters') }}</x-filament::button>
        </form>
    </x-filament::section>

    <div class="dth-camp-head">
        <div class="dth-camp-card"><span>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.campaign','Campaign') }}</span><strong>{{ $campaign->name }}</strong><div class="text-sm text-gray-500" style="margin-top:.3rem">{{ \Dth\Marketing\Support\UiText::status($campaign->status) }} · {{ $filter->label() }}</div></div>
        <div class="dth-camp-card"><span>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.views','Views') }}</span><strong>{{ $metric($s['views']) }}</strong></div>
        <div class="dth-camp-card"><span>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.submissions','Submissions') }}</span><strong>{{ $metric($s['submissions']) }}</strong></div>
        <div class="dth-camp-card"><span>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.leads','Leads') }}</span><strong>{{ $s['leads_available'] ? $metric($s['leads']) : 'N/A' }}</strong></div>
        <div class="dth-camp-card"><span>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.customers','Customers') }}</span><strong>{{ $s['financial_available'] ? $metric($s['customers']) : 'N/A' }}</strong></div>
        <div class="dth-camp-card"><span>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.budget','Budget') }}</span><strong>{{ $money($s['budget'],$s['currency']) }}</strong></div>
        <div class="dth-camp-card"><span>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.revenue','Revenue') }}</span><strong>{{ $s['financial_available'] ? $money($s['revenue'],$s['currency']) : 'N/A' }}</strong></div>
        <div class="dth-camp-card"><span>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.roas','ROAS') }}</span><strong>{{ $s['roas'] === null ? 'N/A' : number_format((float)$s['roas'],2).'x' }}</strong></div>
    </div>

    <div class="dth-camp-grid2">
        <x-filament::section :heading="\Dth\Marketing\Support\UiText::get('analytics.campaign_scope','Campaign scope')" icon="heroicon-o-briefcase">
            <div class="dth-camp-table-wrap"><table class="dth-camp-table"><tbody>
                <tr><td>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.period','Period') }}</td><td>{{ $campaign->start_date?->format('d/m/Y') ?? '—' }} → {{ $campaign->end_date?->format('d/m/Y') ?? '—' }}</td></tr>
                <tr><td>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.services','Services') }}</td><td style="white-space:normal">{{ $serviceNames->isEmpty() ? 'N/A' : $serviceNames->implode(', ') }}</td></tr>
                <tr><td>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.currency','Currency') }}</td><td>{{ $campaign->currency ?: 'VND' }}</td></tr>
            </tbody></table></div>
        </x-filament::section>
        <x-filament::section :heading="\Dth\Marketing\Support\UiText::get('analytics.trend','Acquisition trend')" icon="heroicon-o-chart-bar">
            <div class="dth-camp-table-wrap" style="max-height:260px;overflow:auto"><table class="dth-camp-table"><thead><tr><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.date','Date') }}</th><th class="dth-camp-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.views','Views') }}</th><th class="dth-camp-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.submissions','Submissions') }}</th><th class="dth-camp-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.leads','Leads') }}</th></tr></thead><tbody>
            @forelse($report['trend'] as $row)<tr><td>{{ $row['date'] }}</td><td class="dth-camp-num">{{ $row['views'] }}</td><td class="dth-camp-num">{{ $row['submissions'] }}</td><td class="dth-camp-num">{{ $row['leads'] ?? 'N/A' }}</td></tr>@empty<tr><td colspan="4">{{ \Dth\Marketing\Support\UiText::get('analytics.help.no_data','No data') }}</td></tr>@endforelse
            </tbody></table></div>
        </x-filament::section>
    </div>

    <div class="dth-camp-grid2">
        <x-filament::section :heading="\Dth\Marketing\Support\UiText::get('analytics.funnel','Conversion Funnel')" icon="heroicon-o-funnel">
            <div class="dth-camp-table-wrap"><table class="dth-camp-table"><tbody>
                <tr><td>{{ \Dth\Marketing\Support\UiText::get('analytics.help.view_to_submission','View → submission') }}</td><td class="dth-camp-num">{{ number_format((float)$s['view_to_submission'],2) }}%</td></tr>
                <tr><td>{{ \Dth\Marketing\Support\UiText::get('analytics.help.submission_to_lead','Submission → lead') }}</td><td class="dth-camp-num">{{ $s['submission_to_lead'] === null ? 'N/A' : number_format((float)$s['submission_to_lead'],2).'%' }}</td></tr>
                <tr><td>{{ \Dth\Marketing\Support\UiText::get('analytics.help.lead_to_customer','Lead → customer') }}</td><td class="dth-camp-num">{{ $s['lead_to_customer'] === null ? 'N/A' : number_format((float)$s['lead_to_customer'],2).'%' }}</td></tr>
                <tr><td>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.failed_submissions','Failed submissions') }}</td><td class="dth-camp-num">{{ $s['failed'] }}</td></tr>
                <tr><td>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.spam_submissions','Spam submissions') }}</td><td class="dth-camp-num">{{ $s['spam'] }}</td></tr>
            </tbody></table></div>
        </x-filament::section>
        <x-filament::section :heading="\Dth\Marketing\Support\UiText::get('analytics.analysis','Statistical Analysis')" icon="heroicon-o-light-bulb">
            @foreach($report['insights'] as $insight)<div class="dth-camp-insight"><strong>{{ $insight['title'] }}</strong><div class="text-sm text-gray-600 dark:text-gray-300">{{ $insight['body'] }}</div></div>@endforeach
        </x-filament::section>
    </div>

    <x-filament::section :heading="\Dth\Marketing\Support\UiText::get('analytics.labels.landing_pages','Landing Pages')" icon="heroicon-o-window">
        <div class="dth-camp-table-wrap"><table class="dth-camp-table"><thead><tr><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.landing_page','Landing Page') }}</th><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.status','Status') }}</th><th class="dth-camp-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.views','Views') }}</th><th class="dth-camp-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.submissions','Submissions') }}</th><th class="dth-camp-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.leads','Leads') }}</th><th class="dth-camp-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.conversion','Conversion') }}</th></tr></thead><tbody>
        @forelse($report['landing_pages'] as $row)<tr><td><strong>{{ $row['name'] }}</strong></td><td>{{ \Dth\Marketing\Support\UiText::status($row['status']) }}</td><td class="dth-camp-num">{{ $row['views'] }}</td><td class="dth-camp-num">{{ $row['submissions'] }}</td><td class="dth-camp-num">{{ $row['leads'] ?? 'N/A' }}</td><td class="dth-camp-num">{{ number_format((float)$row['conversion_rate'],2) }}%</td></tr>@empty<tr><td colspan="6">{{ \Dth\Marketing\Support\UiText::get('analytics.help.no_data','No data') }}</td></tr>@endforelse
        </tbody></table></div>
    </x-filament::section>

    <div class="dth-camp-grid2">
        <x-filament::section :heading="\Dth\Marketing\Support\UiText::get('analytics.sources','UTM / Sources')" icon="heroicon-o-link">
            <div class="dth-camp-table-wrap"><table class="dth-camp-table"><thead><tr><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.source','Source') }}</th><th class="dth-camp-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.views','Views') }}</th><th class="dth-camp-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.submissions','Submissions') }}</th><th class="dth-camp-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.leads','Leads') }}</th><th class="dth-camp-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.conversion','Conversion') }}</th></tr></thead><tbody>
            @forelse($report['sources'] as $row)<tr><td>{{ $row['source'] }}</td><td class="dth-camp-num">{{ $row['views'] }}</td><td class="dth-camp-num">{{ $row['submissions'] }}</td><td class="dth-camp-num">{{ $row['leads'] ?? 'N/A' }}</td><td class="dth-camp-num">{{ $row['conversion_rate'] === null ? 'N/A' : number_format((float)$row['conversion_rate'],2).'%' }}</td></tr>@empty<tr><td colspan="5">{{ \Dth\Marketing\Support\UiText::get('analytics.help.no_data','No data') }}</td></tr>@endforelse
            </tbody></table></div>
        </x-filament::section>

        <x-filament::section :heading="\Dth\Marketing\Support\UiText::get('analytics.email_campaigns','Email Campaigns')" icon="heroicon-o-envelope">
            <div class="dth-camp-table-wrap"><table class="dth-camp-table"><thead><tr><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.email_campaign','Email Campaign') }}</th><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.status','Status') }}</th><th class="dth-camp-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.sent','Sent') }}</th><th class="dth-camp-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.open','Open') }}</th><th class="dth-camp-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.click','Click') }}</th></tr></thead><tbody>
            @forelse($report['email_campaigns'] as $row)<tr><td>@if($row['admin_url'])<a href="{{ $row['admin_url'] }}" target="_blank" rel="noopener" style="text-decoration:underline">{{ $row['name'] }}</a>@else{{ $row['name'] }}@endif</td><td>{{ $row['status'] ?? 'N/A' }}</td><td class="dth-camp-num">{{ $row['sent'] ?? 'N/A' }}</td><td class="dth-camp-num">{{ $row['open_rate'] === null ? 'N/A' : number_format((float)$row['open_rate'],2).'%' }}</td><td class="dth-camp-num">{{ $row['click_rate'] === null ? 'N/A' : number_format((float)$row['click_rate'],2).'%' }}</td></tr>@empty<tr><td colspan="5">{{ ($health['email']['available'] ?? false) ? \Dth\Marketing\Support\UiText::get('analytics.help.no_linked_campaigns','No linked campaigns') : \Dth\Marketing\Support\UiText::get('analytics.help.email_bridge_unavailable','Email bridge unavailable') }}</td></tr>@endforelse
            </tbody></table></div>
        </x-filament::section>
    </div>

    <x-filament::section :heading="\Dth\Marketing\Support\UiText::get('analytics.generated_utm_links','Generated UTM Links')" icon="heroicon-o-globe-alt" collapsible>
        <div class="dth-camp-table-wrap"><table class="dth-camp-table"><thead><tr><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.landing_page','Landing') }}</th><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.name','Name') }}</th><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.source','Source') }}</th><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.medium','Medium') }}</th><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.campaign','Campaign') }}</th><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.url','URL') }}</th></tr></thead><tbody>
        @forelse($report['utm_links'] as $row)<tr><td>{{ $row['landing_page'] }}</td><td>{{ $row['name'] ?: '—' }}</td><td>{{ $row['source'] ?: '—' }}</td><td>{{ $row['medium'] ?: '—' }}</td><td>{{ $row['campaign'] ?: '—' }}</td><td style="max-width:360px;white-space:normal;word-break:break-all">{{ $row['url'] }}</td></tr>@empty<tr><td colspan="6">{{ \Dth\Marketing\Support\UiText::get('analytics.help.no_utm_links','No generated UTM links.') }}</td></tr>@endforelse
        </tbody></table></div>
    </x-filament::section>
</div>
