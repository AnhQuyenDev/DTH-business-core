@php
    $s = $report['summary'];
    $money = static fn($value, $currency = 'VND') => $value === null ? 'N/A' : number_format((float)$value, 0, ',', '.').' '.$currency;
    $metric = static fn($value) => $value === null ? 'N/A' : number_format((float)$value, 0, ',', '.');
    $serviceNames = collect((array)($campaign->service_snapshot ?? []))->pluck('name')->filter()->values();
    if ($serviceNames->isEmpty()) { $serviceNames = collect((array)($campaign->service_references ?? []))->filter()->values(); }
@endphp

<style>
    .dth-camp-report{display:flex;flex-direction:column;gap:1.4rem}
    .dth-camp-head{display:grid;grid-template-columns:2fr repeat(3,1fr);gap:1rem}
    .dth-camp-card{border:1px solid rgb(229 231 235);border-radius:12px;padding:1rem;background:white}.dark .dth-camp-card{background:rgb(17 24 39);border-color:rgb(55 65 81)}
    .dth-camp-card span{font-size:.75rem;color:rgb(107 114 128);text-transform:uppercase;font-weight:700}.dth-camp-card strong{display:block;font-size:1.35rem;margin-top:.3rem}
    .dth-camp-grid2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1.2rem}
    .dth-camp-filter{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem;align-items:end}
    .dth-camp-table-wrap{overflow-x:auto}.dth-camp-table{width:100%;border-collapse:collapse;font-size:.875rem}.dth-camp-table th,.dth-camp-table td{padding:.7rem .75rem;border-bottom:1px solid rgb(229 231 235);text-align:left;white-space:nowrap}.dark .dth-camp-table th,.dark .dth-camp-table td{border-color:rgb(55 65 81)}.dth-camp-num{text-align:right!important}
    .dth-camp-insight{border-left:4px solid rgb(99 102 241);padding:.65rem .85rem;margin:.55rem 0;background:rgb(249 250 251);border-radius:6px}.dark .dth-camp-insight{background:rgb(31 41 55)}
    @media(max-width:900px){.dth-camp-head{grid-template-columns:repeat(2,minmax(0,1fr))}.dth-camp-grid2{grid-template-columns:1fr}.dth-camp-filter{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:600px){.dth-camp-head,.dth-camp-filter{grid-template-columns:1fr}}
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
                <tr><td>View → Submission</td><td class="dth-camp-num">{{ number_format((float)$s['view_to_submission'],2) }}%</td></tr>
                <tr><td>Submission → Lead</td><td class="dth-camp-num">{{ $s['submission_to_lead'] === null ? 'N/A' : number_format((float)$s['submission_to_lead'],2).'%' }}</td></tr>
                <tr><td>Lead → Customer</td><td class="dth-camp-num">{{ $s['lead_to_customer'] === null ? 'N/A' : number_format((float)$s['lead_to_customer'],2).'%' }}</td></tr>
                <tr><td>Failed submissions</td><td class="dth-camp-num">{{ $s['failed'] }}</td></tr>
                <tr><td>Spam submissions</td><td class="dth-camp-num">{{ $s['spam'] }}</td></tr>
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
