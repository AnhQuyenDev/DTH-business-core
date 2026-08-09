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
</style>
