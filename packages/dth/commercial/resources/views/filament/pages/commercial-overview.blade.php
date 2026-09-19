@php
    $trend = collect($snapshot['monthly_pipeline'] ?? []);
    $trendValues = $trend->pluck('value')->map(fn ($value) => (float) $value);
    $trendMax = max(1, (float) ($trendValues->max() ?? 0));
    $chartWidth = 620;
    $chartHeight = 210;
    $chartPaddingX = 28;
    $chartPaddingTop = 18;
    $chartPaddingBottom = 38;
    $chartInnerWidth = $chartWidth - ($chartPaddingX * 2);
    $chartInnerHeight = $chartHeight - $chartPaddingTop - $chartPaddingBottom;
    $trendCount = max(1, $trend->count() - 1);
    $points = $trend->values()->map(function ($item, $index) use ($trendMax, $chartPaddingX, $chartPaddingTop, $chartInnerWidth, $chartInnerHeight, $trendCount) {
        $x = $chartPaddingX + (($index / $trendCount) * $chartInnerWidth);
        $y = $chartPaddingTop + $chartInnerHeight - (((float) $item['value'] / $trendMax) * $chartInnerHeight);

        return ['x' => $x, 'y' => $y, 'item' => $item];
    });
    $polyline = $points->map(fn ($point) => number_format($point['x'], 1, '.', '').','.number_format($point['y'], 1, '.', ''))->implode(' ');
    $area = $points->isNotEmpty()
        ? $chartPaddingX.','.($chartPaddingTop + $chartInnerHeight).' '.$polyline.' '.($chartPaddingX + $chartInnerWidth).','.($chartPaddingTop + $chartInnerHeight)
        : '';
    $topMax = max(1, (float) collect($snapshot['top_services'] ?? [])->max('value'));
    $stageTotal = max(1, (int) collect($snapshot['stage_breakdown'] ?? [])->sum('count'));
@endphp

<div class="dth-commercial-overview">
    <section class="dth-com-dashboard-kpis">
        <article class="dth-com-kpi" data-tone="blue">
            <div class="dth-com-kpi__icon"><x-filament::icon icon="heroicon-o-briefcase" /></div>
            <div class="dth-com-kpi__body">
                <span>{{ \Dth\Commercial\Support\UiText::get('overview.open_opportunities', 'Open opportunities') }}</span>
                <strong>{{ number_format((int) ($snapshot['open_opportunities'] ?? 0)) }}</strong>
                <small>{{ \Dth\Commercial\Support\UiText::get('overview.open_opportunities_meta', 'Currently moving through the active pipeline') }}</small>
            </div>
        </article>

        <article class="dth-com-kpi" data-tone="teal">
            <div class="dth-com-kpi__icon"><x-filament::icon icon="heroicon-o-banknotes" /></div>
            <div class="dth-com-kpi__body">
                <span>{{ \Dth\Commercial\Support\UiText::get('overview.weighted_pipeline', 'Weighted pipeline') }}</span>
                <strong>{{ number_format((float) ($snapshot['weighted_pipeline_value'] ?? 0), 0, ',', '.') }} ₫</strong>
                <small>{{ \Dth\Commercial\Support\UiText::get('overview.pipeline_gross_meta', 'Gross pipeline: :value', ['value' => number_format((float) ($snapshot['pipeline_value'] ?? 0), 0, ',', '.').' ₫']) }}</small>
            </div>
        </article>

        <article class="dth-com-kpi" data-tone="green">
            <div class="dth-com-kpi__icon"><x-filament::icon icon="heroicon-o-trophy" /></div>
            <div class="dth-com-kpi__body">
                <span>{{ \Dth\Commercial\Support\UiText::get('overview.win_rate', 'Win rate') }}</span>
                <strong>{{ number_format((float) ($snapshot['win_rate'] ?? 0), 1, ',', '.') }}%</strong>
                <small>{{ \Dth\Commercial\Support\UiText::get('overview.win_rate_meta', 'Calculated from opportunities with a final outcome') }}</small>
            </div>
        </article>

        <article class="dth-com-kpi" data-tone="amber">
            <div class="dth-com-kpi__icon"><x-filament::icon icon="heroicon-o-calendar-days" /></div>
            <div class="dth-com-kpi__body">
                <span>{{ \Dth\Commercial\Support\UiText::get('overview.closing_this_month', 'Expected to close this month') }}</span>
                <strong>{{ number_format((int) ($snapshot['closing_this_month_count'] ?? 0)) }}</strong>
                <small>{{ number_format((float) ($snapshot['closing_this_month_value'] ?? 0), 0, ',', '.') }} ₫</small>
            </div>
        </article>
    </section>

    <section class="dth-com-dashboard-grid">
        <article class="dth-com-dashboard-card dth-com-dashboard-card--funnel">
            <header class="dth-com-card-heading">
                <div>
                    <span class="dth-com-card-kicker">{{ \Dth\Commercial\Support\UiText::get('overview.kicker_pipeline', 'PIPELINE') }}</span>
                    <h3>{{ \Dth\Commercial\Support\UiText::get('overview.stage_breakdown', 'Opportunities by stage') }}</h3>
                </div>
                <span class="dth-com-card-heading__meta">{{ number_format($stageTotal) }} {{ \Dth\Commercial\Support\UiText::get('overview.opportunities_short', 'opportunities') }}</span>
            </header>

            <div class="dth-com-funnel">
                @foreach (($snapshot['stage_breakdown'] ?? []) as $index => $stage)
                    @php
                        $width = max(38, 100 - ($index * 9));
                    @endphp
                    <div class="dth-com-funnel__row" data-tone="{{ $stage['tone'] }}">
                        <div class="dth-com-funnel__shape-wrap">
                            <div class="dth-com-funnel__shape" style="width: {{ $width }}%"></div>
                        </div>
                        <div class="dth-com-funnel__label">
                            <span title="{{ $stage['label'] }}">{{ $stage['label'] }}</span>
                            <strong>{{ number_format((int) $stage['count']) }}</strong>
                            <small>{{ number_format((float) $stage['percentage']) }}%</small>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="dth-com-dashboard-card dth-com-dashboard-card--trend">
            <header class="dth-com-card-heading">
                <div>
                    <span class="dth-com-card-kicker">{{ \Dth\Commercial\Support\UiText::get('overview.kicker_trend', 'TREND') }}</span>
                    <h3>{{ \Dth\Commercial\Support\UiText::get('overview.pipeline_trend', 'Pipeline by expected close month') }}</h3>
                </div>
                <span class="dth-com-card-heading__meta">{{ \Dth\Commercial\Support\UiText::get('overview.next_six_months', 'Next 6 months') }}</span>
            </header>

            <div class="dth-com-trend-chart">
                <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" role="img" aria-label="{{ \Dth\Commercial\Support\UiText::get('overview.pipeline_trend', 'Pipeline by expected close month') }}">
                    <defs>
                        <linearGradient id="dthCommercialArea" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#0f8f95" stop-opacity=".20" />
                            <stop offset="100%" stop-color="#0f8f95" stop-opacity="0" />
                        </linearGradient>
                    </defs>
                    @foreach ([0, .25, .5, .75, 1] as $ratio)
                        @php $gridY = $chartPaddingTop + ($chartInnerHeight * $ratio); @endphp
                        <line class="dth-com-chart-gridline" x1="{{ $chartPaddingX }}" y1="{{ $gridY }}" x2="{{ $chartPaddingX + $chartInnerWidth }}" y2="{{ $gridY }}" />
                    @endforeach
                    @if ($area)
                        <polygon class="dth-com-chart-area" points="{{ $area }}" />
                        <polyline class="dth-com-chart-line" points="{{ $polyline }}" />
                    @endif
                    @foreach ($points as $point)
                        <circle class="dth-com-chart-dot" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4.5" />
                        <text class="dth-com-chart-label" x="{{ $point['x'] }}" y="{{ $chartPaddingTop + $chartInnerHeight + 26 }}" text-anchor="middle">{{ $point['item']['label'] }}</text>
                    @endforeach
                </svg>
            </div>

            <div class="dth-com-trend-summary">
                <div>
                    <span>{{ \Dth\Commercial\Support\UiText::get('overview.pipeline_value', 'Pipeline value') }}</span>
                    <strong>{{ number_format((float) ($snapshot['pipeline_value'] ?? 0), 0, ',', '.') }} ₫</strong>
                </div>
                <div>
                    <span>{{ \Dth\Commercial\Support\UiText::get('overview.weighted_pipeline', 'Weighted pipeline') }}</span>
                    <strong>{{ number_format((float) ($snapshot['weighted_pipeline_value'] ?? 0), 0, ',', '.') }} ₫</strong>
                </div>
            </div>
        </article>

        <article class="dth-com-dashboard-card dth-com-dashboard-card--services">
            <header class="dth-com-card-heading">
                <div>
                    <span class="dth-com-card-kicker">{{ \Dth\Commercial\Support\UiText::get('overview.kicker_catalog', 'CATALOG') }}</span>
                    <h3>{{ \Dth\Commercial\Support\UiText::get('overview.catalog_performance', 'Catalog performance') }}</h3>
                </div>
                @if ($features['catalog'] ?? false)
                    <a href="{{ \Dth\Commercial\Filament\Resources\ServiceResource::getUrl('index') }}" class="dth-com-card-link">
                        {{ \Dth\Commercial\Support\UiText::get('overview.view_catalog', 'View catalog') }}
                        <x-filament::icon icon="heroicon-o-arrow-right" />
                    </a>
                @endif
            </header>

            <div class="dth-com-catalog-metrics">
                <div>
                    <span class="dth-com-catalog-metric__icon"><x-filament::icon icon="heroicon-o-rectangle-stack" /></span>
                    <span>{{ \Dth\Commercial\Support\UiText::get('overview.active_services', 'Active services') }}</span>
                    <strong>{{ number_format((int) ($snapshot['active_services'] ?? 0)) }}</strong>
                </div>
                <div>
                    <span class="dth-com-catalog-metric__icon"><x-filament::icon icon="heroicon-o-cube" /></span>
                    <span>{{ \Dth\Commercial\Support\UiText::get('overview.active_products', 'Active products') }}</span>
                    <strong>{{ number_format((int) ($snapshot['active_products'] ?? 0)) }}</strong>
                </div>
                <div>
                    <span class="dth-com-catalog-metric__icon"><x-filament::icon icon="heroicon-o-gift" /></span>
                    <span>{{ \Dth\Commercial\Support\UiText::get('overview.active_bundles', 'Active bundles') }}</span>
                    <strong>{{ number_format((int) ($snapshot['active_bundles'] ?? ($snapshot['active_packages'] ?? 0))) }}</strong>
                </div>
            </div>

            <div class="dth-com-top-services">
                @forelse (($snapshot['top_services'] ?? []) as $index => $service)
                    <div class="dth-com-service-rank">
                        <span class="dth-com-service-rank__number">{{ $index + 1 }}</span>
                        <div class="dth-com-service-rank__body">
                            <div>
                                <strong title="{{ $service['name'] }}">{{ $service['name'] }}</strong>
                                <span>{{ number_format((float) $service['value'], 0, ',', '.') }} ₫</span>
                            </div>
                            <div class="dth-com-service-rank__track"><span style="width: {{ max(4, (((float) $service['value']) / $topMax) * 100) }}%"></span></div>
                            <small>{{ number_format((int) $service['count']) }} {{ \Dth\Commercial\Support\UiText::get('overview.opportunities_short', 'opportunities') }}</small>
                        </div>
                    </div>
                @empty
                    <div class="dth-com-empty-state">{{ \Dth\Commercial\Support\UiText::get('overview.no_service_data', 'No service-linked opportunity data yet.') }}</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="dth-com-dashboard-card dth-com-recent">
        <header class="dth-com-card-heading">
            <div>
                <span class="dth-com-card-kicker">{{ \Dth\Commercial\Support\UiText::get('overview.kicker_recent', 'RECENT') }}</span>
                <h3>{{ \Dth\Commercial\Support\UiText::get('overview.recent_opportunities', 'Recent business opportunities') }}</h3>
            </div>
            @if ($features['opportunities'] ?? false)
                <a href="{{ \Dth\Commercial\Filament\Resources\OpportunityResource::getUrl('index') }}" class="dth-com-card-link">
                    {{ \Dth\Commercial\Support\UiText::get('overview.view_all', 'View all') }}
                    <x-filament::icon icon="heroicon-o-arrow-right" />
                </a>
            @endif
        </header>

        <div class="dth-com-recent__table-wrap">
            <table class="dth-com-recent__table">
                <thead>
                    <tr>
                        <th>{{ \Dth\Commercial\Support\UiText::get('fields.opportunity_code', 'Opportunity code') }}</th>
                        <th>{{ \Dth\Commercial\Support\UiText::get('common.fields.name', 'Name') }}</th>
                        <th>{{ \Dth\Commercial\Support\UiText::get('fields.service', 'Service') }}</th>
                        <th>{{ \Dth\Commercial\Support\UiText::get('fields.stage', 'Stage') }}</th>
                        <th>{{ \Dth\Commercial\Support\UiText::get('fields.estimated_value', 'Estimated value') }}</th>
                        <th>{{ \Dth\Commercial\Support\UiText::get('fields.probability', 'Probability') }}</th>
                        <th>{{ \Dth\Commercial\Support\UiText::get('fields.expected_close_date', 'Expected close date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($snapshot['recent_opportunities'] ?? []) as $opportunity)
                        @php
                            $stage = $opportunity->stage instanceof \Dth\Commercial\Enums\OpportunityStage
                                ? $opportunity->stage
                                : \Dth\Commercial\Enums\OpportunityStage::tryFrom((string) $opportunity->stage);
                        @endphp
                        <tr>
                            <td><a href="{{ \Dth\Commercial\Filament\Resources\OpportunityResource::getUrl('edit', ['record' => $opportunity]) }}">{{ $opportunity->opportunity_code }}</a></td>
                            <td>
                                <strong>{{ $opportunity->title }}</strong>
                                <small>{{ $opportunity->company_name_snapshot ?: $opportunity->contact_name_snapshot }}</small>
                            </td>
                            <td>{{ $opportunity->service_name_snapshot ?: '—' }}</td>
                            <td><span class="dth-com-stage-badge" data-stage="{{ $stage?->value ?? 'unknown' }}">{{ $stage?->label() ?? (string) $opportunity->stage }}</span></td>
                            <td>{{ number_format((float) $opportunity->estimated_value, 0, ',', '.') }} ₫</td>
                            <td>{{ (int) $opportunity->probability }}%</td>
                            <td>{{ $opportunity->expected_close_date?->format('d/m/Y') ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="dth-com-empty-state">{{ \Dth\Commercial\Support\UiText::get('overview.no_recent_opportunities', 'No recent opportunities yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
