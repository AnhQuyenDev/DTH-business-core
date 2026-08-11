<style>
    :root {
        --dth-radius-sm: .625rem;
        --dth-radius-md: .875rem;
        --dth-radius-lg: 1rem;
        --dth-border: rgb(229 231 235);
        --dth-muted: rgb(107 114 128);
        --dth-surface-soft: rgb(249 250 251);
    }

    .dark {
        --dth-border: rgba(255, 255, 255, .10);
        --dth-muted: rgb(156 163 175);
        --dth-surface-soft: rgba(255, 255, 255, .035);
    }

    /* Keep Filament's native canvas; only normalize rhythm, hierarchy and density. */
    .fi-main {
        padding-bottom: 3rem;
    }

    .fi-header-heading {
        letter-spacing: -.025em;
    }

    .fi-sidebar-nav {
        padding-top: .75rem;
    }

    .fi-sidebar-nav-groups {
        gap: 1.1rem;
    }

    .fi-sidebar-group-label {
        font-size: .69rem !important;
        font-weight: 700 !important;
        letter-spacing: .065em;
        text-transform: uppercase;
        color: var(--dth-muted) !important;
    }

    .fi-sidebar-item-button {
        min-height: 2.45rem;
        border-radius: var(--dth-radius-sm) !important;
    }

    .fi-sidebar-item.fi-active > .fi-sidebar-item-button {
        box-shadow: inset 0 0 0 1px rgba(var(--primary-500), .16);
    }

    .fi-section,
    .fi-ta-ctn,
    .fi-fo-tabs,
    .fi-modal-window {
        border-radius: var(--dth-radius-md) !important;
    }

    .fi-section {
        box-shadow: 0 1px 2px rgba(0, 0, 0, .025) !important;
    }

    .dark .fi-section {
        box-shadow: none !important;
    }

    .fi-section-header {
        padding-top: 1rem !important;
        padding-bottom: 1rem !important;
    }

    .fi-section-header-heading {
        font-weight: 700 !important;
        letter-spacing: -.012em;
    }

    .fi-fo-field-wrp-label label,
    .fi-fo-field-wrp-label span {
        font-weight: 600;
    }

    .fi-input-wrp,
    .fi-select-input,
    .fi-fo-textarea textarea {
        border-radius: var(--dth-radius-sm) !important;
    }

    .fi-btn {
        border-radius: var(--dth-radius-sm) !important;
        font-weight: 650;
    }

    .fi-badge {
        border-radius: 999px !important;
        font-weight: 650 !important;
        letter-spacing: .005em;
    }

    .fi-ta-header-cell-label {
        font-size: .73rem;
        font-weight: 700 !important;
        letter-spacing: .02em;
    }

    .fi-ta-row {
        transition: background-color .12s ease;
    }

    .fi-ta-row:hover {
        background: var(--dth-surface-soft);
    }

    .fi-ta-cell {
        vertical-align: middle;
    }

    .fi-dropdown-panel {
        border-radius: var(--dth-radius-md) !important;
    }

    .fi-tabs {
        border-radius: var(--dth-radius-md) !important;
    }

    /* Shared report primitives. */
    .dth-report-grid {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 1rem;
    }

    @media (min-width: 768px) {
        .dth-report-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (min-width: 1280px) {
        .dth-report-grid.dth-report-grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }

    .dth-metric-card {
        border: 1px solid var(--dth-border);
        border-radius: var(--dth-radius-md);
        padding: 1.1rem;
        background: transparent;
    }

    .dth-metric-label {
        color: var(--dth-muted);
        font-size: .78rem;
        font-weight: 600;
    }

    .dth-metric-value {
        margin-top: .35rem;
        font-size: 1.55rem;
        line-height: 1.2;
        font-weight: 750;
        letter-spacing: -.025em;
    }

    .dth-report-table {
        width: 100%;
        font-size: .82rem;
    }

    .dth-report-table th {
        padding: .7rem .65rem;
        border-bottom: 1px solid var(--dth-border);
        color: var(--dth-muted);
        font-size: .71rem;
        font-weight: 700;
        letter-spacing: .025em;
        text-transform: uppercase;
    }

    .dth-report-table td {
        padding: .75rem .65rem;
        border-bottom: 1px solid var(--dth-border);
    }

    .dth-report-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .dth-report-table tbody tr:hover {
        background: var(--dth-surface-soft);
    }

    .dth-filter-label {
        display: block;
        margin-bottom: .35rem;
        color: var(--dth-muted);
        font-size: .75rem;
        font-weight: 650;
    }

    .dth-note {
        border: 1px solid var(--dth-border);
        border-radius: var(--dth-radius-sm);
        padding: .9rem 1rem;
        background: var(--dth-surface-soft);
        color: var(--dth-muted);
        font-size: .8rem;
        line-height: 1.55;
    }


    /* Round 2: enterprise dashboards and report visualization primitives. */
    .dth-dashboard-hero {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        justify-content: space-between;
        border: 1px solid var(--dth-border);
        border-radius: var(--dth-radius-lg);
        padding: 1.35rem 1.4rem;
        background:
            radial-gradient(circle at 100% 0%, rgba(var(--primary-500), .10), transparent 38%),
            var(--dth-surface-soft);
    }

    @media (min-width: 768px) {
        .dth-dashboard-hero { flex-direction: row; align-items: center; }
    }

    .dth-dashboard-hero h2 {
        margin-top: .18rem;
        color: rgb(17 24 39);
        font-size: 1.45rem;
        line-height: 1.25;
        font-weight: 760;
        letter-spacing: -.025em;
    }

    .dark .dth-dashboard-hero h2 { color: white; }

    .dth-dashboard-hero p:not(.dth-eyebrow) {
        margin-top: .3rem;
        max-width: 52rem;
        color: var(--dth-muted);
        font-size: .86rem;
        line-height: 1.55;
    }

    .dth-eyebrow {
        color: var(--dth-muted);
        font-size: .68rem;
        font-weight: 760;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .dth-tone-admin { box-shadow: inset 4px 0 0 rgb(245 158 11); }
    .dth-tone-marketing { box-shadow: inset 4px 0 0 rgb(168 85 247); }
    .dth-tone-customer-service { box-shadow: inset 4px 0 0 rgb(14 165 233); }
    .dth-tone-sales { box-shadow: inset 4px 0 0 rgb(245 158 11); }
    .dth-tone-finance { box-shadow: inset 4px 0 0 rgb(34 197 94); }

    .dth-metric-card[data-tone="success"] { box-shadow: inset 0 3px 0 rgb(34 197 94); }
    .dth-metric-card[data-tone="warning"] { box-shadow: inset 0 3px 0 rgb(245 158 11); }
    .dth-metric-card[data-tone="danger"] { box-shadow: inset 0 3px 0 rgb(239 68 68); }
    .dth-metric-card[data-tone="info"] { box-shadow: inset 0 3px 0 rgb(14 165 233); }
    .dth-metric-card[data-tone="primary"] { box-shadow: inset 0 3px 0 rgb(var(--primary-500)); }
    .dth-metric-card[data-tone="gray"] { box-shadow: inset 0 3px 0 rgb(156 163 175); }

    .dth-metric-card[data-tone] svg { color: var(--dth-muted); }

    .dth-bar-row { display: grid; gap: .45rem; }
    .dth-bar-meta { display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; font-size: .8rem; }
    .dth-bar-meta span { color: rgb(55 65 81); font-weight: 650; }
    .dark .dth-bar-meta span { color: rgb(229 231 235); }
    .dth-bar-meta strong { color: var(--dth-muted); font-size: .75rem; font-weight: 700; text-align: right; }
    .dth-bar-track { height: .52rem; overflow: hidden; border-radius: 999px; background: var(--dth-surface-soft); box-shadow: inset 0 0 0 1px var(--dth-border); }
    .dth-bar-fill { display: block; height: 100%; border-radius: inherit; min-width: .25rem; }
    .dth-bar-admin { background: rgb(245 158 11); }
    .dth-bar-marketing { background: rgb(168 85 247); }
    .dth-bar-info { background: rgb(14 165 233); }
    .dth-bar-sales { background: rgb(245 158 11); }
    .dth-bar-finance { background: rgb(34 197 94); }

    .dth-column-chart {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: .75rem;
        min-height: 13rem;
        align-items: end;
    }
    .dth-column-item { display: grid; grid-template-rows: auto 8rem auto; gap: .45rem; min-width: 0; text-align: center; }
    .dth-column-value { min-height: 1rem; color: var(--dth-muted); font-size: .65rem; font-weight: 650; }
    .dth-column-track { display: flex; align-items: end; justify-content: center; overflow: hidden; border-radius: .55rem; background: var(--dth-surface-soft); border: 1px solid var(--dth-border); }
    .dth-column-fill { display: block; width: 58%; min-height: 2px; border-radius: .45rem .45rem 0 0; }
    .dth-column-label { color: var(--dth-muted); font-size: .68rem; white-space: nowrap; }

    .dth-list-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .8rem .9rem;
        border: 1px solid var(--dth-border);
        border-radius: var(--dth-radius-sm);
        background: transparent;
    }
    .dth-list-row:hover { background: var(--dth-surface-soft); }

    .dth-empty-state {
        padding: 2rem 1rem;
        text-align: center;
        color: var(--dth-muted);
        font-size: .82rem;
        border: 1px dashed var(--dth-border);
        border-radius: var(--dth-radius-sm);
    }

    .dth-report-link-card {
        display: flex;
        align-items: center;
        gap: .7rem;
        min-height: 3.25rem;
        padding: .8rem .9rem;
        border: 1px solid var(--dth-border);
        border-radius: var(--dth-radius-sm);
        color: rgb(55 65 81);
        font-size: .8rem;
        font-weight: 650;
        transition: background-color .12s ease, border-color .12s ease;
    }
    .dark .dth-report-link-card { color: rgb(229 231 235); }
    .dth-report-link-card span { flex: 1; }
    .dth-report-link-card:hover { background: var(--dth-surface-soft); border-color: rgba(var(--primary-500), .35); }

    /* Form density: keep fields readable without stretching small inputs across the whole canvas. */
    .fi-section-content-ctn .fi-fo-component-ctn { row-gap: 1rem; }
    .fi-fo-field-wrp-helper-text { max-width: 58rem; line-height: 1.45; }
    .fi-fo-textarea textarea { min-height: 6rem; }
    .fi-section-header-description { max-width: 62rem; line-height: 1.5; }


    /* Redesign UI/UX Phase 2: analytics and decision-support surfaces. */
    .dth-analytics-shell { display: grid; gap: 1.25rem; }
    .dth-analytics-toolbar {
        display: flex; flex-direction: column; gap: .9rem; align-items: stretch; justify-content: space-between;
        padding: 1rem 1.1rem; border: 1px solid var(--dth-border); border-radius: var(--dth-radius-md); background: var(--dth-surface-soft);
    }
    @media (min-width: 768px) { .dth-analytics-toolbar { flex-direction: row; align-items: center; } }
    .dth-analytics-toolbar__title { font-weight: 750; letter-spacing: -.015em; color: rgb(17 24 39); }
    .dark .dth-analytics-toolbar__title { color: white; }
    .dth-analytics-toolbar__subtitle { margin-top: .15rem; color: var(--dth-muted); font-size: .78rem; }
    .dth-period-filter { display: flex; align-items: center; gap: .55rem; min-width: 14rem; }
    .dth-period-filter > span { white-space: nowrap; color: var(--dth-muted); font-size: .72rem; font-weight: 700; }
    .dth-period-filter .fi-input-wrp { min-width: 10rem; }

    .dth-analytics-kpi-grid { display: grid; grid-template-columns: repeat(1,minmax(0,1fr)); gap: .85rem; }
    @media (min-width: 640px) { .dth-analytics-kpi-grid { grid-template-columns: repeat(2,minmax(0,1fr)); } }
    @media (min-width: 1280px) { .dth-analytics-kpi-grid { grid-template-columns: repeat(4,minmax(0,1fr)); } }
    .dth-analytics-kpi {
        position: relative; overflow: hidden; min-height: 8.1rem; padding: 1rem 1.05rem;
        border: 1px solid var(--dth-border); border-radius: var(--dth-radius-md); background: rgba(255,255,255,.38);
    }
    .dark .dth-analytics-kpi { background: rgba(255,255,255,.018); }
    .dth-analytics-kpi::after { content:''; position:absolute; inset:auto -.5rem -.8rem auto; width:4.5rem; height:4.5rem; border-radius:999px; opacity:.08; background: currentColor; }
    .dth-analytics-kpi[data-tone="success"] { color: rgb(34 197 94); }
    .dth-analytics-kpi[data-tone="warning"] { color: rgb(245 158 11); }
    .dth-analytics-kpi[data-tone="danger"] { color: rgb(239 68 68); }
    .dth-analytics-kpi[data-tone="info"] { color: rgb(59 130 246); }
    .dth-analytics-kpi[data-tone="marketing"] { color: rgb(168 85 247); }
    .dth-analytics-kpi[data-tone="primary"] { color: rgb(var(--primary-500)); }
    .dth-analytics-kpi__top { display:flex; align-items:center; justify-content:space-between; gap:.75rem; }
    .dth-analytics-kpi__label { color:var(--dth-muted); font-size:.74rem; font-weight:700; }
    .dth-analytics-kpi__icon { display:grid; place-items:center; width:2rem; height:2rem; border-radius:.65rem; color:white; }
    .dth-analytics-kpi[data-tone="success"] .dth-analytics-kpi__icon { background:rgb(34 197 94); }
    .dth-analytics-kpi[data-tone="warning"] .dth-analytics-kpi__icon { background:rgb(245 158 11); }
    .dth-analytics-kpi[data-tone="danger"] .dth-analytics-kpi__icon { background:rgb(239 68 68); }
    .dth-analytics-kpi[data-tone="info"] .dth-analytics-kpi__icon { background:rgb(59 130 246); }
    .dth-analytics-kpi[data-tone="marketing"] .dth-analytics-kpi__icon { background:rgb(168 85 247); }
    .dth-analytics-kpi[data-tone="primary"] .dth-analytics-kpi__icon { background:rgb(var(--primary-500)); }
    .dth-analytics-kpi__icon svg { color:white; }
    .dth-analytics-kpi__value { margin-top:.65rem; color:rgb(17 24 39); font-size:1.55rem; font-weight:800; letter-spacing:-.035em; line-height:1.1; }
    .dark .dth-analytics-kpi__value { color:white; }
    .dth-analytics-kpi__footer { display:flex; align-items:center; gap:.35rem; margin-top:.7rem; color:var(--dth-muted); font-size:.68rem; }
    .dth-analytics-delta { font-weight:800; }
    .dth-analytics-delta[data-direction="up"] { color:rgb(34 197 94); }
    .dth-analytics-delta[data-direction="down"] { color:rgb(239 68 68); }
    .dth-analytics-delta[data-direction="flat"] { color:var(--dth-muted); }

    .dth-analytics-layout-2 { display:grid; grid-template-columns:1fr; gap:1rem; }
    @media (min-width: 1280px) { .dth-analytics-layout-2 { grid-template-columns:minmax(0,1.45fr) minmax(20rem,.85fr); } }
    .dth-analytics-layout-equal { display:grid; grid-template-columns:1fr; gap:1rem; }
    @media (min-width: 1100px) { .dth-analytics-layout-equal { grid-template-columns:repeat(2,minmax(0,1fr)); } }

    .dth-line-chart { min-height: 15rem; }
    .dth-line-chart__svg { width:100%; height:auto; min-height:14rem; overflow:visible; }
    .dth-chart-grid-line { stroke: var(--dth-border); stroke-width:1; stroke-dasharray:4 5; }
    .dth-chart-axis-label,.dth-chart-axis-value { fill:var(--dth-muted); font-size:10px; }
    .dth-chart-axis-value { font-size:9px; }
    .dth-chart-line { fill:none; stroke-width:3; stroke-linecap:round; stroke-linejoin:round; }
    .dth-chart-line--current { stroke:rgb(245 158 11); }
    .dth-chart-line--previous { stroke:rgb(100 116 139); stroke-width:2; stroke-dasharray:7 6; opacity:.72; }
    .dth-chart-area { fill:rgba(245,158,11,.10); stroke:none; }
    .dth-chart-legend { display:flex; justify-content:flex-end; flex-wrap:wrap; gap:.85rem; margin-bottom:.35rem; color:var(--dth-muted); font-size:.68rem; font-weight:650; }
    .dth-chart-legend span { display:flex; align-items:center; gap:.35rem; }
    .dth-chart-dot { width:.55rem; height:.55rem; border-radius:999px; display:inline-block; }
    .dth-chart-dot--current { background:rgb(245 158 11); }
    .dth-chart-dot--previous { background:rgb(100 116 139); }

    .dth-analytics-bars { display:grid; gap:.9rem; }
    .dth-analytics-bar-row { display:grid; gap:.35rem; }
    .dth-analytics-bar-meta { display:flex; align-items:baseline; justify-content:space-between; gap:1rem; font-size:.76rem; }
    .dth-analytics-bar-meta span { min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:rgb(55 65 81); font-weight:650; }
    .dark .dth-analytics-bar-meta span { color:rgb(229 231 235); }
    .dth-analytics-bar-meta strong { color:var(--dth-muted); font-size:.7rem; white-space:nowrap; }
    .dth-analytics-bar-track { height:.52rem; overflow:hidden; border-radius:999px; background:var(--dth-surface-soft); box-shadow:inset 0 0 0 1px var(--dth-border); }
    .dth-analytics-bar-track span { display:block; height:100%; min-width:3px; border-radius:inherit; background:rgb(var(--primary-500)); }
    .dth-analytics-bars[data-tone="marketing"] .dth-analytics-bar-track span { background:rgb(168 85 247); }
    .dth-analytics-bars[data-tone="finance"] .dth-analytics-bar-track span { background:rgb(34 197 94); }
    .dth-analytics-bars[data-tone="sales"] .dth-analytics-bar-track span { background:rgb(245 158 11); }
    .dth-analytics-bars[data-tone="info"] .dth-analytics-bar-track span { background:rgb(59 130 246); }
    .dth-analytics-bar-subtitle { color:var(--dth-muted); font-size:.65rem; }

    .dth-donut-layout { display:grid; grid-template-columns:1fr; gap:1.2rem; align-items:center; }
    @media (min-width: 640px) { .dth-donut-layout { grid-template-columns:11rem 1fr; } }
    .dth-donut { width:10.5rem; aspect-ratio:1; margin:auto; border-radius:999px; display:grid; place-items:center; box-shadow:inset 0 0 0 1px rgba(255,255,255,.08); }
    .dth-donut__hole { width:62%; aspect-ratio:1; display:flex; flex-direction:column; align-items:center; justify-content:center; border-radius:999px; background:white; box-shadow:0 0 0 1px var(--dth-border); }
    .dark .dth-donut__hole { background:rgb(24 24 27); }
    .dth-donut__hole strong { color:rgb(17 24 39); font-size:1rem; font-weight:800; }
    .dark .dth-donut__hole strong { color:white; }
    .dth-donut__hole span { margin-top:.1rem; color:var(--dth-muted); font-size:.62rem; }
    .dth-donut-legend { display:grid; gap:.5rem; }
    .dth-donut-legend__row { display:grid; grid-template-columns:auto minmax(0,1fr) auto; align-items:center; gap:.55rem; font-size:.72rem; }
    .dth-donut-legend__row i { width:.55rem; height:.55rem; border-radius:999px; }
    .dth-donut-legend__label { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:rgb(55 65 81); font-weight:650; }
    .dark .dth-donut-legend__label { color:rgb(229 231 235); }
    .dth-donut-legend__row strong { color:var(--dth-muted); font-size:.68rem; }

    .dth-funnel { display:flex; flex-direction:column; align-items:center; gap:.42rem; padding:.3rem 0; }
    .dth-funnel__step { display:flex; justify-content:space-between; gap:.75rem; min-width:10rem; padding:.62rem .8rem; border:1px solid rgba(245,158,11,.24); border-radius:.55rem; background:rgba(245,158,11,.08); transition:width .2s ease; }
    .dth-funnel__step span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:rgb(55 65 81); font-size:.72rem; font-weight:650; }
    .dark .dth-funnel__step span { color:rgb(229 231 235); }
    .dth-funnel__step strong { font-size:.72rem; color:rgb(245 158 11); }

    .dth-insight-grid { display:grid; grid-template-columns:1fr; gap:.75rem; }
    @media (min-width: 768px) { .dth-insight-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    .dth-insight-card { display:flex; gap:.8rem; padding:.9rem 1rem; border:1px solid var(--dth-border); border-radius:var(--dth-radius-sm); background:var(--dth-surface-soft); }
    .dth-insight-card__icon { flex:0 0 auto; display:grid; place-items:center; width:2rem; height:2rem; border-radius:.6rem; background:rgba(245,158,11,.12); color:rgb(245 158 11); }
    .dth-insight-card strong { display:block; color:rgb(17 24 39); font-size:.78rem; }
    .dark .dth-insight-card strong { color:white; }
    .dth-insight-card p { margin-top:.2rem; color:var(--dth-muted); font-size:.72rem; line-height:1.5; }
    .dth-insight-card[data-tone="success"] .dth-insight-card__icon { background:rgba(34,197,94,.12); color:rgb(34 197 94); }
    .dth-insight-card[data-tone="warning"] .dth-insight-card__icon { background:rgba(245,158,11,.12); color:rgb(245 158 11); }
    .dth-insight-card[data-tone="info"] .dth-insight-card__icon { background:rgba(59,130,246,.12); color:rgb(59 130 246); }
    .dth-insight-card[data-tone="primary"] .dth-insight-card__icon { background:rgba(var(--primary-500),.12); color:rgb(var(--primary-500)); }
    .dth-insight-card[data-tone="marketing"] .dth-insight-card__icon { background:rgba(168,85,247,.12); color:rgb(168 85 247); }

    .dth-analytics-table { width:100%; border-collapse:separate; border-spacing:0; font-size:.76rem; }
    .dth-analytics-table th { padding:.68rem .7rem; color:var(--dth-muted); font-size:.66rem; font-weight:750; text-align:left; text-transform:uppercase; letter-spacing:.025em; border-bottom:1px solid var(--dth-border); }
    .dth-analytics-table td { padding:.75rem .7rem; border-bottom:1px solid var(--dth-border); vertical-align:middle; }
    .dth-analytics-table tbody tr:last-child td { border-bottom:0; }
    .dth-analytics-table tbody tr:hover { background:var(--dth-surface-soft); }
    .dth-analytics-table .numeric { text-align:right; font-variant-numeric:tabular-nums; }
    .dth-analytics-index { display:flex; align-items:center; gap:.5rem; min-width:8rem; }
    .dth-analytics-index__track { flex:1; height:.42rem; background:var(--dth-surface-soft); border-radius:999px; box-shadow:inset 0 0 0 1px var(--dth-border); overflow:hidden; }
    .dth-analytics-index__track span { display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,rgb(59 130 246),rgb(34 197 94)); }
    .dth-analytics-index strong { width:2.5rem; text-align:right; font-size:.68rem; }

    .dth-campaign-card-grid { display:grid; grid-template-columns:1fr; gap:.8rem; }
    @media (min-width: 900px) { .dth-campaign-card-grid { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    .dth-campaign-card { padding:.9rem 1rem; border:1px solid var(--dth-border); border-radius:var(--dth-radius-sm); background:transparent; }
    .dth-campaign-card__head { display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem; }
    .dth-campaign-card__head strong { color:rgb(17 24 39); font-size:.78rem; line-height:1.4; }
    .dark .dth-campaign-card__head strong { color:white; }
    .dth-campaign-card__metrics { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.5rem; margin-top:.75rem; }
    .dth-campaign-card__metrics div { padding:.55rem .6rem; border-radius:.5rem; background:var(--dth-surface-soft); }
    .dth-campaign-card__metrics span { display:block; color:var(--dth-muted); font-size:.6rem; }
    .dth-campaign-card__metrics strong { display:block; margin-top:.15rem; color:rgb(17 24 39); font-size:.75rem; }
    .dark .dth-campaign-card__metrics strong { color:white; }


    /* Configuration UI Refresh RC1: compact, action-first administration surfaces. */
    .dth-config-shell { display:grid; gap:1rem; }

    .dth-config-toolbar {
        display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
        padding:.15rem .1rem .35rem;
    }
    .dth-config-toolbar > p {
        margin:0; max-width:58rem; color:var(--dth-muted); font-size:.8rem; line-height:1.45;
    }
    .dth-config-toolbar__status {
        display:inline-flex; align-items:center; gap:.4rem; min-height:2rem; padding:.35rem .65rem;
        border:1px solid var(--dth-border); border-radius:999px; font-size:.7rem; font-weight:720; white-space:nowrap;
    }
    .dth-config-toolbar__status--success { color:rgb(22 163 74); background:rgba(34,197,94,.08); border-color:rgba(34,197,94,.24); }
    .dth-config-toolbar__status--warning { color:rgb(217 119 6); background:rgba(245,158,11,.08); border-color:rgba(245,158,11,.24); }

    .dth-config-launch-grid { display:grid; grid-template-columns:1fr; gap:.75rem; }
    @media (min-width: 900px) {
        .dth-config-launch-grid--2 { grid-template-columns:repeat(2,minmax(0,1fr)); }
        .dth-config-launch-grid--3 { grid-template-columns:repeat(3,minmax(0,1fr)); }
    }
    .dth-config-launch-card {
        display:grid; grid-template-columns:auto minmax(0,1fr) auto; align-items:center; gap:.8rem;
        min-height:5rem; padding:.9rem 1rem; border:1px solid var(--dth-border); border-radius:var(--dth-radius-md);
        background:rgba(255,255,255,.34); color:inherit; text-decoration:none;
        transition:border-color .12s ease, background-color .12s ease, box-shadow .12s ease, transform .12s ease;
    }
    .dark .dth-config-launch-card { background:rgba(255,255,255,.02); }
    .dth-config-launch-card:hover {
        border-color:rgba(var(--primary-500),.38); background:var(--dth-surface-soft);
        box-shadow:0 8px 22px rgba(15,23,42,.05); transform:translateY(-1px);
    }
    .dark .dth-config-launch-card:hover { box-shadow:0 10px 24px rgba(0,0,0,.16); }
    .dth-config-launch-card__icon {
        display:grid; place-items:center; width:2.35rem; height:2.35rem; border-radius:.7rem;
        background:rgba(var(--primary-500),.10); color:rgb(var(--primary-600));
    }
    .dark .dth-config-launch-card__icon { color:rgb(var(--primary-400)); }
    .dth-config-launch-card__content { display:grid; gap:.18rem; min-width:0; }
    .dth-config-launch-card__content strong { color:rgb(17 24 39); font-size:.82rem; font-weight:760; }
    .dark .dth-config-launch-card__content strong { color:white; }
    .dth-config-launch-card__content span {
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--dth-muted); font-size:.7rem;
    }
    .dth-config-launch-card__meta { display:flex; align-items:center; gap:.45rem; color:var(--dth-muted); }
    .dth-config-launch-card__meta b {
        display:grid; place-items:center; min-width:1.85rem; height:1.85rem; padding:0 .45rem;
        border:1px solid var(--dth-border); border-radius:999px; font-size:.68rem; font-variant-numeric:tabular-nums;
    }

    .dth-config-attention-panel {
        display:grid; grid-template-columns:1fr; gap:.45rem; padding:.75rem .85rem;
        border:1px solid rgba(245,158,11,.22); border-radius:var(--dth-radius-sm); background:rgba(245,158,11,.045);
    }
    @media (min-width: 900px) { .dth-config-attention-panel { grid-template-columns:repeat(2,minmax(0,1fr)); } }
    .dth-config-attention-panel__item { display:flex; align-items:flex-start; gap:.45rem; color:var(--dth-muted); font-size:.72rem; line-height:1.4; }
    .dth-config-attention-panel__item svg { flex:0 0 auto; margin-top:.05rem; color:rgb(245 158 11); }

    .dth-config-palette-panel {
        padding:.85rem 1rem; border:1px solid var(--dth-border); border-radius:var(--dth-radius-md); background:rgba(255,255,255,.34);
    }
    .dark .dth-config-palette-panel { background:rgba(255,255,255,.02); }
    .dth-config-palette-panel__head { display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:.7rem; }
    .dth-config-palette-panel__head strong { color:rgb(17 24 39); font-size:.78rem; }
    .dark .dth-config-palette-panel__head strong { color:white; }
    .dth-config-palette-panel__head span {
        display:grid; place-items:center; min-width:1.75rem; height:1.75rem; border:1px solid var(--dth-border); border-radius:999px;
        color:var(--dth-muted); font-size:.65rem; font-weight:750;
    }
    .dth-config-palette-strip { display:flex; flex-wrap:wrap; gap:.45rem; }
    .dth-config-palette-chip {
        display:inline-flex; align-items:center; gap:.38rem; min-height:1.95rem; padding:.32rem .55rem;
        border:1px solid var(--dth-border); border-radius:999px; color:var(--dth-muted); font-size:.66rem; background:var(--dth-surface-soft);
    }
    .dth-color-dot { width:.65rem; height:.65rem; border-radius:999px; flex:0 0 auto; box-shadow:0 0 0 1px rgba(15,23,42,.08); }
    .dth-color-dot--gray { background:#6b7280; }
    .dth-color-dot--primary, .dth-color-dot--amber { background:#f59e0b; }
    .dth-color-dot--info, .dth-color-dot--sky { background:#0ea5e9; }
    .dth-color-dot--success, .dth-color-dot--emerald { background:#10b981; }
    .dth-color-dot--warning { background:#f59e0b; }
    .dth-color-dot--danger { background:#ef4444; }
    .dth-color-dot--orange { background:#f97316; }
    .dth-color-dot--yellow { background:#eab308; }
    .dth-color-dot--lime { background:#84cc16; }
    .dth-color-dot--green { background:#22c55e; }
    .dth-color-dot--teal { background:#14b8a6; }
    .dth-color-dot--cyan { background:#06b6d4; }
    .dth-color-dot--blue { background:#3b82f6; }
    .dth-color-dot--indigo { background:#6366f1; }
    .dth-color-dot--violet { background:#8b5cf6; }
    .dth-color-dot--purple { background:#a855f7; }
    .dth-color-dot--fuchsia { background:#d946ef; }
    .dth-color-dot--pink { background:#ec4899; }
    .dth-color-dot--rose { background:#f43f5e; }

    .dth-config-form-footer {
        position:sticky; bottom:.8rem; z-index:20; display:flex; justify-content:flex-end; margin-top:1rem; padding:.7rem;
        border:1px solid var(--dth-border); border-radius:var(--dth-radius-sm); background:rgba(255,255,255,.88); backdrop-filter:blur(12px);
        box-shadow:0 8px 26px rgba(15,23,42,.08);
    }
    .dark .dth-config-form-footer { background:rgba(24,24,27,.88); box-shadow:0 10px 28px rgba(0,0,0,.22); }

    .dth-config-matrix { width:100%; border-collapse:separate; border-spacing:0; font-size:.76rem; }
    .dth-config-matrix th {
        padding:.65rem .7rem; border-bottom:1px solid var(--dth-border); color:var(--dth-muted);
        font-size:.64rem; font-weight:760; text-align:left; text-transform:uppercase; letter-spacing:.025em;
    }
    .dth-config-matrix td { padding:.72rem .7rem; border-bottom:1px solid var(--dth-border); vertical-align:middle; color:rgb(55 65 81); }
    .dark .dth-config-matrix td { color:rgb(229 231 235); }
    .dth-config-matrix tbody tr:last-child td { border-bottom:0; }
    .dth-config-matrix tbody tr:hover { background:var(--dth-surface-soft); }


    /* Configuration UX polish: visible hierarchy, compact forms and accessible color swatches. */
    .dth-config-sidebar-scope .fi-sidebar-sub-group-items {
        margin-left: .6rem;
        padding-left: .45rem;
        border-left: 1px solid var(--dth-border);
    }
    .dth-config-sidebar-scope .fi-sidebar-sub-group-items > .fi-sidebar-item > .fi-sidebar-item-button {
        padding-left: .45rem !important;
    }
    .dth-config-sidebar-scope .fi-sidebar-sub-group-items .fi-sidebar-item-grouped-border {
        width: 1.15rem !important;
        margin-left: -.12rem;
    }

    .dth-config-form .fi-section-content {
        row-gap: .9rem !important;
    }
    .dth-config-form .fi-fo-field-wrp-label {
        margin-bottom: .15rem;
    }
    .dth-config-toggle-wrap {
        padding-top: 1.45rem;
        align-self: start;
    }
    @media (max-width: 767px) {
        .dth-config-toggle-wrap { padding-top: 0; }
    }

    .dth-color-swatch-picker {
        display: flex !important;
        flex-wrap: wrap !important;
        grid-template-columns: none !important;
        gap: .55rem !important;
        margin-top: .15rem !important;
    }
    .dth-color-swatch-picker > div {
        padding-top: 0 !important;
    }
    .dth-color-swatch-picker .fi-btn {
        width: 2.15rem !important;
        min-width: 2.15rem !important;
        height: 2.15rem !important;
        min-height: 2.15rem !important;
        padding: 0 !important;
        border-radius: 999px !important;
        border: 2px solid rgba(148, 163, 184, .26) !important;
        box-shadow: inset 0 0 0 2px rgba(255,255,255,.72), 0 1px 2px rgba(15,23,42,.10) !important;
        transition: transform .12s ease, box-shadow .12s ease, border-color .12s ease !important;
    }
    .dark .dth-color-swatch-picker .fi-btn {
        box-shadow: inset 0 0 0 2px rgba(24,24,27,.82), 0 1px 2px rgba(0,0,0,.26) !important;
    }
    .dth-color-swatch-picker .fi-btn:hover {
        transform: translateY(-1px) scale(1.05);
        border-color: rgba(var(--primary-500), .48) !important;
    }
    .dth-color-swatch-picker input:checked + .fi-btn {
        border-color: rgb(var(--primary-500)) !important;
        box-shadow: 0 0 0 3px rgba(var(--primary-500), .22), inset 0 0 0 2px rgba(255,255,255,.88) !important;
        transform: scale(1.06);
    }
    .dark .dth-color-swatch-picker input:checked + .fi-btn {
        box-shadow: 0 0 0 3px rgba(var(--primary-500), .24), inset 0 0 0 2px rgba(24,24,27,.9) !important;
    }
    .dth-color-swatch-picker label[for$="-gray"] { background:#6b7280 !important; }
    .dth-color-swatch-picker label[for$="-primary"],
    .dth-color-swatch-picker label[for$="-amber"] { background:#f59e0b !important; }
    .dth-color-swatch-picker label[for$="-info"],
    .dth-color-swatch-picker label[for$="-sky"] { background:#0ea5e9 !important; }
    .dth-color-swatch-picker label[for$="-success"],
    .dth-color-swatch-picker label[for$="-green"] { background:#22c55e !important; }
    .dth-color-swatch-picker label[for$="-warning"] { background:#f59e0b !important; }
    .dth-color-swatch-picker label[for$="-danger"] { background:#ef4444 !important; }
    .dth-color-swatch-picker label[for$="-orange"] { background:#f97316 !important; }
    .dth-color-swatch-picker label[for$="-yellow"] { background:#eab308 !important; }
    .dth-color-swatch-picker label[for$="-lime"] { background:#84cc16 !important; }
    .dth-color-swatch-picker label[for$="-emerald"] { background:#10b981 !important; }
    .dth-color-swatch-picker label[for$="-teal"] { background:#14b8a6 !important; }
    .dth-color-swatch-picker label[for$="-cyan"] { background:#06b6d4 !important; }
    .dth-color-swatch-picker label[for$="-blue"] { background:#3b82f6 !important; }
    .dth-color-swatch-picker label[for$="-indigo"] { background:#6366f1 !important; }
    .dth-color-swatch-picker label[for$="-violet"] { background:#8b5cf6 !important; }
    .dth-color-swatch-picker label[for$="-purple"] { background:#a855f7 !important; }
    .dth-color-swatch-picker label[for$="-fuchsia"] { background:#d946ef !important; }
    .dth-color-swatch-picker label[for$="-pink"] { background:#ec4899 !important; }
    .dth-color-swatch-picker label[for$="-rose"] { background:#f43f5e !important; }

    .dth-config-color-orbit {
        display:flex;
        align-items:center;
        flex-wrap:wrap;
        gap:.65rem;
    }
    .dth-config-color-orbit__swatch {
        width:1.85rem;
        height:1.85rem;
        border-radius:999px;
        background:var(--swatch-color);
        border:2px solid rgba(148,163,184,.24);
        box-shadow:inset 0 0 0 2px rgba(255,255,255,.72), 0 1px 2px rgba(15,23,42,.10);
        transition:transform .12s ease, box-shadow .12s ease;
        cursor:help;
    }
    .dark .dth-config-color-orbit__swatch {
        box-shadow:inset 0 0 0 2px rgba(24,24,27,.84), 0 1px 2px rgba(0,0,0,.25);
    }
    .dth-config-color-orbit__swatch:hover {
        transform:translateY(-1px) scale(1.08);
        box-shadow:0 0 0 3px rgba(var(--primary-500),.16), inset 0 0 0 2px rgba(255,255,255,.82);
    }


    /* Configuration UI/UX RC3 - scoped visual system inspired by the approved reference direction. */
    body.dth-config-page {
        --dth-config-accent: 245 158 11;
        --dth-config-panel: rgba(255,255,255,.92);
        --dth-config-panel-soft: rgba(248,250,252,.92);
        --dth-config-border: rgba(15,23,42,.08);
        --dth-config-text: rgb(15 23 42);
        --dth-config-muted: rgb(100 116 139);
        --dth-config-shadow: 0 16px 36px rgba(15,23,42,.06);
    }
    .dark body.dth-config-page,
    body.dth-config-page.dark {
        --dth-config-panel: rgba(24,24,27,.86);
        --dth-config-panel-soft: rgba(255,255,255,.028);
        --dth-config-border: rgba(255,255,255,.095);
        --dth-config-text: rgb(250 250 250);
        --dth-config-muted: rgb(161 161 170);
        --dth-config-shadow: 0 18px 44px rgba(0,0,0,.18);
    }

    body.dth-config-page .fi-main {
        background:
            radial-gradient(circle at 88% 4%, rgba(var(--dth-config-accent), .055), transparent 26rem),
            transparent;
    }
    body.dth-config-page .fi-header { gap:.35rem; margin-bottom:1.35rem; }
    body.dth-config-page .fi-header-heading {
        font-size:clamp(1.55rem,2vw,2rem) !important;
        line-height:1.15 !important;
        font-weight:800 !important;
        letter-spacing:-.035em !important;
    }
    body.dth-config-page .fi-header-subheading {
        max-width:58rem;
        color:var(--dth-config-muted) !important;
        font-size:.86rem !important;
        line-height:1.55 !important;
    }
    body.dth-config-page .fi-breadcrumbs-item-label { font-size:.76rem !important; }

    /* Sidebar: stronger parent/child hierarchy only for Configuration (the only nested group in V1). */
    .dth-config-sidebar-scope {
        padding:.2rem .15rem .35rem;
        border-radius:1rem;
    }
    .dth-config-sidebar-scope .fi-sidebar-group-button {
        min-height:2.7rem;
        border-radius:.8rem;
        padding:.55rem .7rem !important;
        background:rgba(var(--dth-config-accent),.035);
    }
    .dth-config-sidebar-scope .fi-sidebar-group-icon {
        width:1.2rem !important;
        height:1.2rem !important;
        color:rgb(var(--dth-config-accent)) !important;
    }
    .dth-config-sidebar-scope .fi-sidebar-group-label {
        color:rgb(var(--dth-config-accent)) !important;
        font-size:.72rem !important;
        font-weight:800 !important;
        letter-spacing:.055em !important;
    }
    .dth-config-sidebar-scope > .fi-sidebar-group-items { gap:.3rem !important; }
    .dth-config-sidebar-scope > .fi-sidebar-group-items > .fi-sidebar-item > .fi-sidebar-item-button {
        min-height:2.75rem;
        padding:.55rem .72rem !important;
        border-radius:.78rem !important;
    }
    .dth-config-sidebar-scope > .fi-sidebar-group-items > .fi-sidebar-item.fi-active > .fi-sidebar-item-button {
        background:linear-gradient(90deg, rgba(var(--dth-config-accent),.17), rgba(var(--dth-config-accent),.055)) !important;
        box-shadow:inset 3px 0 0 rgb(var(--dth-config-accent)), inset 0 0 0 1px rgba(var(--dth-config-accent),.18) !important;
    }
    .dth-config-sidebar-scope > .fi-sidebar-group-items > .fi-sidebar-item.fi-active > .fi-sidebar-item-button .fi-sidebar-item-label,
    .dth-config-sidebar-scope > .fi-sidebar-group-items > .fi-sidebar-item.fi-active > .fi-sidebar-item-button .fi-sidebar-item-icon {
        color:rgb(var(--dth-config-accent)) !important;
    }
    .dth-config-sidebar-scope .fi-sidebar-sub-group-items {
        margin:.15rem 0 .2rem 1.05rem !important;
        padding:.15rem 0 .15rem .62rem !important;
        border-left:1px solid rgba(148,163,184,.22) !important;
        gap:.18rem !important;
    }
    .dth-config-sidebar-scope .fi-sidebar-sub-group-items > .fi-sidebar-item > .fi-sidebar-item-button {
        min-height:2.3rem !important;
        padding:.38rem .5rem !important;
        border-radius:.62rem !important;
    }
    .dth-config-sidebar-scope .fi-sidebar-sub-group-items .fi-sidebar-item-grouped-border {
        width:.9rem !important;
        margin-left:-.05rem !important;
    }
    .dth-config-sidebar-scope .fi-sidebar-sub-group-items .fi-sidebar-item-icon {
        width:1.05rem !important;
        height:1.05rem !important;
    }
    .dth-config-sidebar-scope .fi-sidebar-sub-group-items .fi-sidebar-item-label {
        font-size:.8rem !important;
        font-weight:560 !important;
    }
    .dth-config-sidebar-scope .fi-sidebar-sub-group-items .fi-sidebar-item.fi-active > .fi-sidebar-item-button {
        background:rgba(var(--dth-config-accent),.09) !important;
    }

    /* Configuration cards and forms. */
    body.dth-config-page .dth-config-form.fi-section,
    body.dth-config-page .fi-section.dth-config-form,
    body.dth-config-page .fi-ta-ctn,
    body.dth-config-page .fi-modal-window {
        border:1px solid var(--dth-config-border) !important;
        background:var(--dth-config-panel) !important;
        box-shadow:var(--dth-config-shadow) !important;
        border-radius:1rem !important;
        overflow:hidden;
    }
    body.dth-config-page .dth-config-form .fi-section-header {
        min-height:3.6rem;
        padding:.8rem 1rem !important;
        background:linear-gradient(90deg, rgba(var(--dth-config-accent),.035), transparent 28%);
    }
    body.dth-config-page .dth-config-form .fi-section-header-icon {
        width:2rem !important;
        height:2rem !important;
        padding:.42rem;
        border-radius:.62rem;
        background:linear-gradient(145deg, rgba(var(--dth-config-accent),.25), rgba(var(--dth-config-accent),.08));
        color:rgb(var(--dth-config-accent)) !important;
        box-shadow:inset 0 0 0 1px rgba(var(--dth-config-accent),.15);
        margin:0 !important;
    }
    body.dth-config-page .dth-config-form .fi-section-header-heading {
        font-size:.91rem !important;
        font-weight:780 !important;
    }
    body.dth-config-page .dth-config-form .fi-section-header-description {
        font-size:.72rem !important;
        color:var(--dth-config-muted) !important;
    }
    body.dth-config-page .dth-config-form .fi-section-content {
        padding:1rem !important;
        row-gap:.9rem !important;
    }
    body.dth-config-page .dth-config-form .fi-section-content-ctn {
        border-color:var(--dth-config-border) !important;
    }

    /* Fields: compact, balanced, reference-like. */
    body.dth-config-page .fi-fo-field-wrp-label { margin-bottom:.28rem !important; }
    body.dth-config-page .fi-fo-field-wrp-label label,
    body.dth-config-page .fi-fo-field-wrp-label span { font-size:.78rem !important; font-weight:680 !important; }
    body.dth-config-page .fi-input-wrp,
    body.dth-config-page .fi-select-input,
    body.dth-config-page .fi-fo-textarea textarea {
        border-radius:.66rem !important;
        border-color:var(--dth-config-border) !important;
    }
    body.dth-config-page .fi-input-wrp { min-height:2.65rem; }
    body.dth-config-page .fi-input,
    body.dth-config-page .fi-select-input { font-size:.82rem !important; }
    body.dth-config-page .fi-fo-textarea textarea { min-height:6.5rem; }
    body.dth-config-page .fi-fo-field-wrp-hint-icon { color:rgb(113 113 122) !important; }
    body.dth-config-page .fi-fo-placeholder { color:var(--dth-config-text); font-weight:650; }

    /* Toggles: center vertically in their grid cell, never float near the top. */
    body.dth-config-page .dth-config-toggle-wrap {
        padding-top:0 !important;
        align-self:stretch !important;
        display:flex !important;
        align-items:center !important;
        min-height:4.5rem;
    }
    body.dth-config-page .dth-config-toggle-wrap > * { width:100%; }
    body.dth-config-page .dth-config-toggle-wrap .fi-fo-field-wrp { display:flex; flex-direction:column; justify-content:center; height:100%; }
    body.dth-config-page .dth-config-toggle-wrap .fi-fo-toggle { gap:.55rem; }

    /* Every configuration action looks deliberate; primary actions are vivid but not neon. */
    body.dth-config-page .fi-btn {
        min-height:2.5rem;
        border-radius:.68rem !important;
        gap:.48rem !important;
        font-size:.8rem !important;
        font-weight:720 !important;
    }
    body.dth-config-page .fi-btn.fi-color-primary {
        box-shadow:0 7px 18px rgba(var(--dth-config-accent),.16), inset 0 1px 0 rgba(255,255,255,.18) !important;
    }
    body.dth-config-page .fi-btn.fi-color-primary:hover { transform:translateY(-1px); }
    body.dth-config-page .fi-icon-btn { border-radius:.62rem !important; }

    /* Tables: stronger information hierarchy without becoming dense. */
    body.dth-config-page .fi-ta-header { padding:.72rem .9rem !important; }
    body.dth-config-page .fi-ta-header-cell { background:rgba(148,163,184,.035); }
    body.dth-config-page .fi-ta-header-cell-label { font-size:.69rem !important; font-weight:760 !important; }
    body.dth-config-page .fi-ta-row { min-height:3.4rem; }
    body.dth-config-page .fi-ta-row:hover { background:rgba(var(--dth-config-accent),.025) !important; }
    body.dth-config-page .fi-ta-cell { padding-top:.72rem !important; padding-bottom:.72rem !important; }
    body.dth-config-page .fi-ta-actions .fi-icon-btn { color:rgb(var(--dth-config-accent)) !important; }

    /* Real circular color picker. Labels stay accessible to screen readers and tooltips only. */
    body.dth-config-page .dth-color-swatch-picker.fi-fo-toggle-buttons,
    body.dth-config-page .dth-color-swatch-picker .fi-fo-toggle-buttons {
        display:flex !important;
        flex-wrap:wrap !important;
        gap:.62rem !important;
        margin-top:.18rem !important;
    }
    body.dth-config-page .dth-color-swatch-picker > div,
    body.dth-config-page .dth-color-swatch-picker .fi-fo-toggle-buttons > div { padding:0 !important; }
    body.dth-config-page .dth-color-swatch-picker label.fi-btn {
        width:2.55rem !important;
        min-width:2.55rem !important;
        height:2.55rem !important;
        min-height:2.55rem !important;
        padding:0 !important;
        border-radius:999px !important;
        border:2px solid rgba(148,163,184,.28) !important;
        box-shadow:inset 0 0 0 3px rgba(255,255,255,.80), 0 4px 10px rgba(15,23,42,.10) !important;
        transform:none;
        transition:transform .13s ease, box-shadow .13s ease, border-color .13s ease !important;
    }
    .dark body.dth-config-page .dth-color-swatch-picker label.fi-btn {
        box-shadow:inset 0 0 0 3px rgba(24,24,27,.90), 0 5px 12px rgba(0,0,0,.28) !important;
    }
    body.dth-config-page .dth-color-swatch-picker label.fi-btn:hover { transform:translateY(-2px) scale(1.06); }
    body.dth-config-page .dth-color-swatch-picker input:checked + label.fi-btn {
        border-color:rgb(var(--dth-config-accent)) !important;
        box-shadow:0 0 0 3px rgba(var(--dth-config-accent),.26), inset 0 0 0 3px rgba(255,255,255,.92), 0 7px 18px rgba(15,23,42,.12) !important;
        transform:scale(1.07);
    }
    .dark body.dth-config-page .dth-color-swatch-picker input:checked + label.fi-btn {
        box-shadow:0 0 0 3px rgba(var(--dth-config-accent),.28), inset 0 0 0 3px rgba(24,24,27,.94), 0 7px 18px rgba(0,0,0,.32) !important;
    }
    body.dth-config-page .dth-color-swatch-picker input[value="gray"] + label.fi-btn { background:#6b7280 !important; }
    body.dth-config-page .dth-color-swatch-picker input[value="primary"] + label.fi-btn,
    body.dth-config-page .dth-color-swatch-picker input[value="amber"] + label.fi-btn { background:#f59e0b !important; }
    body.dth-config-page .dth-color-swatch-picker input[value="info"] + label.fi-btn,
    body.dth-config-page .dth-color-swatch-picker input[value="sky"] + label.fi-btn { background:#0ea5e9 !important; }
    body.dth-config-page .dth-color-swatch-picker input[value="success"] + label.fi-btn,
    body.dth-config-page .dth-color-swatch-picker input[value="green"] + label.fi-btn { background:#22c55e !important; }
    body.dth-config-page .dth-color-swatch-picker input[value="warning"] + label.fi-btn { background:#f59e0b !important; }
    body.dth-config-page .dth-color-swatch-picker input[value="danger"] + label.fi-btn { background:#ef4444 !important; }
    body.dth-config-page .dth-color-swatch-picker input[value="orange"] + label.fi-btn { background:#f97316 !important; }
    body.dth-config-page .dth-color-swatch-picker input[value="yellow"] + label.fi-btn { background:#eab308 !important; }
    body.dth-config-page .dth-color-swatch-picker input[value="lime"] + label.fi-btn { background:#84cc16 !important; }
    body.dth-config-page .dth-color-swatch-picker input[value="emerald"] + label.fi-btn { background:#10b981 !important; }
    body.dth-config-page .dth-color-swatch-picker input[value="teal"] + label.fi-btn { background:#14b8a6 !important; }
    body.dth-config-page .dth-color-swatch-picker input[value="cyan"] + label.fi-btn { background:#06b6d4 !important; }
    body.dth-config-page .dth-color-swatch-picker input[value="blue"] + label.fi-btn { background:#3b82f6 !important; }
    body.dth-config-page .dth-color-swatch-picker input[value="indigo"] + label.fi-btn { background:#6366f1 !important; }
    body.dth-config-page .dth-color-swatch-picker input[value="violet"] + label.fi-btn { background:#8b5cf6 !important; }
    body.dth-config-page .dth-color-swatch-picker input[value="purple"] + label.fi-btn { background:#a855f7 !important; }
    body.dth-config-page .dth-color-swatch-picker input[value="fuchsia"] + label.fi-btn { background:#d946ef !important; }
    body.dth-config-page .dth-color-swatch-picker input[value="pink"] + label.fi-btn { background:#ec4899 !important; }
    body.dth-config-page .dth-color-swatch-picker input[value="rose"] + label.fi-btn { background:#f43f5e !important; }

    /* Permission picker becomes a clean card grid while keeping Filament's native relationship handling. */
    body.dth-config-page .dth-config-form .fi-fo-checkbox-list {
        gap:.65rem !important;
    }
    body.dth-config-page .dth-config-form .fi-fo-checkbox-list-option-label {
        min-height:2.65rem;
        align-items:flex-start;
        padding:.58rem .65rem;
        border:1px solid var(--dth-config-border);
        border-radius:.68rem;
        background:var(--dth-config-panel-soft);
        transition:border-color .12s ease, background .12s ease, transform .12s ease;
    }
    body.dth-config-page .dth-config-form .fi-fo-checkbox-list-option-label:hover {
        border-color:rgba(var(--dth-config-accent),.30);
        background:rgba(var(--dth-config-accent),.035);
        transform:translateY(-1px);
    }
    body.dth-config-page .dth-config-form .fi-fo-checkbox-list-option-label > span:last-child {
        font-size:.76rem !important;
        line-height:1.4;
    }

    /* Hub cards now look like admin launchers, not documentation blocks. */
    body.dth-config-page .dth-config-shell { gap:.9rem !important; }
    body.dth-config-page .dth-config-toolbar {
        padding:.8rem .95rem !important;
        background:var(--dth-config-panel-soft) !important;
        border:1px solid var(--dth-config-border) !important;
        border-radius:.8rem !important;
    }
    body.dth-config-page .dth-config-launch-card {
        min-height:5.35rem !important;
        border:1px solid var(--dth-config-border) !important;
        border-radius:.9rem !important;
        background:var(--dth-config-panel) !important;
        box-shadow:0 8px 20px rgba(15,23,42,.035);
    }
    body.dth-config-page .dth-config-launch-card:hover {
        border-color:rgba(var(--dth-config-accent),.30) !important;
        box-shadow:0 14px 30px rgba(15,23,42,.065) !important;
    }
    body.dth-config-page .dth-config-launch-card__icon {
        width:2.45rem !important;
        height:2.45rem !important;
        border-radius:.7rem !important;
        background:linear-gradient(145deg,rgba(var(--dth-config-accent),.23),rgba(var(--dth-config-accent),.075)) !important;
        color:rgb(var(--dth-config-accent)) !important;
    }

    @media (max-width: 767px) {
        body.dth-config-page .dth-config-form .fi-section-content { padding:.85rem !important; }
        body.dth-config-page .dth-config-toggle-wrap { min-height:auto; }
        body.dth-config-page .dth-color-swatch-picker label.fi-btn { width:2.35rem !important; min-width:2.35rem !important; height:2.35rem !important; min-height:2.35rem !important; }
    }

</style>


<script>
(function () {
    function applySwatchTooltips(root) {
        (root || document).querySelectorAll('.dth-color-swatch-picker .fi-btn').forEach(function (button) {
            if (button.getAttribute('title')) return;
            var label = (button.textContent || '').replace(/\s+/g, ' ').trim();
            if (label) {
                button.setAttribute('title', label);
                button.setAttribute('aria-label', label);
            }
        });
    }

    function markConfigurationSidebar() {
        document.querySelectorAll('.fi-sidebar-group').forEach(function (group) {
            var configLink = group.querySelector('a[href*="/departments"], a[href*="/positions"], a[href*="/staff"]');
            group.classList.toggle('dth-config-sidebar-scope', Boolean(configLink));
        });
    }

    function bootSwatches() {
        markConfigurationSidebar();
        applySwatchTooltips(document);
        if (window.__dthSwatchObserver) return;
        window.__dthSwatchObserver = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) applySwatchTooltips(node);
                });
            });
        });
        window.__dthSwatchObserver.observe(document.body, { childList: true, subtree: true });
    }

    document.addEventListener('DOMContentLoaded', bootSwatches);
    document.addEventListener('livewire:navigated', bootSwatches);
    if (document.readyState !== 'loading') bootSwatches();
})();
</script>

<script>
(function () {
    const configurationFragments = [
        '/admin/configuration/',
        '/admin/departments',
        '/admin/positions',
        '/admin/staff',
        '/admin/users',
        '/admin/security/rbac-roles',
        '/admin/ui-badge-styles'
    ];

    function markConfigurationPage() {
        const path = window.location.pathname || '';
        let activeConfigurationGroup = false;

        document.querySelectorAll('.fi-sidebar-group').forEach(function (group) {
            const isConfigGroup = Boolean(group.querySelector('a[href*="/configuration/"], a[href*="/departments"], a[href*="/positions"], a[href*="/staff"], a[href*="/users"], a[href*="/ui-badge-styles"]'));
            group.classList.toggle('dth-config-sidebar-scope', isConfigGroup);
            if (isConfigGroup && group.querySelector('.fi-sidebar-item.fi-active')) {
                activeConfigurationGroup = true;
            }
        });

        const isConfigurationPath = configurationFragments.some((fragment) => path.indexOf(fragment) !== -1);
        document.body.classList.toggle('dth-config-page', isConfigurationPath || activeConfigurationGroup);
    }

    function enhanceConfigurationSwatches(root) {
        (root || document).querySelectorAll('.dth-color-swatch-picker label.fi-btn').forEach(function (button) {
            const input = button.previousElementSibling;
            if (!input) return;
            const hiddenLabel = (button.textContent || '').replace(/\s+/g, ' ').trim();
            if (hiddenLabel) {
                button.title = hiddenLabel;
                button.setAttribute('aria-label', hiddenLabel);
            }
        });
    }

    function bootConfigurationChrome() {
        markConfigurationPage();
        enhanceConfigurationSwatches(document);
    }

    document.addEventListener('DOMContentLoaded', bootConfigurationChrome);
    document.addEventListener('livewire:navigated', bootConfigurationChrome);
    document.addEventListener('livewire:initialized', bootConfigurationChrome);
    if (document.readyState !== 'loading') bootConfigurationChrome();
})();
</script>
