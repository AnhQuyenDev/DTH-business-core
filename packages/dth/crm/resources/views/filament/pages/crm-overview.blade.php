@php
    use Dth\Crm\Filament\Resources\CompanyResource;
    use Dth\Crm\Filament\Resources\ContactQualificationResource;
    use Dth\Crm\Filament\Resources\ContactResource;
    use Dth\Crm\Filament\Resources\CustomerResource;
    use Dth\Crm\Filament\Resources\LeadResource;
    use Dth\Crm\Support\CrmOptions;
    use Dth\Crm\Support\StatusColor;
    use Dth\Crm\Support\UiText;

    $m = $snapshot['metrics'] ?? [];
    $pipeline = $snapshot['pipeline'] ?? [];
    $maxPipeline = max(1, collect($pipeline)->max('value') ?? 1);
    $number = static fn ($value) => number_format((int) $value, 0, ',', '.');
    $money = static fn ($value) => number_format((float) $value, 0, ',', '.').' ₫';
    $badgeClass = static fn ($state, ?string $context = null) => 'dth-crm-badge--'.StatusColor::for($state, $context);
@endphp

<style>
    .dth-crm-shell{display:flex;flex-direction:column;gap:1.5rem}
    .dth-crm-kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:1rem}
    .dth-crm-kpi{position:relative;overflow:hidden;border:1px solid rgb(229 231 235);border-radius:16px;padding:1rem 1.1rem;background:rgb(255 255 255);box-shadow:0 1px 3px rgba(15,23,42,.05);transition:transform .15s ease,box-shadow .15s ease}
    .dth-crm-kpi:hover{transform:translateY(-1px);box-shadow:0 8px 20px rgba(15,23,42,.07)}
    .dark .dth-crm-kpi{background:rgb(17 24 39);border-color:rgb(55 65 81)}
    .dth-crm-kpi-top{display:flex;align-items:center;justify-content:space-between;gap:.75rem}
    .dth-crm-kpi-label{font-size:.76rem;font-weight:750;text-transform:uppercase;letter-spacing:.045em;color:rgb(107 114 128)}
    .dth-crm-kpi-value{font-size:1.7rem;font-weight:800;line-height:1.2;margin-top:.35rem;color:rgb(17 24 39)}
    .dark .dth-crm-kpi-value{color:white}
    .dth-crm-kpi-help{font-size:.78rem;color:rgb(107 114 128);margin-top:.35rem;min-height:1.15rem}
    .dth-crm-kpi-icon{display:flex;align-items:center;justify-content:center;width:2.35rem;height:2.35rem;border-radius:11px;background:rgb(239 246 255);color:rgb(37 99 235);flex:0 0 auto}
    .dark .dth-crm-kpi-icon{background:rgba(59,130,246,.14);color:rgb(147 197 253)}
    .dth-crm-grid2{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1.25rem}
    .dth-crm-pipeline{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.75rem}
    .dth-crm-pipeline-step{border:1px solid rgb(229 231 235);background:rgb(249 250 251);border-radius:13px;padding:.9rem;min-width:0}
    .dark .dth-crm-pipeline-step{background:rgb(31 41 55);border-color:rgb(55 65 81)}
    .dth-crm-pipeline-label{font-size:.78rem;color:rgb(107 114 128);font-weight:650}
    .dth-crm-pipeline-value{font-size:1.45rem;font-weight:800;margin:.25rem 0 .6rem}
    .dth-crm-progress{height:.38rem;border-radius:999px;background:rgb(229 231 235);overflow:hidden}.dark .dth-crm-progress{background:rgb(55 65 81)}
    .dth-crm-progress>span{display:block;height:100%;border-radius:inherit;background:rgb(59 130 246)}
    .dth-crm-status-list{display:flex;flex-direction:column;gap:.65rem}
    .dth-crm-status-row{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.55rem .15rem;border-bottom:1px solid rgb(243 244 246)}
    .dark .dth-crm-status-row{border-color:rgb(55 65 81)}
    .dth-crm-status-row:last-child{border-bottom:0}
    .dth-crm-badge{display:inline-flex;align-items:center;border-radius:999px;padding:.22rem .58rem;font-size:.75rem;font-weight:700;white-space:nowrap}
    .dth-crm-badge--success{background:rgb(220 252 231);color:rgb(21 128 61)}.dark .dth-crm-badge--success{background:rgba(34,197,94,.14);color:rgb(134 239 172)}
    .dth-crm-badge--info{background:rgb(219 234 254);color:rgb(29 78 216)}.dark .dth-crm-badge--info{background:rgba(59,130,246,.14);color:rgb(147 197 253)}
    .dth-crm-badge--warning{background:rgb(254 243 199);color:rgb(180 83 9)}.dark .dth-crm-badge--warning{background:rgba(245,158,11,.14);color:rgb(253 230 138)}
    .dth-crm-badge--danger{background:rgb(254 226 226);color:rgb(185 28 28)}.dark .dth-crm-badge--danger{background:rgba(239,68,68,.14);color:rgb(252 165 165)}
    .dth-crm-badge--gray{background:rgb(243 244 246);color:rgb(75 85 99)}.dark .dth-crm-badge--gray{background:rgb(55 65 81);color:rgb(209 213 219)}
    .dth-crm-table-wrap{overflow-x:auto}
    .dth-crm-table{width:100%;border-collapse:collapse;font-size:.86rem}
    .dth-crm-table th{font-size:.71rem;text-transform:uppercase;letter-spacing:.04em;color:rgb(107 114 128);text-align:left;padding:.65rem .7rem;border-bottom:1px solid rgb(229 231 235);white-space:nowrap}
    .dth-crm-table td{padding:.72rem .7rem;border-bottom:1px solid rgb(243 244 246);vertical-align:middle;white-space:nowrap}
    .dark .dth-crm-table th,.dark .dth-crm-table td{border-color:rgb(55 65 81)}
    .dth-crm-table tr:last-child td{border-bottom:0}
    .dth-crm-muted{color:rgb(107 114 128);font-size:.78rem}
    .dth-crm-workload{display:flex;flex-direction:column;gap:.9rem}
    .dth-crm-workload-head{display:flex;justify-content:space-between;align-items:center;gap:1rem;margin-bottom:.38rem}
    .dth-crm-workload-name{font-size:.86rem;font-weight:700;color:rgb(31 41 55)}.dark .dth-crm-workload-name{color:rgb(243 244 246)}
    .dth-crm-flow{display:flex;gap:.55rem;align-items:center}
    .dth-crm-flow-step{position:relative;flex:1 1 0;min-width:0;text-align:center;padding:.85rem .55rem;border:1px solid rgb(229 231 235);border-radius:12px;background:rgb(249 250 251);font-size:.78rem;font-weight:700;color:rgb(55 65 81)}
    .dark .dth-crm-flow-step{background:rgb(31 41 55);border-color:rgb(55 65 81);color:rgb(229 231 235)}
    .dth-crm-flow-arrow{flex:0 0 auto;text-align:center;color:rgb(156 163 175);font-weight:800}
    .dth-crm-empty{padding:1.2rem;text-align:center;color:rgb(107 114 128);font-size:.86rem}
    @media(max-width:1100px){.dth-crm-kpis{grid-template-columns:repeat(2,minmax(0,1fr))}.dth-crm-pipeline{grid-template-columns:repeat(2,minmax(0,1fr))}.dth-crm-flow{display:grid;grid-template-columns:repeat(3,minmax(0,1fr))}.dth-crm-flow-arrow{display:none}}
    @media(max-width:760px){.dth-crm-kpis,.dth-crm-grid2,.dth-crm-pipeline{grid-template-columns:1fr}.dth-crm-flow{grid-template-columns:1fr}}
</style>

<x-filament-panels::page>
    <div class="dth-crm-shell">
        <div class="dth-crm-kpis">
            <a href="{{ ContactResource::getUrl('index') }}" class="dth-crm-kpi">
                <div class="dth-crm-kpi-top"><div><div class="dth-crm-kpi-label">{{ UiText::get('dashboard.metrics.contacts', 'Contacts') }}</div><div class="dth-crm-kpi-value">{{ $number($m['contacts'] ?? 0) }}</div></div><div class="dth-crm-kpi-icon"><x-filament::icon icon="heroicon-o-users" class="h-5 w-5" /></div></div>
                <div class="dth-crm-kpi-help">{{ UiText::get('dashboard.help.contact_mix', ':personal personal · :business business', ['personal' => $number($m['personal_contacts'] ?? 0), 'business' => $number($m['business_contacts'] ?? 0)]) }}</div>
            </a>
            <a href="{{ CompanyResource::getUrl('index') }}" class="dth-crm-kpi">
                <div class="dth-crm-kpi-top"><div><div class="dth-crm-kpi-label">{{ UiText::get('dashboard.metrics.companies', 'Companies') }}</div><div class="dth-crm-kpi-value">{{ $number($m['companies'] ?? 0) }}</div></div><div class="dth-crm-kpi-icon"><x-filament::icon icon="heroicon-o-building-office-2" class="h-5 w-5" /></div></div>
                <div class="dth-crm-kpi-help">{{ UiText::get('dashboard.help.company_master', 'Business profiles linked across the CRM flow') }}</div>
            </a>
            <a href="{{ LeadResource::getUrl('index') }}" class="dth-crm-kpi">
                <div class="dth-crm-kpi-top"><div><div class="dth-crm-kpi-label">{{ UiText::get('dashboard.metrics.leads', 'Leads') }}</div><div class="dth-crm-kpi-value">{{ $number($m['leads'] ?? 0) }}</div></div><div class="dth-crm-kpi-icon"><x-filament::icon icon="heroicon-o-funnel" class="h-5 w-5" /></div></div>
                <div class="dth-crm-kpi-help">{{ UiText::get('dashboard.help.lead_mix', ':new new · :active in progress', ['new' => $number($m['new_leads'] ?? 0), 'active' => $number($m['active_leads'] ?? 0)]) }}</div>
            </a>
            <a href="{{ ContactQualificationResource::getUrl('index') }}" class="dth-crm-kpi">
                <div class="dth-crm-kpi-top"><div><div class="dth-crm-kpi-label">{{ UiText::get('dashboard.metrics.qualified', 'Qualified') }}</div><div class="dth-crm-kpi-value">{{ $number($m['qualified'] ?? 0) }}</div></div><div class="dth-crm-kpi-icon"><x-filament::icon icon="heroicon-o-clipboard-document-check" class="h-5 w-5" /></div></div>
                <div class="dth-crm-kpi-help">{{ UiText::get('dashboard.help.followups_due', ':count follow-ups due', ['count' => $number($m['follow_ups_due'] ?? 0)]) }}</div>
            </a>
            <a href="{{ CustomerResource::getUrl('index') }}" class="dth-crm-kpi">
                <div class="dth-crm-kpi-top"><div><div class="dth-crm-kpi-label">{{ UiText::get('dashboard.metrics.customers', 'Customers') }}</div><div class="dth-crm-kpi-value">{{ $number($m['customers'] ?? 0) }}</div></div><div class="dth-crm-kpi-icon"><x-filament::icon icon="heroicon-o-user-group" class="h-5 w-5" /></div></div>
                <div class="dth-crm-kpi-help">{{ UiText::get('dashboard.help.active_customers', ':count active customers', ['count' => $number($m['active_customers'] ?? 0)]) }}</div>
            </a>
            <div class="dth-crm-kpi">
                <div class="dth-crm-kpi-top"><div><div class="dth-crm-kpi-label">{{ UiText::get('dashboard.metrics.revenue', 'Customer revenue') }}</div><div class="dth-crm-kpi-value">{{ $money($m['total_revenue'] ?? 0) }}</div></div><div class="dth-crm-kpi-icon"><x-filament::icon icon="heroicon-o-banknotes" class="h-5 w-5" /></div></div>
                <div class="dth-crm-kpi-help">{{ UiText::get('dashboard.help.recorded_revenue', 'Revenue recorded on customer profiles') }}</div>
            </div>
            <div class="dth-crm-kpi">
                <div class="dth-crm-kpi-top"><div><div class="dth-crm-kpi-label">{{ UiText::get('dashboard.metrics.conversion_rate', 'Lead → customer') }}</div><div class="dth-crm-kpi-value">{{ number_format((float)($m['lead_to_customer_rate'] ?? 0), 1) }}%</div></div><div class="dth-crm-kpi-icon"><x-filament::icon icon="heroicon-o-arrow-trending-up" class="h-5 w-5" /></div></div>
                <div class="dth-crm-kpi-help">{{ UiText::get('dashboard.help.conversion_rate', 'Customers linked back to an origin Lead') }}</div>
            </div>
            <div class="dth-crm-kpi">
                <div class="dth-crm-kpi-top"><div><div class="dth-crm-kpi-label">{{ UiText::get('dashboard.metrics.attention', 'Needs attention') }}</div><div class="dth-crm-kpi-value">{{ $number(($m['unassigned_leads'] ?? 0) + ($m['pending_matches'] ?? 0)) }}</div></div><div class="dth-crm-kpi-icon"><x-filament::icon icon="heroicon-o-bell-alert" class="h-5 w-5" /></div></div>
                <div class="dth-crm-kpi-help">{{ UiText::get('dashboard.help.attention', ':leads unassigned Leads · :matches company matches', ['leads' => $number($m['unassigned_leads'] ?? 0), 'matches' => $number($m['pending_matches'] ?? 0)]) }}</div>
            </div>
        </div>

        <x-filament::section :heading="UiText::get('dashboard.sections.pipeline', 'CRM conversion pipeline')" icon="heroicon-o-chart-bar-square">
            <div class="dth-crm-pipeline">
                @foreach($pipeline as $step)
                    @php
                        $label = match($step['key']) {
                            'contacts' => UiText::get('dashboard.pipeline.contacts', 'Contacts'),
                            'leads' => UiText::get('dashboard.pipeline.leads', 'Leads'),
                            'qualified' => UiText::get('dashboard.pipeline.qualified', 'Qualified'),
                            'customers' => UiText::get('dashboard.pipeline.customers', 'Customers'),
                            default => $step['key'],
                        };
                        $width = max(3, round(((int)$step['value'] / $maxPipeline) * 100));
                    @endphp
                    <div class="dth-crm-pipeline-step">
                        <div class="dth-crm-pipeline-label">{{ $label }}</div>
                        <div class="dth-crm-pipeline-value">{{ $number($step['value']) }}</div>
                        <div class="dth-crm-progress"><span style="width:{{ $width }}%"></span></div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <div class="dth-crm-grid2">
            <x-filament::section :heading="UiText::get('dashboard.sections.lead_status', 'Lead status')" icon="heroicon-o-funnel">
                <div class="dth-crm-status-list">
                    @forelse(CrmOptions::leadStatuses() as $value => $label)
                        @php $count = (int)($snapshot['lead_statuses'][$value] ?? 0); @endphp
                        <div class="dth-crm-status-row"><span class="dth-crm-badge {{ $badgeClass($value, 'lead_status') }}">{{ $label }}</span><strong>{{ $number($count) }}</strong></div>
                    @empty
                        <div class="dth-crm-empty">{{ UiText::get('dashboard.empty.no_status_data', 'No status data yet.') }}</div>
                    @endforelse
                </div>
            </x-filament::section>

            <x-filament::section :heading="UiText::get('dashboard.sections.qualification_status', 'Qualification status')" icon="heroicon-o-clipboard-document-check">
                <div class="dth-crm-status-list">
                    @foreach(CrmOptions::qualificationStatuses() as $value => $label)
                        @php $count = (int)($snapshot['qualification_statuses'][$value] ?? 0); @endphp
                        <div class="dth-crm-status-row"><span class="dth-crm-badge {{ $badgeClass($value, 'qualification_status') }}">{{ $label }}</span><strong>{{ $number($count) }}</strong></div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>

        <div class="dth-crm-grid2">
            <x-filament::section :heading="UiText::get('dashboard.sections.recent_leads', 'Recent Leads')" icon="heroicon-o-clock">
                <div class="dth-crm-table-wrap">
                    <table class="dth-crm-table">
                        <thead><tr><th>{{ UiText::get('fields.lead_code', 'Lead code') }}</th><th>{{ UiText::get('fields.contact_or_company', 'Contact / company') }}</th><th>{{ UiText::get('common.fields.status', 'Status') }}</th><th>{{ UiText::get('fields.owner', 'Owner') }}</th></tr></thead>
                        <tbody>
                        @forelse($snapshot['recent_leads'] ?? [] as $lead)
                            <tr>
                                <td><a class="font-semibold text-primary-600 dark:text-primary-400" href="{{ LeadResource::getUrl('view', ['record' => $lead['id']]) }}">{{ $lead['code'] }}</a><div class="dth-crm-muted">{{ optional($lead['created_at'])->format('d/m/Y H:i') }}</div></td>
                                <td>{{ $lead['name'] }}<div class="dth-crm-muted">{{ $lead['service'] }}</div></td>
                                <td><span class="dth-crm-badge {{ $badgeClass($lead['status'], 'lead_status') }}">{{ CrmOptions::label('lead_status', $lead['status']) }}</span></td>
                                <td>{{ $lead['owner'] ?: UiText::get('fields.unassigned', 'Unassigned') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="dth-crm-empty">{{ UiText::get('dashboard.empty.no_recent_leads', 'No Leads yet.') }}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </x-filament::section>

            <x-filament::section :heading="UiText::get('dashboard.sections.team_workload', 'CRM team workload')" icon="heroicon-o-identification">
                <div class="dth-crm-workload">
                    @forelse($snapshot['agent_workload'] ?? [] as $agent)
                        <div>
                            <div class="dth-crm-workload-head"><span class="dth-crm-workload-name">{{ $agent['name'] }}</span><span class="dth-crm-muted">{{ UiText::get('dashboard.help.lead_capacity', ':open / :capacity Leads', ['open' => $agent['open_leads'], 'capacity' => $agent['capacity']]) }}</span></div>
                            <div class="dth-crm-progress"><span style="width:{{ max(2, $agent['load_percent']) }}%"></span></div>
                        </div>
                    @empty
                        <div class="dth-crm-empty">{{ UiText::get('dashboard.empty.no_agents', 'No active CRM assignees yet.') }}</div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        <x-filament::section :heading="UiText::get('dashboard.sections.business_flow', 'CRM business flow')" icon="heroicon-o-arrows-right-left">
            <div class="dth-crm-flow">
                <div class="dth-crm-flow-step">{{ UiText::get('dashboard.flow.contact', 'Contact') }}</div><div class="dth-crm-flow-arrow">→</div>
                <div class="dth-crm-flow-step">{{ UiText::get('dashboard.flow.company_lead', 'Company / Lead') }}</div><div class="dth-crm-flow-arrow">→</div>
                <div class="dth-crm-flow-step">{{ UiText::get('dashboard.flow.qualification', 'Qualification') }}</div><div class="dth-crm-flow-arrow">→</div>
                <div class="dth-crm-flow-step">{{ UiText::get('dashboard.flow.sales_handoff', 'Sales handoff') }}</div><div class="dth-crm-flow-arrow">→</div>
                <div class="dth-crm-flow-step">{{ UiText::get('dashboard.flow.customer', 'Customer') }}</div><div class="dth-crm-flow-arrow">→</div>
                <div class="dth-crm-flow-step">{{ UiText::get('dashboard.flow.care', 'Customer care') }}</div>
            </div>
            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ UiText::get('dashboard.flow.description', 'Data is carried through the CRM lifecycle instead of being recreated independently at each step, preserving continuity and traceability.') }}</p>
        </x-filament::section>
    </div>
</x-filament-panels::page>
