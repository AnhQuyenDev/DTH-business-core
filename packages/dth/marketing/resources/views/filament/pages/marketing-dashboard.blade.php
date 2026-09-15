@php
    $s = $analytics['summary'];
    $money = static fn($value, $currency = 'VND') => $value === null ? 'N/A' : number_format((float)$value, 0, ',', '.').' '.$currency;
    $metric = static fn($value) => $value === null ? 'N/A' : number_format((float)$value, 0, ',', '.');
    $pct = static fn($value) => $value === null ? 'N/A' : number_format((float)$value, 1, ',', '.').'%';

    $trend = collect($analytics['trend'] ?? [])->values();
    $trendCount = max(1, $trend->count());
    $maxTrend = max(1, (float) ($trend->max(fn($row) => max((float)($row['views'] ?? 0), (float)($row['submissions'] ?? 0))) ?? 1));
    $pointString = static function ($rows, string $key, float $max, int $count): string {
        if ($rows->isEmpty()) return '';
        return $rows->map(function ($row, $index) use ($key, $max, $count) {
            $x = $count <= 1 ? 0 : ($index / ($count - 1)) * 1000;
            $y = 220 - (((float)($row[$key] ?? 0) / $max) * 180);
            return number_format($x, 1, '.', '').','.number_format($y, 1, '.', '');
        })->implode(' ');
    };
    $viewPoints = $pointString($trend, 'views', $maxTrend, $trendCount);
    $submissionPoints = $pointString($trend, 'submissions', $maxTrend, $trendCount);

    $kpis = [
        ['label' => \Dth\Marketing\Support\UiText::get('analytics.views','Views'), 'value' => $metric($s['views']), 'help' => $s['deltas']['views'] === null ? \Dth\Marketing\Support\UiText::get('analytics.help.new_baseline','New baseline') : (($s['deltas']['views'] >= 0 ? '+' : '').$s['deltas']['views'].'% '.\Dth\Marketing\Support\UiText::get('analytics.help.vs_previous','vs previous period')), 'icon' => 'heroicon-o-eye', 'tone' => 'blue'],
        ['label' => \Dth\Marketing\Support\UiText::get('analytics.submissions','Submissions'), 'value' => $metric($s['submissions']), 'help' => $pct($s['view_to_submission']).' '.\Dth\Marketing\Support\UiText::get('analytics.help.view_to_submission','view → submission'), 'icon' => 'heroicon-o-document-check', 'tone' => 'green'],
        ['label' => \Dth\Marketing\Support\UiText::get('analytics.leads','Leads'), 'value' => $s['leads_available'] ? $metric($s['leads']) : 'N/A', 'help' => $s['leads_available'] ? $pct($s['submission_to_lead']).' '.\Dth\Marketing\Support\UiText::get('analytics.help.submission_to_lead','submission → lead') : \Dth\Marketing\Support\UiText::get('analytics.help.lead_provider_unavailable','LeadProvider unavailable'), 'icon' => 'heroicon-o-user-plus', 'tone' => 'violet'],
        ['label' => \Dth\Marketing\Support\UiText::get('analytics.customers','Customers'), 'value' => $s['financial_available'] ? $metric($s['customers']) : 'N/A', 'help' => $s['financial_available'] ? $pct($s['lead_to_customer']).' '.\Dth\Marketing\Support\UiText::get('analytics.help.lead_to_customer','lead → customer') : ($s['financial_reason'] ?? \Dth\Marketing\Support\UiText::get('analytics.help.revenue_provider_unavailable','RevenueProvider unavailable')), 'icon' => 'heroicon-o-user-group', 'tone' => 'amber'],
        ['label' => \Dth\Marketing\Support\UiText::get('analytics.budget','Budget'), 'value' => $money($s['budget'], $s['currency']), 'help' => \Dth\Marketing\Support\UiText::get('analytics.help.campaign_budget','Campaign budget in selected scope'), 'icon' => 'heroicon-o-banknotes', 'tone' => 'blue'],
        ['label' => \Dth\Marketing\Support\UiText::get('analytics.revenue','Revenue'), 'value' => $s['financial_available'] ? $money($s['revenue'], $s['currency']) : 'N/A', 'help' => \Dth\Marketing\Support\UiText::get('analytics.help.revenue_attributed','Attributed through RevenueProvider'), 'icon' => 'heroicon-o-currency-dollar', 'tone' => 'green'],
        ['label' => \Dth\Marketing\Support\UiText::get('analytics.roas','ROAS'), 'value' => $s['roas'] === null ? 'N/A' : number_format((float)$s['roas'],2).'x', 'help' => \Dth\Marketing\Support\UiText::get('analytics.help.revenue_budget','Revenue / campaign budget'), 'icon' => 'heroicon-o-arrow-trending-up', 'tone' => 'violet'],
        ['label' => \Dth\Marketing\Support\UiText::get('analytics.quality','Processing quality'), 'value' => $pct(100 - (float)$s['failure_rate']), 'help' => \Dth\Marketing\Support\UiText::get('analytics.help.failed_spam','Failed :failed · Spam :spam', ['failed' => $pct($s['failure_rate']), 'spam' => $pct($s['spam_rate'])]), 'icon' => 'heroicon-o-shield-check', 'tone' => 'amber'],
    ];
@endphp

<div class="dth-mkt-dashboard-shell">
    <div class="dth-mkt-kpi-grid">
        @foreach($kpis as $card)
            <div class="dth-mkt-kpi-card" data-tone="{{ $card['tone'] }}">
                <div class="dth-mkt-kpi-icon"><x-filament::icon :icon="$card['icon']" /></div>
                <div class="dth-mkt-kpi-copy">
                    <div class="dth-mkt-kpi-label">{{ $card['label'] }}</div>
                    <div class="dth-mkt-kpi-value">{{ $card['value'] }}</div>
                    <div class="dth-mkt-kpi-help">{{ $card['help'] }}</div>
                </div>
                <div class="dth-mkt-kpi-glow"></div>
            </div>
        @endforeach
    </div>

    <div class="dth-mkt-dashboard-grid dth-mkt-dashboard-grid--hero">
        <section class="dth-mkt-dashboard-card dth-mkt-dashboard-card--trend">
            <div class="dth-mkt-card-head">
                <div>
                    <h3>{{ \Dth\Marketing\Support\UiText::get('analytics.trend','Acquisition trend') }}</h3>
                    <p>{{ \Dth\Marketing\Support\UiText::get('analytics.help.trend_legend','Purple = views · Green = submissions') }}</p>
                </div>
                <div class="dth-mkt-chart-legend"><span class="is-view"></span>{{ \Dth\Marketing\Support\UiText::get('analytics.views','Views') }}<span class="is-sub"></span>{{ \Dth\Marketing\Support\UiText::get('analytics.submissions','Submissions') }}</div>
            </div>
            <div class="dth-mkt-line-chart">
                <svg viewBox="0 0 1000 240" preserveAspectRatio="none" aria-hidden="true">
                    <g class="dth-mkt-grid-lines"><line x1="0" y1="40" x2="1000" y2="40"/><line x1="0" y1="100" x2="1000" y2="100"/><line x1="0" y1="160" x2="1000" y2="160"/><line x1="0" y1="220" x2="1000" y2="220"/></g>
                    @if($viewPoints)<polyline class="dth-mkt-line dth-mkt-line--views" points="{{ $viewPoints }}" fill="none" vector-effect="non-scaling-stroke"/>@endif
                    @if($submissionPoints)<polyline class="dth-mkt-line dth-mkt-line--subs" points="{{ $submissionPoints }}" fill="none" vector-effect="non-scaling-stroke"/>@endif
                </svg>
                <div class="dth-mkt-chart-labels">
                    @foreach($trend as $index => $row)
                        @if($index === 0 || $index === $trendCount - 1 || $index % max(1,(int)ceil($trendCount/6)) === 0)
                            <span style="left:{{ $trendCount <= 1 ? 0 : ($index/($trendCount-1))*100 }}%">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d/m') }}</span>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>

        <section class="dth-mkt-dashboard-card dth-mkt-dashboard-card--funnel">
            <div class="dth-mkt-card-head"><div><h3>{{ \Dth\Marketing\Support\UiText::get('analytics.funnel','Conversion funnel') }}</h3><p>{{ \Dth\Marketing\Support\UiText::get('analytics.help.view_to_submission','view → submission') }}</p></div><x-filament::icon icon="heroicon-o-funnel" /></div>
            <div class="dth-mkt-funnel-stack">
                @foreach($analytics['funnel'] as $index => $step)
                    @php $width = max(48, 100 - ($index * 14)); @endphp
                    <div class="dth-mkt-funnel-row" style="width:{{ $width }}%">
                        <span>{{ $step['label'] }}</span><strong>{{ $step['available'] ? $metric($step['value']) : 'N/A' }}</strong>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="dth-mkt-dashboard-grid dth-mkt-dashboard-grid--3">
        <section class="dth-mkt-dashboard-card">
            <div class="dth-mkt-card-head"><div><h3>{{ \Dth\Marketing\Support\UiText::get('analytics.campaigns','Campaign performance') }}</h3><p>{{ \Dth\Marketing\Support\UiText::get('analytics.help.no_campaign_data','No campaign data for this filter.') }}</p></div><x-filament::icon icon="heroicon-o-megaphone" /></div>
            <div class="dth-mkt-table-wrap"><table class="dth-mkt-data-table"><thead><tr><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.campaign','Campaign') }}</th><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.status','Status') }}</th><th class="num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.submissions','Submissions') }}</th><th class="num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.roas','ROAS') }}</th></tr></thead><tbody>
            @forelse(collect($analytics['campaigns'])->take(5) as $row)<tr><td><strong>{{ $row['name'] }}</strong></td><td><span class="dth-mkt-status-pill">{{ \Dth\Marketing\Support\UiText::status($row['status']) }}</span></td><td class="num">{{ $row['submissions'] }}</td><td class="num">{{ $row['roas'] === null ? 'N/A' : number_format((float)$row['roas'],2).'x' }}</td></tr>@empty<tr><td colspan="4" class="empty">{{ \Dth\Marketing\Support\UiText::get('analytics.help.no_campaign_data','No campaign data for this filter.') }}</td></tr>@endforelse
            </tbody></table></div>
        </section>

        <section class="dth-mkt-dashboard-card">
            <div class="dth-mkt-card-head"><div><h3>{{ \Dth\Marketing\Support\UiText::get('analytics.landing_pages','Landing Page performance') }}</h3><p>{{ \Dth\Marketing\Support\UiText::get('analytics.help.no_landing_data','No Landing Page data for this filter.') }}</p></div><x-filament::icon icon="heroicon-o-window" /></div>
            <div class="dth-mkt-table-wrap"><table class="dth-mkt-data-table"><thead><tr><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.landing_page','Landing Page') }}</th><th class="num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.views','Views') }}</th><th class="num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.submissions','Submissions') }}</th><th class="num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.conversion','Conversion') }}</th></tr></thead><tbody>
            @forelse(collect($analytics['landing_pages'])->take(5) as $row)<tr><td><strong>{{ $row['name'] }}</strong><small>{{ \Dth\Marketing\Support\UiText::status($row['status']) }}</small></td><td class="num">{{ $row['views'] }}</td><td class="num">{{ $row['submissions'] }}</td><td class="num accent">{{ number_format((float)$row['conversion_rate'],1) }}%</td></tr>@empty<tr><td colspan="4" class="empty">{{ \Dth\Marketing\Support\UiText::get('analytics.help.no_landing_data','No Landing Page data for this filter.') }}</td></tr>@endforelse
            </tbody></table></div>
        </section>

        <section class="dth-mkt-dashboard-card dth-mkt-dashboard-card--insights">
            <div class="dth-mkt-card-head"><div><h3>{{ \Dth\Marketing\Support\UiText::get('analytics.insights','Statistical insights') }}</h3><p>{{ \Dth\Marketing\Support\UiText::get('analytics.analysis','Statistical analysis') }}</p></div><x-filament::icon icon="heroicon-o-light-bulb" /></div>
            <div class="dth-mkt-insight-list">
                @forelse(collect($analytics['insights'])->take(5) as $insight)
                    <div class="dth-mkt-insight" data-level="{{ $insight['level'] }}"><span></span><div><strong>{{ $insight['title'] }}</strong><p>{{ $insight['body'] }}</p></div></div>
                @empty
                    <div class="dth-mkt-empty">{{ \Dth\Marketing\Support\UiText::get('analytics.help.no_data','No data') }}</div>
                @endforelse
            </div>
        </section>
    </div>

    <div class="dth-mkt-dashboard-grid dth-mkt-dashboard-grid--2">
        <section class="dth-mkt-dashboard-card">
            <div class="dth-mkt-card-head"><div><h3>{{ \Dth\Marketing\Support\UiText::get('analytics.sources','UTM / acquisition sources') }}</h3><p>UTM source</p></div><x-filament::icon icon="heroicon-o-link" /></div>
            <div class="dth-mkt-table-wrap"><table class="dth-mkt-data-table"><thead><tr><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.source','Source') }}</th><th class="num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.views','Views') }}</th><th class="num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.submissions','Submissions') }}</th><th class="num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.conversion','Conversion') }}</th></tr></thead><tbody>
            @forelse($analytics['sources'] as $row)<tr><td><strong>{{ $row['source'] }}</strong></td><td class="num">{{ $row['views'] }}</td><td class="num">{{ $row['submissions'] }}</td><td class="num accent">{{ $row['conversion_rate'] === null ? 'N/A' : number_format((float)$row['conversion_rate'],1).'%' }}</td></tr>@empty<tr><td colspan="4" class="empty">{{ \Dth\Marketing\Support\UiText::get('analytics.help.no_data','No data') }}</td></tr>@endforelse
            </tbody></table></div>
        </section>

        <section class="dth-mkt-dashboard-card">
            <div class="dth-mkt-card-head"><div><h3>{{ \Dth\Marketing\Support\UiText::get('analytics.email_campaigns','Email Campaigns') }}</h3><p>{{ \Dth\Marketing\Support\UiText::get('analytics.help.no_linked_campaigns','No linked campaigns') }}</p></div><x-filament::icon icon="heroicon-o-envelope" /></div>
            <div class="dth-mkt-table-wrap"><table class="dth-mkt-data-table"><thead><tr><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.campaign','Campaign') }}</th><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.status','Status') }}</th><th class="num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.sent','Sent') }}</th><th class="num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.open','Open') }}</th><th class="num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.click','Click') }}</th></tr></thead><tbody>
            @forelse($analytics['email_campaigns'] as $row)<tr><td><strong>{{ $row['name'] }}</strong></td><td>{{ $row['status'] ?? 'N/A' }}</td><td class="num">{{ $row['sent'] ?? 'N/A' }}</td><td class="num">{{ $row['open_rate'] === null ? 'N/A' : number_format((float)$row['open_rate'],1).'%' }}</td><td class="num">{{ $row['click_rate'] === null ? 'N/A' : number_format((float)$row['click_rate'],1).'%' }}</td></tr>@empty<tr><td colspan="5" class="empty">{{ ($health['email']['available'] ?? false) ? \Dth\Marketing\Support\UiText::get('analytics.help.no_email_data','No linked Email Campaign data.') : \Dth\Marketing\Support\UiText::get('analytics.help.email_bridge_unavailable','Email bridge unavailable.') }}</td></tr>@endforelse
            </tbody></table></div>
        </section>
    </div>

    <div class="dth-mkt-dashboard-grid dth-mkt-dashboard-grid--2">
        <section class="dth-mkt-dashboard-card">
            <div class="dth-mkt-card-head"><div><h3>{{ \Dth\Marketing\Support\UiText::get('analytics.utm_medium_attribution','UTM medium attribution') }}</h3><p>{{ \Dth\Marketing\Support\UiText::get('analytics.sources','UTM / acquisition sources') }}</p></div><x-filament::icon icon="heroicon-o-arrows-right-left" /></div>
            <div class="dth-mkt-table-wrap"><table class="dth-mkt-data-table"><thead><tr><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.medium','Medium') }}</th><th class="num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.views','Views') }}</th><th class="num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.submissions','Submissions') }}</th><th class="num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.conversion','Conversion') }}</th></tr></thead><tbody>
            @forelse($analytics['utm_mediums'] as $row)<tr><td><strong>{{ $row['medium'] }}</strong></td><td class="num">{{ $row['views'] }}</td><td class="num">{{ $row['submissions'] }}</td><td class="num accent">{{ $row['conversion_rate'] === null ? 'N/A' : number_format((float)$row['conversion_rate'],1).'%' }}</td></tr>@empty<tr><td colspan="4" class="empty">{{ \Dth\Marketing\Support\UiText::get('analytics.help.no_data','No data') }}</td></tr>@endforelse
            </tbody></table></div>
        </section>

        <section class="dth-mkt-dashboard-card">
            <div class="dth-mkt-card-head"><div><h3>{{ \Dth\Marketing\Support\UiText::get('analytics.utm_campaign_attribution','UTM campaign attribution') }}</h3><p>{{ \Dth\Marketing\Support\UiText::get('analytics.sources','UTM / acquisition sources') }}</p></div><x-filament::icon icon="heroicon-o-tag" /></div>
            <div class="dth-mkt-table-wrap"><table class="dth-mkt-data-table"><thead><tr><th>{{ \Dth\Marketing\Support\UiText::get('analytics.labels.utm_campaign','UTM Campaign') }}</th><th class="num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.views','Views') }}</th><th class="num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.submissions','Submissions') }}</th><th class="num">{{ \Dth\Marketing\Support\UiText::get('analytics.labels.conversion','Conversion') }}</th></tr></thead><tbody>
            @forelse($analytics['utm_campaigns'] as $row)<tr><td><strong>{{ $row['campaign'] }}</strong></td><td class="num">{{ $row['views'] }}</td><td class="num">{{ $row['submissions'] }}</td><td class="num accent">{{ $row['conversion_rate'] === null ? 'N/A' : number_format((float)$row['conversion_rate'],1).'%' }}</td></tr>@empty<tr><td colspan="4" class="empty">{{ \Dth\Marketing\Support\UiText::get('analytics.help.no_data','No data') }}</td></tr>@endforelse
            </tbody></table></div>
        </section>
    </div>

    <section class="dth-mkt-dashboard-card">
        <div class="dth-mkt-card-head"><div><h3>{{ \Dth\Marketing\Support\UiText::get('analytics.integration_health','Integration health') }}</h3><p>{{ \Dth\Marketing\Support\UiText::get('overview.foundation.description','External capabilities are available only when their adapters are registered.') }}</p></div><x-filament::icon icon="heroicon-o-heart" /></div>
        <div class="dth-mkt-health-grid">
            @foreach(['audience','lead','catalog','revenue','email'] as $integration)
                @php $item = $health[$integration] ?? []; $available = (bool)($item['available'] ?? false); @endphp
                <div class="dth-mkt-health-item" data-state="{{ $available ? 'ok' : 'na' }}">
                    <span class="dth-mkt-health-dot"></span>
                    <div><strong>{{ \Dth\Marketing\Support\UiText::get('analytics.integration.'.$integration, ucfirst($integration)) }}</strong><small>{{ $available ? \Dth\Marketing\Support\UiText::get('analytics.integration.available','Available') : \Dth\Marketing\Support\UiText::get('analytics.integration.unavailable','Unavailable') }}</small></div>
                    <b>{{ (int)($item['capabilities'] ?? 0) }}</b>
                </div>
            @endforeach
        </div>
    </section>
</div>

<style>
    .dth-mkt-dashboard-shell{display:flex;flex-direction:column;gap:14px}.dth-mkt-kpi-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.dth-mkt-kpi-card{--tone:91,92,240;position:relative;min-height:108px;overflow:hidden;display:grid;grid-template-columns:44px minmax(0,1fr);gap:12px;padding:15px;border:1px solid #e6eaf2;border-radius:15px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.04)}.dth-mkt-kpi-card[data-tone=blue]{--tone:47,128,237}.dth-mkt-kpi-card[data-tone=green]{--tone:18,183,106}.dth-mkt-kpi-card[data-tone=violet]{--tone:139,92,246}.dth-mkt-kpi-card[data-tone=amber]{--tone:245,158,11}.dth-mkt-kpi-icon{width:44px;height:44px;display:grid;place-items:center;border-radius:13px;color:rgb(var(--tone));background:rgba(var(--tone),.1)}.dth-mkt-kpi-icon svg{width:22px;height:22px}.dth-mkt-kpi-label{font-size:.73rem;font-weight:700;color:#758197}.dth-mkt-kpi-value{margin-top:.15rem;font-size:1.42rem;font-weight:820;color:#111827;letter-spacing:-.03em}.dth-mkt-kpi-help{margin-top:.22rem;font-size:.66rem;color:#8d98aa;line-height:1.25}.dth-mkt-kpi-glow{position:absolute;width:70px;height:70px;border-radius:999px;right:-30px;bottom:-34px;background:rgba(var(--tone),.1)}
    .dth-mkt-dashboard-grid{display:grid;gap:14px}.dth-mkt-dashboard-grid--hero{grid-template-columns:minmax(0,2fr) minmax(300px,.8fr)}.dth-mkt-dashboard-grid--3{grid-template-columns:repeat(3,minmax(0,1fr))}.dth-mkt-dashboard-grid--2{grid-template-columns:repeat(2,minmax(0,1fr))}.dth-mkt-dashboard-card{min-width:0;border:1px solid #e6eaf2;border-radius:16px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.04);padding:16px}.dth-mkt-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:12px}.dth-mkt-card-head h3{margin:0;color:#172033;font-size:.94rem;font-weight:800}.dth-mkt-card-head p{margin:.24rem 0 0;color:#8a95a8;font-size:.68rem;line-height:1.35}.dth-mkt-card-head>svg{width:19px;height:19px;color:#7f89a0}
    .dth-mkt-chart-legend{display:flex;align-items:center;gap:.4rem;color:#7f8a9d;font-size:.67rem;white-space:nowrap}.dth-mkt-chart-legend span{width:8px;height:8px;border-radius:999px}.dth-mkt-chart-legend .is-view{background:#5b5cf0}.dth-mkt-chart-legend .is-sub{background:#12b76a}.dth-mkt-line-chart{position:relative;height:260px;padding:6px 0 24px}.dth-mkt-line-chart svg{width:100%;height:220px;overflow:visible}.dth-mkt-grid-lines line{stroke:#edf1f6;stroke-width:1}.dth-mkt-line{stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round}.dth-mkt-line--views{stroke:#5b5cf0}.dth-mkt-line--subs{stroke:#12b76a}.dth-mkt-chart-labels{position:absolute;left:0;right:0;bottom:0;height:18px}.dth-mkt-chart-labels span{position:absolute;transform:translateX(-50%);color:#9aa4b5;font-size:.62rem;white-space:nowrap}
    .dth-mkt-funnel-stack{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:7px;padding:10px 5px 4px}.dth-mkt-funnel-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;min-height:44px;padding:0 15px;border-radius:9px;background:linear-gradient(90deg,#5b5cf0,#7475fa);color:#fff}.dth-mkt-funnel-row:nth-child(2){background:linear-gradient(90deg,#3b82f6,#60a5fa)}.dth-mkt-funnel-row:nth-child(3){background:linear-gradient(90deg,#12b76a,#44cf8f)}.dth-mkt-funnel-row:nth-child(4){background:linear-gradient(90deg,#84d9b1,#b8ead0);color:#175b3d}.dth-mkt-funnel-row span{font-size:.68rem;font-weight:700}.dth-mkt-funnel-row strong{font-size:.88rem;font-weight:850}
    .dth-mkt-table-wrap{overflow:auto}.dth-mkt-data-table{width:100%;border-collapse:collapse;font-size:.74rem}.dth-mkt-data-table th{text-align:left;padding:.55rem .5rem;color:#8a95a8;font-size:.62rem;font-weight:760;text-transform:uppercase;letter-spacing:.035em;border-bottom:1px solid #edf1f6;white-space:nowrap}.dth-mkt-data-table td{padding:.62rem .5rem;color:#475467;border-bottom:1px solid #f1f4f7;vertical-align:middle}.dth-mkt-data-table tr:last-child td{border-bottom:0}.dth-mkt-data-table td strong{display:block;color:#253047;font-weight:750}.dth-mkt-data-table td small{display:block;margin-top:.1rem;color:#98a2b3}.dth-mkt-data-table .num{text-align:right;white-space:nowrap}.dth-mkt-data-table .accent{color:#4e50dd;font-weight:800}.dth-mkt-data-table .empty{text-align:center;color:#98a2b3;padding:1.8rem .5rem}.dth-mkt-status-pill{display:inline-flex;padding:.22rem .5rem;border-radius:999px;background:#f1f2ff;color:#5355db;font-size:.64rem;font-weight:750}
    .dth-mkt-insight-list{display:flex;flex-direction:column;gap:8px}.dth-mkt-insight{display:grid;grid-template-columns:8px minmax(0,1fr);gap:9px;padding:9px 10px;border:1px solid #edf0f5;border-radius:11px;background:#fbfcfe}.dth-mkt-insight>span{width:8px;height:8px;margin-top:3px;border-radius:999px;background:#64748b}.dth-mkt-insight[data-level=success]>span{background:#12b76a}.dth-mkt-insight[data-level=warning]>span{background:#f59e0b}.dth-mkt-insight[data-level=danger]>span{background:#ef4444}.dth-mkt-insight[data-level=info]>span{background:#3b82f6}.dth-mkt-insight strong{display:block;color:#273143;font-size:.72rem}.dth-mkt-insight p{margin:.15rem 0 0;color:#7e899b;font-size:.64rem;line-height:1.35}.dth-mkt-empty{padding:1.5rem;text-align:center;color:#98a2b3;font-size:.75rem}
    .dth-mkt-health-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px}.dth-mkt-health-item{display:grid;grid-template-columns:9px minmax(0,1fr) auto;align-items:center;gap:8px;padding:11px 12px;border:1px solid #edf0f5;border-radius:12px;background:#fbfcfe}.dth-mkt-health-dot{width:9px;height:9px;border-radius:999px;background:#c0c7d2}.dth-mkt-health-item[data-state=ok] .dth-mkt-health-dot{background:#12b76a}.dth-mkt-health-item strong{display:block;color:#344054;font-size:.7rem}.dth-mkt-health-item small{display:block;margin-top:.1rem;color:#98a2b3;font-size:.61rem}.dth-mkt-health-item b{color:#667085;font-size:.76rem}
    @media(max-width:1180px){.dth-mkt-kpi-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.dth-mkt-dashboard-grid--3{grid-template-columns:1fr 1fr}.dth-mkt-dashboard-card--insights{grid-column:1/-1}.dth-mkt-health-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:760px){.dth-mkt-kpi-grid,.dth-mkt-dashboard-grid--hero,.dth-mkt-dashboard-grid--3,.dth-mkt-dashboard-grid--2{grid-template-columns:1fr}.dth-mkt-health-grid{grid-template-columns:1fr 1fr}.dth-mkt-card-head{flex-wrap:wrap}.dth-mkt-line-chart{height:220px}.dth-mkt-line-chart svg{height:180px}}
</style>
