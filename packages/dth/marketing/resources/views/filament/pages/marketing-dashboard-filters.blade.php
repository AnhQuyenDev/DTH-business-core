@php
    $rangeLabel = $filter->start->format('d/m/Y').' - '.$filter->end->format('d/m/Y');
@endphp

<div class="dth-mkt-filter-card">
    <div class="dth-mkt-filter-card__header">
        <div class="dth-mkt-filter-card__title">
            <x-filament::icon icon="heroicon-o-adjustments-horizontal" />
            <span>{{ \Dth\Marketing\Support\UiText::get('analytics.filters.heading', 'Report filters') }}</span>
        </div>
        <a href="{{ $resetUrl }}" class="dth-mkt-filter-reset">
            <x-filament::icon icon="heroicon-o-arrow-path" />
            {{ \Dth\Marketing\Support\UiText::get('analytics.filters.reset', 'Reset') }}
        </a>
    </div>

    <form method="GET" action="{{ $actionUrl }}" class="dth-mkt-filter-form">
        <input type="hidden" name="period" value="custom">

        <div class="dth-mkt-filter-field dth-mkt-filter-field--period">
            <label>{{ \Dth\Marketing\Support\UiText::get('analytics.filters.period', 'Reporting period') }}</label>
            <div class="dth-mkt-period-control">
                <span>{{ $rangeLabel }}</span>
                <x-filament::icon icon="heroicon-o-calendar-days" />
            </div>
        </div>

        <div class="dth-mkt-filter-field">
            <label>{{ \Dth\Marketing\Support\UiText::get('analytics.filters.start', 'Start date') }}</label>
            <input type="date" name="start" value="{{ $state['start'] }}" max="{{ now()->toDateString() }}" required>
        </div>

        <div class="dth-mkt-filter-field">
            <label>{{ \Dth\Marketing\Support\UiText::get('analytics.filters.end', 'End date') }}</label>
            <input type="date" name="end" value="{{ $state['end'] }}" max="{{ now()->toDateString() }}" required>
        </div>

        <div class="dth-mkt-filter-field">
            <label>{{ \Dth\Marketing\Support\UiText::get('analytics.filters.campaign', 'Campaign') }}</label>
            <select name="campaign">
                <option value="">{{ \Dth\Marketing\Support\UiText::get('analytics.filters.all_campaigns', 'All campaigns') }}</option>
                @foreach($campaigns as $id => $name)
                    <option value="{{ $id }}" @selected((string)$state['campaign'] === (string)$id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>

        <div class="dth-mkt-filter-field">
            <label>{{ \Dth\Marketing\Support\UiText::get('analytics.filters.status', 'Campaign status') }}</label>
            <select name="status">
                <option value="">{{ \Dth\Marketing\Support\UiText::get('analytics.filters.all_statuses', 'All statuses') }}</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" @selected((string)$state['status'] === (string)$value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="dth-mkt-filter-field">
            <label>{{ \Dth\Marketing\Support\UiText::get('analytics.filters.source', 'Source') }}</label>
            <select name="source">
                <option value="">{{ \Dth\Marketing\Support\UiText::get('analytics.filters.all_sources', 'All sources') }}</option>
                @foreach($sources as $source)
                    <option value="{{ $source }}" @selected((string)$state['source'] === (string)$source)>{{ $source }}</option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="dth-mkt-filter-submit">
            <x-filament::icon icon="heroicon-o-funnel" />
            {{ \Dth\Marketing\Support\UiText::get('analytics.filters.apply', 'Apply filters') }}
        </button>
    </form>

    <div class="dth-mkt-filter-presets">
        @foreach($presets as $key => $preset)
            <a href="{{ $preset['url'] }}" @class(['is-active' => $state['period'] === $key])>{{ $preset['label'] }}</a>
        @endforeach
    </div>
</div>

<style>
    .dth-mkt-filter-card{overflow:hidden;border:1px solid var(--dth-mkt-border);border-radius:16px;background:#fff;box-shadow:var(--dth-mkt-shadow)}
    .dth-mkt-filter-card__header{display:flex;align-items:center;justify-content:space-between;gap:1rem;min-height:44px;padding:.65rem 1rem;border-bottom:1px solid #edf1f6}
    .dth-mkt-filter-card__title,.dth-mkt-filter-reset{display:inline-flex;align-items:center;gap:.45rem}.dth-mkt-filter-card__title{font-size:.88rem;font-weight:800;color:#253047}.dth-mkt-filter-card__title svg,.dth-mkt-filter-reset svg{width:17px;height:17px}.dth-mkt-filter-reset{font-size:.74rem;font-weight:650;color:#667085;text-decoration:none}
    .dth-mkt-filter-form{display:grid;grid-template-columns:1.18fr 1fr 1fr 1.15fr 1.15fr 1fr 150px;gap:.75rem;align-items:end;padding:.8rem 1rem .65rem}.dth-mkt-filter-field{min-width:0}.dth-mkt-filter-field label{display:block;margin-bottom:.32rem;font-size:.68rem;font-weight:700;color:#59667c}.dth-mkt-filter-field input,.dth-mkt-filter-field select,.dth-mkt-period-control{width:100%;height:39px;border:1px solid #dce3ed;border-radius:10px;background:#fff;padding:0 .72rem;color:#344054;font-size:.78rem;outline:none}.dth-mkt-filter-field input:focus,.dth-mkt-filter-field select:focus{border-color:#8b8cf7;box-shadow:0 0 0 3px rgba(91,92,240,.1)}.dth-mkt-period-control{display:flex;align-items:center;justify-content:space-between;gap:.45rem}.dth-mkt-period-control svg{width:16px;height:16px;color:#7a8699}.dth-mkt-filter-submit{height:39px;display:inline-flex;align-items:center;justify-content:center;gap:.4rem;border:0;border-radius:10px;background:linear-gradient(180deg,#6869f5,#5557e8);color:#fff;font-size:.76rem;font-weight:760;box-shadow:0 8px 18px rgba(91,92,240,.18);cursor:pointer}.dth-mkt-filter-submit svg{width:16px;height:16px}
    .dth-mkt-filter-presets{display:flex;align-items:center;gap:.45rem;flex-wrap:wrap;padding:.1rem 1rem .85rem}.dth-mkt-filter-presets a{padding:.36rem .7rem;border:1px solid #e4e8ef;border-radius:999px;background:#fff;color:#667085;font-size:.71rem;font-weight:700;text-decoration:none}.dth-mkt-filter-presets a.is-active{border-color:#cfd1ff;background:#eff0ff;color:#4f50df}
    @media(max-width:1280px){.dth-mkt-filter-form{grid-template-columns:repeat(3,minmax(0,1fr))}.dth-mkt-filter-submit{align-self:end}}@media(max-width:760px){.dth-mkt-filter-form{grid-template-columns:1fr}.dth-mkt-filter-field--period{display:none}.dth-mkt-filter-submit{width:100%}}
</style>
