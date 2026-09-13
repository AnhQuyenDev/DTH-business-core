@php
    $s = $analytics['summary'];
    $money = static fn($value, $currency = 'VND') => $value === null ? 'N/A' : number_format((float)$value, 0, ',', '.').' '.$currency;
    $metric = static fn($value) => $value === null ? 'N/A' : number_format((float)$value, 0, ',', '.');
    $pct = static fn($value) => $value === null ? 'N/A' : number_format((float)$value, 2).'%';
    $maxTrend = max(1, collect($analytics['trend'])->max(fn($row) => max((int)$row['views'], (int)$row['submissions'])) ?? 1);
@endphp

<style>
    .dth-mkt-shell{display:flex;flex-direction:column;gap:1.5rem}
    .dth-mkt-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem}
    .dth-mkt-kpi{border:1px solid rgb(229 231 235);border-radius:14px;padding:1rem 1.1rem;background:rgb(255 255 255);box-shadow:0 1px 2px rgba(0,0,0,.03)}
    .dark .dth-mkt-kpi{background:rgb(17 24 39);border-color:rgb(55 65 81)}
    .dth-mkt-kpi-label{font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:rgb(107 114 128)}
    .dth-mkt-kpi-value{font-size:1.65rem;font-weight:800;line-height:1.2;margin-top:.35rem;color:rgb(17 24 39)}
    .dark .dth-mkt-kpi-value{color:white}
    .dth-mkt-kpi-help{font-size:.77rem;color:rgb(107 114 128);margin-top:.35rem}
    .dth-mkt-grid2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1.25rem}
    .dth-mkt-table-wrap{overflow-x:auto}
    .dth-mkt-table{width:100%;border-collapse:collapse;font-size:.875rem}
    .dth-mkt-table th{font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:rgb(107 114 128);text-align:left;padding:.65rem .75rem;border-bottom:1px solid rgb(229 231 235);white-space:nowrap}
    .dth-mkt-table td{padding:.72rem .75rem;border-bottom:1px solid rgb(243 244 246);vertical-align:top;white-space:nowrap}
    .dark .dth-mkt-table th,.dark .dth-mkt-table td{border-color:rgb(55 65 81)}
    .dth-mkt-num{text-align:right!important}
    .dth-mkt-funnel{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.65rem}
    .dth-mkt-funnel-step{padding:1rem;border-radius:12px;background:rgb(249 250 251);border:1px solid rgb(229 231 235)}
    .dark .dth-mkt-funnel-step{background:rgb(31 41 55);border-color:rgb(55 65 81)}
    .dth-mkt-funnel-step strong{font-size:1.35rem;display:block;margin-top:.25rem}
    .dth-mkt-trend{display:flex;align-items:flex-end;gap:4px;height:180px;padding-top:1rem}
    .dth-mkt-trend-day{flex:1;min-width:3px;display:flex;align-items:flex-end;gap:1px;height:100%}
    .dth-mkt-trend-view{width:55%;background:rgb(99 102 241);border-radius:3px 3px 0 0;min-height:1px}
    .dth-mkt-trend-sub{width:45%;background:rgb(16 185 129);border-radius:3px 3px 0 0;min-height:1px}
    .dth-mkt-insight{padding:.9rem 1rem;border-radius:10px;border:1px solid rgb(229 231 235);margin-bottom:.65rem}
    .dth-mkt-insight strong{display:block;margin-bottom:.2rem}
    .dth-mkt-insight--warning{border-left:4px solid rgb(245 158 11)}
    .dth-mkt-insight--danger{border-left:4px solid rgb(239 68 68)}
    .dth-mkt-insight--success{border-left:4px solid rgb(16 185 129)}
    .dth-mkt-insight--info{border-left:4px solid rgb(59 130 246)}
    .dth-mkt-insight--neutral{border-left:4px solid rgb(156 163 175)}
    .dth-mkt-health{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:.65rem}
    .dth-mkt-health-item{border:1px solid rgb(229 231 235);border-radius:10px;padding:.8rem}.dark .dth-mkt-health-item{border-color:rgb(55 65 81)}
    @media(max-width:1100px){.dth-mkt-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.dth-mkt-health{grid-template-columns:repeat(3,minmax(0,1fr))}}
    @media(max-width:760px){.dth-mkt-kpis,.dth-mkt-grid2{grid-template-columns:1fr}.dth-mkt-funnel{grid-template-columns:repeat(2,minmax(0,1fr))}.dth-mkt-health{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>

<div class="dth-mkt-shell">
    <div class="dth-mkt-kpis">
        <div class="dth-mkt-kpi"><div class="dth-mkt-kpi-label">{{ \Dth\Marketing\Support\UiText::get('analytics.views','Views') }}</div><div class="dth-mkt-kpi-value">{{ $metric($s['views']) }}</div><div class="dth-mkt-kpi-help">{{ $s['deltas']['views'] === null ? \Dth\Marketing\Support\UiText::get('analytics.help.new_baseline','New baseline') : (($s['deltas']['views'] >= 0 ? '+' : '').$s['deltas']['views'].'% '.\Dth\Marketing\Support\UiText::get('analytics.help.vs_previous','vs previous period')) }}</div></div>
        <div class="dth-mkt-kpi"><div class="dth-mkt-kpi-label">{{ \Dth\Marketing\Support\UiText::get('analytics.submissions','Submissions') }}</div><div class="dth-mkt-kpi-value">{{ $metric($s['submissions']) }}</div><div class="dth-mkt-kpi-help">{{ $pct($s['view_to_submission']) }} {{ \Dth\Marketing\Support\UiText::get('analytics.help.view_to_submission','view → submission') }}</div></div>
        <div class="dth-mkt-kpi"><div class="dth-mkt-kpi-label">{{ \Dth\Marketing\Support\UiText::get('analytics.leads','Leads') }}</div><div class="dth-mkt-kpi-value">{{ $s['leads_available'] ? $metric($s['leads']) : 'N/A' }}</div><div class="dth-mkt-kpi-help">{{ $s['leads_available'] ? $pct($s['submission_to_lead']).' '.\Dth\Marketing\Support\UiText::get('analytics.help.submission_to_lead','submission → lead') : \Dth\Marketing\Support\UiText::get('analytics.help.lead_provider_unavailable','LeadProvider unavailable') }}</div></div>
        <div class="dth-mkt-kpi"><div class="dth-mkt-kpi-label">{{ \Dth\Marketing\Support\UiText::get('analytics.customers','Customers') }}</div><div class="dth-mkt-kpi-value">{{ $s['financial_available'] ? $metric($s['customers']) : 'N/A' }}</div><div class="dth-mkt-kpi-help">{{ $s['financial_available'] ? $pct($s['lead_to_customer']).' '.\Dth\Marketing\Support\UiText::get('analytics.help.lead_to_customer','lead → customer') : ($s['financial_reason'] ?? \Dth\Marketing\Support\UiText::get('analytics.help.revenue_provider_unavailable','RevenueProvider unavailable')) }}</div></div>
        <div class="dth-mkt-kpi"><div class="dth-mkt-kpi-label">{{ \Dth\Marketing\Support\UiText::get('analytics.budget','Budget') }}</div><div class="dth-mkt-kpi-value">{{ $money($s['budget'], $s['currency']) }}</div><div class="dth-mkt-kpi-help">{{ \Dth\Marketing\Support\UiText::get('analytics.help.campaign_budget','Campaign budget in selected scope') }}</div></div>
        <div class="dth-mkt-kpi"><div class="dth-mkt-kpi-label">{{ \Dth\Marketing\Support\UiText::get('analytics.revenue','Revenue') }}</div><div class="dth-mkt-kpi-value">{{ $s['financial_available'] ? $money($s['revenue'], $s['currency']) : 'N/A' }}</div><div class="dth-mkt-kpi-help">{{ \Dth\Marketing\Support\UiText::get('analytics.help.revenue_attributed','Attributed through RevenueProvider') }}</div></div>
        <div class="dth-mkt-kpi"><div class="dth-mkt-kpi-label">{{ \Dth\Marketing\Support\UiText::get('analytics.roas','ROAS') }}</div><div class="dth-mkt-kpi-value">{{ $s['roas'] === null ? 'N/A' : number_format((float)$s['roas'],2).'x' }}</div><div class="dth-mkt-kpi-help">{{ \Dth\Marketing\Support\UiText::get('analytics.help.revenue_budget','Revenue / campaign budget') }}</div></div>
        <div class="dth-mkt-kpi"><div class="dth-mkt-kpi-label">{{ \Dth\Marketing\Support\UiText::get('analytics.quality','Processing quality') }}</div><div class="dth-mkt-kpi-value">{{ $pct(100 - (float)$s['failure_rate']) }}</div><div class="dth-mkt-kpi-help">{{ \Dth\Marketing\Support\UiText::get('analytics.help.failed_spam','Failed :failed · Spam :spam', ['failed' => $pct($s['failure_rate']), 'spam' => $pct($s['spam_rate'])]) }}</div></div>
    </div>

    <div class="dth-mkt-grid2">
        <x-filament::section :heading="\Dth\Marketing\Support\UiText::get('analytics.funnel','Conversion funnel')" icon="heroicon-o-funnel">
            <div class="dth-mkt-funnel">
                @foreach($analytics['funnel'] as $step)
                    <div class="dth-mkt-funnel-step"><span class="text-sm text-gray-500">{{ $step['label'] }}</span><strong>{{ $step['available'] ? $metric($step['value']) : 'N/A' }}</strong></div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section :heading="\Dth\Marketing\Support\UiText::get('analytics.insights','Statistical insights')" icon="heroicon-o-light-bulb">
            @foreach($analytics['insights'] as $insight)
                <div class="dth-mkt-insight dth-mkt-insight--{{ $insight['level'] }}"><strong>{{ $insight['title'] }}</strong><div class="text-sm text-gray-600 dark:text-gray-300">{{ $insight['body'] }}</div></div>
            @endforeach
        </x-filament::section>
    </div>

    <x-filament::section :heading="\Dth\Marketing\Support\UiText::get('analytics.trend','Acquisition trend')" icon="heroicon-o-chart-bar">
        <div class="text-xs text-gray-500">{{ \Dth\Marketing\Support\UiText::get('analytics.help.trend_legend','Purple = views · Green = submissions') }}</div>
        <div class="dth-mkt-trend">
            @foreach($analytics['trend'] as $row)
                <div class="dth-mkt-trend-day" title="{{ $row['date'] }} · Views {{ $row['views'] }} · Submissions {{ $row['submissions'] }}">
                    <div class="dth-mkt-trend-view" style="height:{{ max(1, round(($row['views']/$maxTrend)*100)) }}%"></div>
                    <div class="dth-mkt-trend-sub" style="height:{{ max(1, round(($row['submissions']/$maxTrend)*100)) }}%"></div>
                </div>
            @endforeach
        </div>
    </x-filament::section>

    <div class="dth-mkt-grid2">
        <x-filament::section :heading="\Dth\Marketing\Support\UiText::get('analytics.sources','UTM / acquisition sources')" icon="heroicon-o-link">
            <div class="dth-mkt-table-wrap"><table class="dth-mkt-table"><thead><tr><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.source','Source') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.views','Views') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.submissions','Submissions') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.leads','Leads') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.conversion','Conversion') }}</th></tr></thead><tbody>
            @forelse($analytics['sources'] as $row)<tr><td><strong>{{ $row['source'] }}</strong></td><td class="dth-mkt-num">{{ $row['views'] }}</td><td class="dth-mkt-num">{{ $row['submissions'] }}</td><td class="dth-mkt-num">{{ $row['leads'] ?? 'N/A' }}</td><td class="dth-mkt-num">{{ $row['conversion_rate'] === null ? 'N/A' : number_format((float)$row['conversion_rate'],2).'%' }}</td></tr>@empty<tr><td colspan="5">{{ \Dth\Marketing\Support\UiText::get('analytics.help.no_data','No data') }}</td></tr>@endforelse
            </tbody></table></div>
        </x-filament::section>

        <x-filament::section :heading="\Dth\Marketing\Support\UiText::get('analytics.email_campaigns','Email Campaigns')" icon="heroicon-o-envelope">
            <div class="dth-mkt-table-wrap"><table class="dth-mkt-table"><thead><tr><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.campaign','Campaign') }}</th><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.status','Status') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.sent','Sent') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.open','Open') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.click','Click') }}</th></tr></thead><tbody>
            @forelse($analytics['email_campaigns'] as $row)<tr><td><strong>{{ $row['name'] }}</strong></td><td>{{ $row['status'] ?? 'N/A' }}</td><td class="dth-mkt-num">{{ $row['sent'] ?? 'N/A' }}</td><td class="dth-mkt-num">{{ $row['open_rate'] === null ? 'N/A' : number_format((float)$row['open_rate'],2).'%' }}</td><td class="dth-mkt-num">{{ $row['click_rate'] === null ? 'N/A' : number_format((float)$row['click_rate'],2).'%' }}</td></tr>@empty<tr><td colspan="5">{{ ($health['email']['available'] ?? false) ? \Dth\Marketing\Support\UiText::get('analytics.help.no_email_data','No linked Email Campaign data.') : \Dth\Marketing\Support\UiText::get('analytics.help.email_bridge_unavailable','Email bridge unavailable.') }}</td></tr>@endforelse
            </tbody></table></div>
        </x-filament::section>
    </div>

    <div class="dth-mkt-grid2">
        <x-filament::section :heading="\Dth\Marketing\Support\UiText::get('analytics.utm_medium','UTM Medium attribution')" icon="heroicon-o-arrows-right-left">
            <div class="dth-mkt-table-wrap"><table class="dth-mkt-table"><thead><tr><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.medium','Medium') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.views','Views') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.submissions','Submissions') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.leads','Leads') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.conversion','Conversion') }}</th></tr></thead><tbody>
            @forelse($analytics['utm_mediums'] as $row)<tr><td><strong>{{ $row['medium'] }}</strong></td><td class="dth-mkt-num">{{ $row['views'] }}</td><td class="dth-mkt-num">{{ $row['submissions'] }}</td><td class="dth-mkt-num">{{ $row['leads'] ?? 'N/A' }}</td><td class="dth-mkt-num">{{ $row['conversion_rate'] === null ? 'N/A' : number_format((float)$row['conversion_rate'],2).'%' }}</td></tr>@empty<tr><td colspan="5">{{ \Dth\Marketing\Support\UiText::get('analytics.help.no_data','No data') }}</td></tr>@endforelse
            </tbody></table></div>
        </x-filament::section>

        <x-filament::section :heading="\Dth\Marketing\Support\UiText::get('analytics.utm_campaign','UTM Campaign attribution')" icon="heroicon-o-tag">
            <div class="dth-mkt-table-wrap"><table class="dth-mkt-table"><thead><tr><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.utm_campaign','UTM Campaign') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.views','Views') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.submissions','Submissions') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.leads','Leads') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.conversion','Conversion') }}</th></tr></thead><tbody>
            @forelse($analytics['utm_campaigns'] as $row)<tr><td><strong>{{ $row['campaign'] }}</strong></td><td class="dth-mkt-num">{{ $row['views'] }}</td><td class="dth-mkt-num">{{ $row['submissions'] }}</td><td class="dth-mkt-num">{{ $row['leads'] ?? 'N/A' }}</td><td class="dth-mkt-num">{{ $row['conversion_rate'] === null ? 'N/A' : number_format((float)$row['conversion_rate'],2).'%' }}</td></tr>@empty<tr><td colspan="5">{{ \Dth\Marketing\Support\UiText::get('analytics.help.no_data','No data') }}</td></tr>@endforelse
            </tbody></table></div>
        </x-filament::section>
    </div>

    <x-filament::section :heading="\Dth\Marketing\Support\UiText::get('analytics.campaigns','Campaign performance')" icon="heroicon-o-megaphone">
        <div class="dth-mkt-table-wrap"><table class="dth-mkt-table"><thead><tr><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.campaign','Campaign') }}</th><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.status','Status') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.views','Views') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.submissions','Submissions') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.leads','Leads') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.budget','Budget') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.revenue','Revenue') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.roas','ROAS') }}</th></tr></thead><tbody>
        @forelse($analytics['campaigns'] as $row)<tr><td><strong>{{ $row['name'] }}</strong></td><td>{{ \Dth\Marketing\Support\UiText::status($row['status']) }}</td><td class="dth-mkt-num">{{ $row['views'] }}</td><td class="dth-mkt-num">{{ $row['submissions'] }}</td><td class="dth-mkt-num">{{ $row['leads'] ?? 'N/A' }}</td><td class="dth-mkt-num">{{ $money($row['budget'],$row['currency']) }}</td><td class="dth-mkt-num">{{ $row['financial_available'] ? $money($row['revenue'],$row['currency']) : 'N/A' }}</td><td class="dth-mkt-num">{{ $row['roas'] === null ? 'N/A' : number_format((float)$row['roas'],2).'x' }}</td></tr>@empty<tr><td colspan="8">{{ \Dth\Marketing\Support\UiText::get('analytics.help.no_campaign_data','No campaign data for this filter.') }}</td></tr>@endforelse
        </tbody></table></div>
    </x-filament::section>

    <x-filament::section :heading="\Dth\Marketing\Support\UiText::get('analytics.landing_pages','Landing Page performance')" icon="heroicon-o-window">
        <div class="dth-mkt-table-wrap"><table class="dth-mkt-table"><thead><tr><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.landing_page','Landing Page') }}</th><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.status','Status') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.views','Views') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.submissions','Submissions') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.leads','Leads') }}</th><th class="dth-mkt-num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.conversion','Conversion') }}</th></tr></thead><tbody>
        @forelse($analytics['landing_pages'] as $row)<tr><td><strong>{{ $row['name'] }}</strong></td><td>{{ \Dth\Marketing\Support\UiText::status($row['status']) }}</td><td class="dth-mkt-num">{{ $row['views'] }}</td><td class="dth-mkt-num">{{ $row['submissions'] }}</td><td class="dth-mkt-num">{{ $row['leads'] ?? 'N/A' }}</td><td class="dth-mkt-num">{{ number_format((float)$row['conversion_rate'],2) }}%</td></tr>@empty<tr><td colspan="6">{{ \Dth\Marketing\Support\UiText::get('analytics.help.no_landing_data','No Landing Page data for this filter.') }}</td></tr>@endforelse
        </tbody></table></div>
    </x-filament::section>

    <x-filament::section :heading="\Dth\Marketing\Support\UiText::get('analytics.integration_health','Integration health')" icon="heroicon-o-heart">
        <div class="dth-mkt-health">
            @foreach($health as $name => $status)
                <div class="dth-mkt-health-item">
                    <div class="text-sm font-semibold">{{ \Dth\Marketing\Support\UiText::get('analytics.integration.'.$name, ucfirst($name)) }}</div>
                    <div style="margin-top:.35rem">
                        <x-filament::badge :color="!($status['healthy'] ?? true) ? 'danger' : ($status['available'] ? 'success' : 'gray')">
                            {{ !($status['healthy'] ?? true)
                                ? \Dth\Marketing\Support\UiText::get('analytics.integration.error', 'Error')
                                : ($status['available']
                                    ? \Dth\Marketing\Support\UiText::get('analytics.integration.available', 'Available')
                                    : \Dth\Marketing\Support\UiText::get('analytics.integration.unavailable', 'N/A')) }}
                        </x-filament::badge>
                    </div>
                    <div class="text-xs text-gray-500" style="margin-top:.4rem">
                        {{ !($status['healthy'] ?? true)
                            ? ($status['error'] ?? \Dth\Marketing\Support\UiText::get('analytics.integration.provider_error', 'Provider error'))
                            : \Dth\Marketing\Support\UiText::get('analytics.integration.capability_flags', ':count capability', ['count' => count($status['capabilities'])]) }}
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</div>
