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

</style>
