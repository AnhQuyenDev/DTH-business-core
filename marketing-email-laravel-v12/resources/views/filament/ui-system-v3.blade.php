{{--
    DTH UI/UX Design System V3
    --------------------------------------------------------------------------
    A presentation-only layer for the complete Filament admin panel.
    It deliberately uses existing Filament markup and Livewire events so no
    resource query, validation rule, authorization rule, or workflow changes.
--}}
<style>
    :root {
        --dth-v3-canvas: 248 250 252;
        --dth-v3-canvas-deep: 241 245 249;
        --dth-v3-panel: 255 255 255;
        --dth-v3-panel-soft: 248 250 252;
        --dth-v3-panel-raised: 255 255 255;
        --dth-v3-border: 226 232 240;
        --dth-v3-border-strong: 203 213 225;
        --dth-v3-text: 15 23 42;
        --dth-v3-text-soft: 71 85 105;
        --dth-v3-text-muted: 100 116 139;
        --dth-v3-shadow: 15 23 42;
        --dth-v3-accent: 245 158 11;
        --dth-v3-accent-strong: 217 119 6;
        --dth-v3-accent-soft: 255 247 237;
        --dth-v3-danger: 239 68 68;
        --dth-v3-success: 16 185 129;
        --dth-v3-radius-sm: .65rem;
        --dth-v3-radius-md: .9rem;
        --dth-v3-radius-lg: 1.1rem;
        --dth-v3-transition: 150ms cubic-bezier(.2, .8, .2, 1);
    }

    .dark {
        --dth-v3-canvas: 7 9 13;
        --dth-v3-canvas-deep: 4 6 10;
        --dth-v3-panel: 20 22 27;
        --dth-v3-panel-soft: 25 27 33;
        --dth-v3-panel-raised: 29 32 39;
        --dth-v3-border: 54 58 68;
        --dth-v3-border-strong: 75 80 92;
        --dth-v3-text: 248 250 252;
        --dth-v3-text-soft: 203 213 225;
        --dth-v3-text-muted: 148 163 184;
        --dth-v3-shadow: 0 0 0;
        --dth-v3-accent-soft: 56 38 8;
    }

    body[data-dth-module="email"] {
        --dth-v3-accent: 14 165 233;
        --dth-v3-accent-strong: 2 132 199;
        --dth-v3-accent-soft: 240 249 255;
    }

    body[data-dth-module="marketing"] {
        --dth-v3-accent: 168 85 247;
        --dth-v3-accent-strong: 147 51 234;
        --dth-v3-accent-soft: 250 245 255;
    }

    body[data-dth-module="crm"] {
        --dth-v3-accent: 59 130 246;
        --dth-v3-accent-strong: 37 99 235;
        --dth-v3-accent-soft: 239 246 255;
    }

    body[data-dth-module="sales"] {
        --dth-v3-accent: 245 158 11;
        --dth-v3-accent-strong: 217 119 6;
        --dth-v3-accent-soft: 255 247 237;
    }

    body[data-dth-module="finance"] {
        --dth-v3-accent: 16 185 129;
        --dth-v3-accent-strong: 5 150 105;
        --dth-v3-accent-soft: 236 253 245;
    }

    body[data-dth-module="customer-care"] {
        --dth-v3-accent: 244 63 94;
        --dth-v3-accent-strong: 225 29 72;
        --dth-v3-accent-soft: 255 241 242;
    }

    body[data-dth-module="configuration"] {
        --dth-v3-accent: 245 158 11;
        --dth-v3-accent-strong: 217 119 6;
        --dth-v3-accent-soft: 255 247 237;
    }

    body[data-dth-module="system"] {
        --dth-v3-accent: 100 116 139;
        --dth-v3-accent-strong: 71 85 105;
        --dth-v3-accent-soft: 248 250 252;
    }

    .dark body[data-dth-module="email"] { --dth-v3-accent-soft: 7 38 55; }
    .dark body[data-dth-module="marketing"] { --dth-v3-accent-soft: 43 24 65; }
    .dark body[data-dth-module="crm"] { --dth-v3-accent-soft: 18 39 71; }
    .dark body[data-dth-module="sales"] { --dth-v3-accent-soft: 56 38 8; }
    .dark body[data-dth-module="finance"] { --dth-v3-accent-soft: 8 47 39; }
    .dark body[data-dth-module="customer-care"] { --dth-v3-accent-soft: 62 22 33; }
    .dark body[data-dth-module="configuration"] { --dth-v3-accent-soft: 56 38 8; }
    .dark body[data-dth-module="system"] { --dth-v3-accent-soft: 30 41 59; }

    /* ---------------------------------------------------------------------
       Application canvas and page hierarchy
       ------------------------------------------------------------------ */
    body.fi-body {
        color: rgb(var(--dth-v3-text));
        background:
            radial-gradient(circle at 82% -10%, rgba(var(--dth-v3-accent), .075), transparent 28rem),
            linear-gradient(180deg, rgb(var(--dth-v3-canvas)) 0%, rgb(var(--dth-v3-canvas-deep)) 100%);
    }

    .dark body.fi-body {
        background:
            radial-gradient(circle at 82% -10%, rgba(var(--dth-v3-accent), .11), transparent 34rem),
            linear-gradient(180deg, rgb(var(--dth-v3-canvas)) 0%, rgb(var(--dth-v3-canvas-deep)) 100%);
    }

    .fi-layout {
        min-height: 100vh;
    }

    .fi-main-ctn {
        background: transparent;
    }

    .fi-main {
        width: 100%;
        max-width: 100%;
        padding-top: 1.35rem !important;
        padding-bottom: 5rem !important;
    }

    @media (min-width: 1024px) {
        .fi-main {
            padding-inline: clamp(1.4rem, 2.2vw, 2.5rem) !important;
        }
    }

    .fi-page {
        isolation: isolate;
    }

    .fi-header {
        position: relative;
        gap: 1rem !important;
        padding-bottom: .4rem;
    }

    .fi-header::after {
        position: absolute;
        right: 0;
        bottom: -.15rem;
        left: 0;
        height: 1px;
        content: "";
        background: linear-gradient(90deg, rgba(var(--dth-v3-accent), .45), rgba(var(--dth-v3-border), .45) 22%, transparent 72%);
        pointer-events: none;
    }

    .fi-header-heading {
        color: rgb(var(--dth-v3-text)) !important;
        font-size: clamp(1.55rem, 2vw, 2rem) !important;
        font-weight: 780 !important;
        letter-spacing: -.035em !important;
        line-height: 1.15 !important;
    }

    .fi-header-subheading {
        max-width: 58rem;
        color: rgb(var(--dth-v3-text-muted)) !important;
        font-size: .91rem !important;
        line-height: 1.6 !important;
    }

    .fi-breadcrumbs-item-label {
        color: rgb(var(--dth-v3-text-muted)) !important;
        font-size: .76rem !important;
        font-weight: 600 !important;
    }

    .fi-breadcrumbs-item:last-child .fi-breadcrumbs-item-label {
        color: rgba(var(--dth-v3-accent-strong), .95) !important;
    }

    /* ---------------------------------------------------------------------
       Top bar
       ------------------------------------------------------------------ */
    .fi-topbar > nav {
        border-bottom: 1px solid rgba(var(--dth-v3-border), .82) !important;
        background: rgba(var(--dth-v3-panel), .86) !important;
        box-shadow: 0 1px 0 rgba(255, 255, 255, .35), 0 10px 28px rgba(var(--dth-v3-shadow), .035) !important;
        backdrop-filter: blur(18px) saturate(1.25);
    }

    .dark .fi-topbar > nav {
        background: rgba(var(--dth-v3-panel), .82) !important;
        box-shadow: 0 1px 0 rgba(255, 255, 255, .025), 0 10px 32px rgba(0, 0, 0, .24) !important;
    }

    .fi-topbar-open-sidebar-btn,
    .fi-topbar-close-sidebar-btn,
    .fi-topbar-database-notifications-btn,
    .fi-topbar .fi-icon-btn {
        border: 1px solid transparent;
        border-radius: .7rem !important;
        transition: color var(--dth-v3-transition), background var(--dth-v3-transition), border-color var(--dth-v3-transition), transform var(--dth-v3-transition);
    }

    .fi-topbar .fi-icon-btn:hover,
    .fi-topbar-open-sidebar-btn:hover,
    .fi-topbar-close-sidebar-btn:hover {
        color: rgb(var(--dth-v3-accent)) !important;
        border-color: rgba(var(--dth-v3-accent), .2);
        background: rgba(var(--dth-v3-accent), .08) !important;
        transform: translateY(-1px);
    }

    /* ---------------------------------------------------------------------
       Sidebar — one visual grammar for every module
       ------------------------------------------------------------------ */
    .fi-sidebar {
        border-right: 1px solid rgba(var(--dth-v3-border), .86) !important;
        background:
            linear-gradient(180deg, rgba(var(--dth-v3-panel), .98), rgba(var(--dth-v3-panel-soft), .94)) !important;
        box-shadow: 14px 0 34px rgba(var(--dth-v3-shadow), .035) !important;
    }

    .dark .fi-sidebar {
        background:
            radial-gradient(circle at 25% 0%, rgba(var(--dth-v3-accent), .055), transparent 18rem),
            linear-gradient(180deg, rgba(var(--dth-v3-panel), .98), rgba(var(--dth-v3-canvas-deep), .98)) !important;
        box-shadow: 14px 0 36px rgba(0, 0, 0, .24) !important;
    }

    .fi-sidebar-header {
        min-height: 4rem;
        border-bottom: 1px solid rgba(var(--dth-v3-border), .72) !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    .fi-sidebar-nav {
        padding: .9rem .75rem 1.5rem !important;
    }

    .fi-sidebar-nav-groups {
        gap: .65rem !important;
    }

    .fi-sidebar-group {
        --dth-group-accent: var(--dth-v3-text-muted);
        position: relative;
        border-radius: .85rem;
        transition: background var(--dth-v3-transition);
    }

    .fi-sidebar-group[data-dth-module="email"] { --dth-group-accent: 14 165 233; }
    .fi-sidebar-group[data-dth-module="marketing"] { --dth-group-accent: 168 85 247; }
    .fi-sidebar-group[data-dth-module="crm"] { --dth-group-accent: 59 130 246; }
    .fi-sidebar-group[data-dth-module="sales"] { --dth-group-accent: 245 158 11; }
    .fi-sidebar-group[data-dth-module="finance"] { --dth-group-accent: 16 185 129; }
    .fi-sidebar-group[data-dth-module="customer-care"] { --dth-group-accent: 244 63 94; }
    .fi-sidebar-group[data-dth-module="configuration"] { --dth-group-accent: 245 158 11; }
    .fi-sidebar-group[data-dth-module="system"] { --dth-group-accent: 100 116 139; }

    .fi-sidebar-group[data-dth-active="true"] {
        background: linear-gradient(135deg, rgba(var(--dth-v3-accent), .055), transparent 72%);
    }

    .fi-sidebar-group-button {
        min-height: 2.7rem;
        padding: .55rem .68rem !important;
        border: 1px solid transparent;
        border-radius: .72rem !important;
        transition: background var(--dth-v3-transition), border-color var(--dth-v3-transition), color var(--dth-v3-transition);
    }

    .fi-sidebar-group-button:hover {
        border-color: rgba(var(--dth-v3-border-strong), .72);
        background: rgba(var(--dth-v3-panel-raised), .76) !important;
    }

    .fi-sidebar-group[data-dth-active="true"] > .fi-sidebar-group-button,
    .fi-sidebar-group[data-dth-active="true"] > div > .fi-sidebar-group-button {
        color: rgb(var(--dth-v3-accent)) !important;
        border-color: rgba(var(--dth-v3-accent), .16);
        background: rgba(var(--dth-v3-accent), .075) !important;
    }

    .fi-sidebar-group-label {
        color: rgb(var(--dth-v3-text-soft)) !important;
        font-size: .7rem !important;
        font-weight: 780 !important;
        letter-spacing: .055em !important;
        text-transform: uppercase;
    }

    .fi-sidebar-group-icon {
        width: 1.8rem !important;
        height: 1.8rem !important;
        padding: .35rem;
        border: 1px solid rgba(var(--dth-v3-border), .78);
        border-radius: .54rem;
        color: rgb(var(--dth-group-accent)) !important;
        background: rgba(var(--dth-group-accent), .06);
    }

    .fi-sidebar-group[data-dth-active="true"] .fi-sidebar-group-icon {
        color: rgb(var(--dth-v3-accent)) !important;
        border-color: rgba(var(--dth-v3-accent), .2);
        background: rgba(var(--dth-v3-accent), .1);
    }

    .fi-sidebar-group-items {
        position: relative;
        gap: .18rem !important;
        margin-top: .28rem !important;
        padding: 0 0 .36rem .46rem !important;
    }

    .fi-sidebar-group-items::before {
        position: absolute;
        top: .2rem;
        bottom: .55rem;
        left: .77rem;
        width: 1px;
        content: "";
        background: linear-gradient(180deg, rgba(var(--dth-v3-accent), .38), rgba(var(--dth-v3-border), .72));
    }

    .fi-sidebar-item {
        position: relative;
        z-index: 1;
    }

    .fi-sidebar-item-button {
        min-height: 2.45rem !important;
        padding: .46rem .62rem .46rem .78rem !important;
        border: 1px solid transparent;
        border-radius: .68rem !important;
        transition: transform var(--dth-v3-transition), color var(--dth-v3-transition), background var(--dth-v3-transition), border-color var(--dth-v3-transition), box-shadow var(--dth-v3-transition);
    }

    .fi-sidebar-item-button:hover {
        color: rgb(var(--dth-v3-text)) !important;
        border-color: rgba(var(--dth-v3-border-strong), .62);
        background: rgba(var(--dth-v3-panel-raised), .82) !important;
        transform: translateX(2px);
    }

    .fi-sidebar-item.fi-active > .fi-sidebar-item-button {
        color: rgb(var(--dth-v3-accent)) !important;
        border-color: rgba(var(--dth-v3-accent), .24) !important;
        background: linear-gradient(90deg, rgba(var(--dth-v3-accent), .16), rgba(var(--dth-v3-accent), .055)) !important;
        box-shadow: inset 3px 0 0 rgb(var(--dth-v3-accent)), 0 5px 16px rgba(var(--dth-v3-accent), .06) !important;
        transform: none;
    }

    .fi-sidebar-item-label {
        font-size: .84rem !important;
        font-weight: 620 !important;
    }

    .fi-sidebar-item.fi-active .fi-sidebar-item-label {
        font-weight: 750 !important;
    }

    .fi-sidebar-item-icon {
        width: 1.65rem !important;
        height: 1.65rem !important;
        padding: .31rem;
        border: 1px solid rgba(var(--dth-v3-border), .7);
        border-radius: .48rem;
        color: rgb(var(--dth-v3-text-muted)) !important;
        background: rgba(var(--dth-v3-panel-soft), .66);
    }

    .fi-sidebar-item.fi-active .fi-sidebar-item-icon,
    .fi-sidebar-item-button:hover .fi-sidebar-item-icon {
        color: rgb(var(--dth-v3-accent)) !important;
        border-color: rgba(var(--dth-v3-accent), .2);
        background: rgba(var(--dth-v3-accent), .09);
    }

    .fi-sidebar-item-badge-ctn .fi-badge {
        border-color: rgba(var(--dth-v3-accent), .18) !important;
        background: rgba(var(--dth-v3-accent), .1) !important;
        color: rgb(var(--dth-v3-accent-strong)) !important;
    }

    .fi-sidebar-footer {
        border-top: 1px solid rgba(var(--dth-v3-border), .72) !important;
    }

    /* ---------------------------------------------------------------------
       Cards, sections, tabs and widgets
       ------------------------------------------------------------------ */
    .fi-section,
    .fi-ta-ctn,
    .fi-fo-tabs,
    .fi-wi-widget > div,
    .fi-modal-window,
    .fi-dropdown-panel,
    .fi-global-search-results-ctn {
        border: 1px solid rgba(var(--dth-v3-border), .94) !important;
        border-radius: var(--dth-v3-radius-md) !important;
        background: rgba(var(--dth-v3-panel), .96) !important;
        box-shadow: 0 10px 30px rgba(var(--dth-v3-shadow), .052), 0 1px 2px rgba(var(--dth-v3-shadow), .035) !important;
    }

    .dark .fi-section,
    .dark .fi-ta-ctn,
    .dark .fi-fo-tabs,
    .dark .fi-wi-widget > div,
    .dark .fi-modal-window,
    .dark .fi-dropdown-panel,
    .dark .fi-global-search-results-ctn {
        background: linear-gradient(145deg, rgba(var(--dth-v3-panel-raised), .97), rgba(var(--dth-v3-panel), .98)) !important;
        box-shadow: 0 16px 38px rgba(0, 0, 0, .19), inset 0 1px 0 rgba(255, 255, 255, .025) !important;
    }

    .fi-section {
        overflow: hidden;
    }

    .fi-section-header {
        position: relative;
        min-height: 3.45rem;
        padding: .88rem 1rem !important;
        border-bottom: 1px solid rgba(var(--dth-v3-border), .78) !important;
        background: linear-gradient(90deg, rgba(var(--dth-v3-accent), .055), transparent 35%) !important;
    }

    .fi-section-header::before {
        position: absolute;
        top: .78rem;
        bottom: .78rem;
        left: 0;
        width: 3px;
        border-radius: 999px;
        content: "";
        background: rgb(var(--dth-v3-accent));
        opacity: .84;
    }

    .fi-section-header-heading {
        color: rgb(var(--dth-v3-text)) !important;
        font-size: .92rem !important;
        font-weight: 760 !important;
        letter-spacing: -.014em !important;
    }

    .fi-section-header-description {
        color: rgb(var(--dth-v3-text-muted)) !important;
        font-size: .78rem !important;
        line-height: 1.5 !important;
    }

    .fi-section-header-icon {
        width: 2rem !important;
        height: 2rem !important;
        padding: .42rem;
        border: 1px solid rgba(var(--dth-v3-accent), .16);
        border-radius: .58rem;
        color: rgb(var(--dth-v3-accent)) !important;
        background: rgba(var(--dth-v3-accent), .1);
    }

    .fi-section-content {
        padding: 1.05rem !important;
    }

    @media (min-width: 768px) {
        .fi-section-content {
            padding: 1.18rem 1.25rem !important;
        }
    }

    .fi-fo-tabs-tablist {
        gap: .2rem !important;
        padding: .35rem !important;
        border-bottom: 1px solid rgba(var(--dth-v3-border), .78) !important;
        background: rgba(var(--dth-v3-panel-soft), .7) !important;
    }

    .fi-fo-tabs-tab,
    .fi-tabs-item {
        min-height: 2.45rem;
        border-radius: .62rem !important;
        font-weight: 650 !important;
        transition: color var(--dth-v3-transition), background var(--dth-v3-transition), box-shadow var(--dth-v3-transition);
    }

    .fi-fo-tabs-tab[aria-selected="true"],
    .fi-tabs-item.fi-active {
        color: rgb(var(--dth-v3-accent)) !important;
        background: rgba(var(--dth-v3-accent), .1) !important;
        box-shadow: inset 0 0 0 1px rgba(var(--dth-v3-accent), .18) !important;
    }

    .fi-wi-stats-overview-stat {
        position: relative;
        overflow: hidden;
        min-height: 8rem;
        border-color: rgba(var(--dth-v3-border), .88) !important;
        border-radius: var(--dth-v3-radius-md) !important;
        background: linear-gradient(145deg, rgba(var(--dth-v3-panel), .98), rgba(var(--dth-v3-panel-soft), .94)) !important;
        box-shadow: 0 8px 24px rgba(var(--dth-v3-shadow), .045) !important;
        transition: transform var(--dth-v3-transition), border-color var(--dth-v3-transition), box-shadow var(--dth-v3-transition);
    }

    .fi-wi-stats-overview-stat::after {
        position: absolute;
        top: -1.75rem;
        right: -1.75rem;
        width: 6rem;
        height: 6rem;
        border-radius: 999px;
        content: "";
        background: radial-gradient(circle, rgba(var(--dth-v3-accent), .14), transparent 68%);
        pointer-events: none;
    }

    .fi-wi-stats-overview-stat:hover {
        border-color: rgba(var(--dth-v3-accent), .23) !important;
        box-shadow: 0 14px 30px rgba(var(--dth-v3-shadow), .07) !important;
        transform: translateY(-2px);
    }

    /* ---------------------------------------------------------------------
       Forms — rhythm, contrast and predictable responsive structure
       ------------------------------------------------------------------ */
    .fi-fo-component-ctn {
        gap: 1rem !important;
    }

    .fi-fo-field-wrp {
        min-width: 0;
    }

    .fi-fo-field-wrp-label {
        gap: .35rem !important;
        margin-bottom: .42rem !important;
    }

    .fi-fo-field-wrp-label label,
    .fi-fo-field-wrp-label span:not(.fi-badge) {
        color: rgb(var(--dth-v3-text-soft)) !important;
        font-size: .78rem !important;
        font-weight: 690 !important;
        letter-spacing: -.005em;
    }

    .fi-fo-field-wrp-required-mark {
        color: rgb(var(--dth-v3-danger)) !important;
    }

    .fi-input-wrp,
    .fi-select-input,
    .fi-fo-textarea textarea,
    .fi-fo-rich-editor,
    .fi-fo-markdown-editor,
    .fi-fo-tags-input,
    .fi-fo-key-value,
    .fi-fo-file-upload-input-ctn {
        border: 1px solid rgba(var(--dth-v3-border-strong), .82) !important;
        border-radius: var(--dth-v3-radius-sm) !important;
        background: rgba(var(--dth-v3-panel-soft), .72) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .28), 0 1px 2px rgba(var(--dth-v3-shadow), .025) !important;
        transition: border-color var(--dth-v3-transition), box-shadow var(--dth-v3-transition), background var(--dth-v3-transition) !important;
    }

    .dark .fi-input-wrp,
    .dark .fi-select-input,
    .dark .fi-fo-textarea textarea,
    .dark .fi-fo-rich-editor,
    .dark .fi-fo-markdown-editor,
    .dark .fi-fo-tags-input,
    .dark .fi-fo-key-value,
    .dark .fi-fo-file-upload-input-ctn {
        background: rgba(var(--dth-v3-panel-soft), .86) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .025) !important;
    }

    .fi-input-wrp:focus-within,
    .fi-select-input:focus,
    .fi-fo-textarea textarea:focus,
    .fi-fo-rich-editor:focus-within,
    .fi-fo-markdown-editor:focus-within,
    .fi-fo-tags-input:focus-within,
    .fi-fo-key-value:focus-within,
    .fi-fo-file-upload-input-ctn:focus-within {
        border-color: rgba(var(--dth-v3-accent), .72) !important;
        background: rgba(var(--dth-v3-panel), .98) !important;
        box-shadow: 0 0 0 3px rgba(var(--dth-v3-accent), .13), 0 4px 12px rgba(var(--dth-v3-accent), .055) !important;
    }

    .fi-input-wrp input,
    .fi-input,
    .fi-select-input,
    .fi-fo-textarea textarea {
        min-height: 2.62rem;
        color: rgb(var(--dth-v3-text)) !important;
        font-size: .86rem !important;
    }

    .fi-fo-textarea textarea {
        padding-top: .72rem !important;
        padding-bottom: .72rem !important;
        line-height: 1.6 !important;
    }

    .fi-input-wrp input::placeholder,
    .fi-input::placeholder,
    .fi-select-input:invalid,
    .fi-fo-textarea textarea::placeholder {
        color: rgb(var(--dth-v3-text-muted)) !important;
        opacity: .72;
    }

    .fi-input-wrp:has(input:disabled),
    .fi-input-wrp:has(input[readonly]),
    .fi-select-input:disabled,
    .fi-fo-textarea textarea:disabled,
    .fi-fo-textarea textarea[readonly] {
        border-style: dashed !important;
        border-color: rgba(var(--dth-v3-border-strong), .72) !important;
        background: rgba(var(--dth-v3-border), .2) !important;
        color: rgb(var(--dth-v3-text-muted)) !important;
        opacity: .9 !important;
    }

    .fi-fo-field-wrp-error-message {
        margin-top: .35rem !important;
        font-size: .75rem !important;
        font-weight: 600 !important;
    }

    .fi-fo-field-wrp-helper-text {
        color: rgb(var(--dth-v3-text-muted)) !important;
        font-size: .75rem !important;
        line-height: 1.55 !important;
    }

    .fi-fo-repeater {
        overflow: hidden;
        border: 1px solid rgba(var(--dth-v3-border), .9) !important;
        border-radius: var(--dth-v3-radius-md) !important;
        background: rgba(var(--dth-v3-panel-soft), .58) !important;
    }

    .fi-fo-repeater-item {
        border-color: rgba(var(--dth-v3-border), .86) !important;
        border-radius: .72rem !important;
        background: rgba(var(--dth-v3-panel), .9) !important;
        box-shadow: 0 4px 14px rgba(var(--dth-v3-shadow), .035) !important;
    }

    .fi-fo-repeater-item-header {
        border-bottom-color: rgba(var(--dth-v3-border), .75) !important;
        background: linear-gradient(90deg, rgba(var(--dth-v3-accent), .045), transparent) !important;
    }

    .fi-fo-checkbox-list-option-label,
    .fi-fo-radio-option-label {
        min-height: 2.45rem;
        padding: .52rem .62rem;
        border: 1px solid rgba(var(--dth-v3-border), .88);
        border-radius: .62rem;
        background: rgba(var(--dth-v3-panel-soft), .62);
        transition: border-color var(--dth-v3-transition), background var(--dth-v3-transition), transform var(--dth-v3-transition);
    }

    .fi-fo-checkbox-list-option-label:hover,
    .fi-fo-radio-option-label:hover {
        border-color: rgba(var(--dth-v3-accent), .28);
        background: rgba(var(--dth-v3-accent), .055);
        transform: translateY(-1px);
    }

    .fi-checkbox-input,
    .fi-radio-input {
        border-color: rgba(var(--dth-v3-border-strong), .9) !important;
    }

    .fi-fo-file-upload .filepond--root {
        margin-bottom: 0 !important;
    }

    .fi-fo-file-upload .filepond--panel-root {
        border: 1px dashed rgba(var(--dth-v3-border-strong), .9) !important;
        border-radius: .76rem !important;
        background: rgba(var(--dth-v3-panel-soft), .72) !important;
    }

    /* Hint icons and fallback tooltip trigger. */
    .fi-fo-field-wrp-hint-icon,
    .dth-helper-tooltip__trigger {
        display: inline-grid !important;
        width: 1.18rem !important;
        height: 1.18rem !important;
        place-items: center;
        border: 1px solid rgba(var(--dth-v3-border-strong), .75) !important;
        border-radius: 999px !important;
        color: rgb(var(--dth-v3-text-muted)) !important;
        background: rgba(var(--dth-v3-panel-soft), .92) !important;
        cursor: help;
        transition: color var(--dth-v3-transition), border-color var(--dth-v3-transition), background var(--dth-v3-transition), transform var(--dth-v3-transition);
    }

    .fi-fo-field-wrp-hint-icon:hover,
    .dth-helper-tooltip__trigger:hover,
    .dth-helper-tooltip__trigger:focus-visible {
        color: rgb(var(--dth-v3-accent)) !important;
        border-color: rgba(var(--dth-v3-accent), .45) !important;
        background: rgba(var(--dth-v3-accent), .1) !important;
        outline: none;
        transform: translateY(-1px);
    }

    .dth-helper-tooltip__trigger {
        margin-inline-start: .35rem;
        font-size: .72rem;
        font-weight: 800;
        line-height: 1;
    }

    .dth-helper-tooltip__source {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
    }

    /* Explicit textual state beside every custom toggle. */
    .dth-state-toggle {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: .58rem;
    }

    .dth-state-toggle [role="switch"] {
        flex: 0 0 auto;
    }

    .dth-state-toggle__label {
        display: inline-flex;
        align-items: center;
        min-height: 1.75rem;
        padding: .28rem .58rem;
        border: 1px solid rgba(var(--dth-v3-border-strong), .76);
        border-radius: 999px;
        color: rgb(var(--dth-v3-text-muted));
        background: rgba(var(--dth-v3-panel-soft), .88);
        font-size: .73rem;
        font-weight: 700;
        line-height: 1.15;
        transition: color var(--dth-v3-transition), border-color var(--dth-v3-transition), background var(--dth-v3-transition);
    }

    .dth-state-toggle[data-dth-state="on"] .dth-state-toggle__label {
        color: rgb(var(--dth-v3-accent-strong));
        border-color: rgba(var(--dth-v3-accent), .28);
        background: rgba(var(--dth-v3-accent), .1);
    }

    .dth-state-toggle[data-dth-state="off"] .dth-state-toggle__label {
        color: rgb(var(--dth-v3-text-muted));
    }

    /* ---------------------------------------------------------------------
       Buttons and actions
       ------------------------------------------------------------------ */
    .fi-btn {
        min-height: 2.48rem;
        gap: .45rem !important;
        padding-inline: .86rem !important;
        border-radius: var(--dth-v3-radius-sm) !important;
        font-size: .79rem !important;
        font-weight: 720 !important;
        letter-spacing: -.006em;
        box-shadow: 0 1px 2px rgba(var(--dth-v3-shadow), .06) !important;
        transition: transform var(--dth-v3-transition), box-shadow var(--dth-v3-transition), border-color var(--dth-v3-transition), background var(--dth-v3-transition), color var(--dth-v3-transition) !important;
    }

    .fi-btn:hover:not(:disabled) {
        box-shadow: 0 7px 16px rgba(var(--dth-v3-shadow), .09) !important;
        transform: translateY(-1px);
    }

    .fi-btn:active:not(:disabled) {
        transform: translateY(0) scale(.985);
    }

    .fi-btn.fi-color-primary {
        border-color: rgba(var(--primary-600), .24) !important;
        background: linear-gradient(135deg, rgb(var(--primary-500)), rgb(var(--primary-600))) !important;
        box-shadow: 0 6px 16px rgba(var(--primary-500), .19) !important;
    }

    .fi-btn.fi-color-primary:hover:not(:disabled) {
        background: linear-gradient(135deg, rgb(var(--primary-400)), rgb(var(--primary-600))) !important;
        box-shadow: 0 9px 20px rgba(var(--primary-500), .25) !important;
    }

    .fi-btn.fi-color-gray {
        border-color: rgba(var(--dth-v3-border-strong), .9) !important;
        color: rgb(var(--dth-v3-text-soft)) !important;
        background: rgba(var(--dth-v3-panel-raised), .86) !important;
    }

    .fi-btn.fi-color-gray:hover:not(:disabled) {
        color: rgb(var(--dth-v3-text)) !important;
        border-color: rgba(var(--dth-v3-accent), .26) !important;
        background: rgba(var(--dth-v3-accent), .055) !important;
    }

    .fi-btn.fi-color-danger {
        box-shadow: 0 5px 14px rgba(var(--dth-v3-danger), .14) !important;
    }

    .fi-btn-icon,
    .dth-auto-icon {
        width: 1rem !important;
        height: 1rem !important;
        flex: 0 0 auto;
        stroke-width: 1.8;
    }

    .fi-icon-btn {
        border-radius: .62rem !important;
        transition: color var(--dth-v3-transition), background var(--dth-v3-transition), transform var(--dth-v3-transition);
    }

    .fi-icon-btn:hover {
        background: rgba(var(--dth-v3-accent), .085) !important;
        transform: translateY(-1px);
    }

    .dth-page-form-actions {
        position: sticky;
        z-index: 20;
        bottom: .8rem;
        width: fit-content;
        max-width: 100%;
        padding: .55rem;
        border: 1px solid rgba(var(--dth-v3-border), .94);
        border-radius: .82rem;
        background: rgba(var(--dth-v3-panel), .9);
        box-shadow: 0 14px 34px rgba(var(--dth-v3-shadow), .12);
        backdrop-filter: blur(16px) saturate(1.2);
    }

    .dark .dth-page-form-actions {
        background: rgba(var(--dth-v3-panel), .88);
        box-shadow: 0 16px 36px rgba(0, 0, 0, .3);
    }

    /* ---------------------------------------------------------------------
       Data tables — readable density and clear interactions
       ------------------------------------------------------------------ */
    .fi-ta-ctn {
        overflow: hidden;
    }

    .fi-ta-header,
    .fi-ta-header-toolbar {
        border-bottom: 1px solid rgba(var(--dth-v3-border), .82) !important;
        background: linear-gradient(180deg, rgba(var(--dth-v3-panel), .98), rgba(var(--dth-v3-panel-soft), .84)) !important;
    }

    .fi-ta-header {
        padding: .92rem 1rem !important;
    }

    .fi-ta-header-heading {
        color: rgb(var(--dth-v3-text)) !important;
        font-weight: 760 !important;
        letter-spacing: -.015em;
    }

    .fi-ta-header-description {
        color: rgb(var(--dth-v3-text-muted)) !important;
    }

    .fi-ta-content {
        overflow-x: auto;
        scrollbar-width: thin;
        scrollbar-color: rgba(var(--dth-v3-border-strong), .9) transparent;
    }

    .fi-ta-table {
        min-width: 100%;
        border-collapse: separate !important;
        border-spacing: 0 !important;
    }

    .fi-ta-header-cell {
        position: sticky;
        z-index: 4;
        top: 0;
        border-bottom: 1px solid rgba(var(--dth-v3-border-strong), .76) !important;
        background: rgba(var(--dth-v3-panel-soft), .97) !important;
        backdrop-filter: blur(10px);
    }

    .fi-ta-header-cell-label {
        color: rgb(var(--dth-v3-text-soft)) !important;
        font-size: .7rem !important;
        font-weight: 780 !important;
        letter-spacing: .035em !important;
        text-transform: uppercase;
    }

    .fi-ta-row {
        background: rgba(var(--dth-v3-panel), .62);
        transition: background var(--dth-v3-transition), box-shadow var(--dth-v3-transition) !important;
    }

    .fi-ta-row:nth-child(even) {
        background: rgba(var(--dth-v3-panel-soft), .56);
    }

    .fi-ta-row:hover {
        position: relative;
        z-index: 2;
        background: rgba(var(--dth-v3-accent), .055) !important;
        box-shadow: inset 3px 0 0 rgba(var(--dth-v3-accent), .75);
    }

    .fi-ta-row > td {
        border-bottom-color: rgba(var(--dth-v3-border), .68) !important;
    }

    .fi-ta-cell {
        color: rgb(var(--dth-v3-text-soft));
        vertical-align: middle !important;
    }

    .fi-ta-text-item-label {
        line-height: 1.45 !important;
    }

    .fi-ta-filters-above-content-ctn,
    .fi-ta-filters-form {
        border-color: rgba(var(--dth-v3-border), .84) !important;
        background: rgba(var(--dth-v3-panel-soft), .76) !important;
    }

    .fi-ta-search-field .fi-input-wrp {
        min-width: min(100%, 17rem);
    }

    .fi-ta-pagination {
        border-top: 1px solid rgba(var(--dth-v3-border), .78) !important;
        background: rgba(var(--dth-v3-panel-soft), .74) !important;
    }

    .fi-pagination-item-button {
        border-radius: .55rem !important;
    }

    .fi-ta-empty-state {
        min-height: 16rem;
        background:
            radial-gradient(circle at 50% 40%, rgba(var(--dth-v3-accent), .075), transparent 11rem),
            rgba(var(--dth-v3-panel), .75);
    }

    .fi-ta-empty-state-icon-ctn {
        border: 1px solid rgba(var(--dth-v3-accent), .15);
        background: rgba(var(--dth-v3-accent), .085) !important;
    }

    .fi-ta-empty-state-icon {
        color: rgb(var(--dth-v3-accent)) !important;
    }

    .fi-ta-empty-state-heading {
        color: rgb(var(--dth-v3-text)) !important;
        font-weight: 760 !important;
    }

    /* Badges preserve semantic Filament colors while gaining consistent form. */
    .fi-badge {
        min-height: 1.55rem;
        padding-inline: .55rem !important;
        border: 1px solid currentColor;
        border-radius: 999px !important;
        font-size: .69rem !important;
        font-weight: 720 !important;
        letter-spacing: .006em;
    }

    /* ---------------------------------------------------------------------
       Modals, dropdowns, notifications and empty states
       ------------------------------------------------------------------ */
    .fi-modal-close-overlay {
        background: rgba(2, 6, 23, .64) !important;
        backdrop-filter: blur(5px);
    }

    .fi-modal-window {
        overflow: hidden;
        max-height: calc(100vh - 2rem);
    }

    .fi-modal-header {
        padding: 1.05rem 1.2rem !important;
        border-bottom: 1px solid rgba(var(--dth-v3-border), .82) !important;
        background: linear-gradient(90deg, rgba(var(--dth-v3-accent), .065), transparent 38%) !important;
    }

    .fi-modal-heading {
        color: rgb(var(--dth-v3-text)) !important;
        font-weight: 780 !important;
        letter-spacing: -.02em;
    }

    .fi-modal-description {
        color: rgb(var(--dth-v3-text-muted)) !important;
        line-height: 1.55 !important;
    }

    .fi-modal-content {
        padding: 1.1rem 1.2rem !important;
    }

    .fi-modal-footer {
        padding: .85rem 1.2rem !important;
        border-top: 1px solid rgba(var(--dth-v3-border), .82) !important;
        background: rgba(var(--dth-v3-panel-soft), .82) !important;
    }

    .fi-dropdown-list-item {
        min-height: 2.3rem;
        margin: .12rem .2rem;
        border-radius: .55rem !important;
        transition: background var(--dth-v3-transition), color var(--dth-v3-transition);
    }

    .fi-dropdown-list-item:hover {
        background: rgba(var(--dth-v3-accent), .075) !important;
    }

    /* ---------------------------------------------------------------------
       Authentication and account surfaces
       ------------------------------------------------------------------ */
    .fi-simple-layout {
        background:
            radial-gradient(circle at 15% 15%, rgba(var(--dth-v3-accent), .13), transparent 22rem),
            radial-gradient(circle at 85% 85%, rgba(var(--dth-v3-accent), .07), transparent 26rem),
            rgb(var(--dth-v3-canvas));
    }

    .fi-simple-main {
        border: 1px solid rgba(var(--dth-v3-border), .9) !important;
        border-radius: 1.15rem !important;
        background: rgba(var(--dth-v3-panel), .94) !important;
        box-shadow: 0 22px 52px rgba(var(--dth-v3-shadow), .13) !important;
        backdrop-filter: blur(20px);
    }

    /* ---------------------------------------------------------------------
       Small-screen safeguards
       ------------------------------------------------------------------ */
    @media (max-width: 767px) {
        .fi-main {
            padding-top: .9rem !important;
            padding-inline: .7rem !important;
        }

        .fi-header-heading {
            font-size: 1.45rem !important;
        }

        .fi-section-header,
        .fi-section-content,
        .fi-modal-header,
        .fi-modal-content,
        .fi-modal-footer {
            padding-inline: .85rem !important;
        }

        .fi-btn {
            min-height: 2.55rem;
        }

        .dth-page-form-actions {
            right: .5rem;
            bottom: .5rem;
            left: .5rem;
            width: auto;
            overflow-x: auto;
        }

        .fi-ta-header-cell {
            position: static;
        }
    }

    /* Strong and visible keyboard focus. */
    :where(a, button, input, select, textarea, [tabindex]):focus-visible {
        outline: 2px solid rgba(var(--dth-v3-accent), .9) !important;
        outline-offset: 2px !important;
    }

    @media (prefers-reduced-motion: reduce) {
        *,
        *::before,
        *::after {
            scroll-behavior: auto !important;
            transition-duration: .01ms !important;
            animation-duration: .01ms !important;
            animation-iteration-count: 1 !important;
        }
    }
</style>

<script>
(function () {
    'use strict';

    const MODULE_ALIASES = [
        { key: 'email', patterns: ['email'] },
        { key: 'marketing', patterns: ['marketing'] },
        { key: 'crm', patterns: ['crm'] },
        { key: 'sales', patterns: ['sales', 'kinh doanh'] },
        { key: 'finance', patterns: ['finance', 'tài chính', 'tai chinh'] },
        { key: 'customer-care', patterns: ['customer care', 'chăm sóc khách hàng', 'cham soc khach hang', 'cskh'] },
        { key: 'configuration', patterns: ['configuration', 'cấu hình', 'cau hinh'] },
        { key: 'system', patterns: ['system', 'hệ thống', 'he thong'] },
    ];

    const PATH_MODULES = [
        { key: 'email', fragments: ['/campaigns', '/campaign-reports', '/campaign-report', '/email-templates', '/email-template-categories', '/sending-accounts', '/sending-domains', '/suppression-entries'] },
        { key: 'marketing', fragments: ['/marketing-dashboard', '/marketing-campaigns', '/campaign-analytics', '/contact-lists', '/custom-fields', '/segments', '/tags', '/form-templates', '/landing-pages', '/landing-page-submissions'] },
        { key: 'crm', fragments: ['/companies', '/company-match-candidates', '/personal-contacts', '/business-contacts', '/contact-qualifications', '/leads', '/staff-dashboard'] },
        { key: 'sales', fragments: ['/sales-dashboard', '/opportunities', '/quotations', '/quotation-approvals', '/services', '/service-packages', '/service-products', '/price-books'] },
        { key: 'finance', fragments: ['/finance-dashboard', '/payments', '/payment-trackings', '/bank-accounts', '/revenue-report'] },
        { key: 'customer-care', fragments: ['/customer-service-dashboard', '/customer-care', '/customers', '/customer-distribution-batches', '/support-tickets'] },
        { key: 'configuration', fragments: ['/configuration/', '/departments', '/positions', '/staff', '/users', '/security/rbac-roles', '/ui-badge-styles', '/organization-access', '/access-control', '/role-permission-matrix', '/appearance-settings', '/company-settings'] },
        { key: 'system', fragments: ['/audit-logs'] },
    ];

    const toggleEnhancements = new WeakMap();

    const ICONS = {
        plus: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />',
        save: '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3.75h9.75l2.25 2.25v14.25H5.25V3.75h1.5Zm1.5 0v5.25h7.5V3.75M8.25 20.25v-6.75h7.5v6.75" />',
        back: '<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />',
        close: '<path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />',
        trash: '<path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673A2.25 2.25 0 0 1 15.916 21H8.084a2.25 2.25 0 0 1-2.244-1.327L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0V4.477c0-1.18-.91-2.165-2.09-2.202a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.202v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />',
        eye: '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Zm9.75 2.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z" />',
        pencil: '<path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.862 4.487ZM16.862 4.487 19.5 7.125M18 14.25v4.125A2.625 2.625 0 0 1 15.375 21H5.625A2.625 2.625 0 0 1 3 18.375V8.625A2.625 2.625 0 0 1 5.625 6H9.75" />',
        send: '<path stroke-linecap="round" stroke-linejoin="round" d="m6 12-3.75-2.25L21 3l-6.75 18L12 15m-6-3 6 3m-6-3 9-6" />',
        download: '<path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-4.5-6L12 15m0 0-4.5-4.5M12 15V3" />',
        check: '<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />',
        filter: '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5h16.5M6.75 9.75h10.5m-7.5 5.25h4.5" />',
        refresh: '<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992V4.356m-.572 5.572A8.25 8.25 0 1 0 19.5 15.75M7.977 14.652H2.985v4.992m.572-5.572A8.25 8.25 0 0 0 4.5 8.25" />',
        arrow: '<path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />',
    };

    function normalize(value) {
        return (value || '')
            .toString()
            .trim()
            .toLocaleLowerCase('vi')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd')
            .replace(/\s+/g, ' ');
    }

    function moduleFromLabel(label) {
        const normalized = normalize(label);

        for (const module of MODULE_ALIASES) {
            if (module.patterns.some((pattern) => normalized.includes(normalize(pattern)))) {
                return module.key;
            }
        }

        return null;
    }

    function moduleFromPath() {
        const path = (window.location.pathname || '').toLocaleLowerCase('vi');

        for (const module of PATH_MODULES) {
            if (module.fragments.some((fragment) => path.includes(fragment))) {
                return module.key;
            }
        }

        return null;
    }

    function markModules() {
        let activeModule = null;

        document.querySelectorAll('.fi-sidebar-group').forEach(function (group) {
            const labelNode = group.querySelector('.fi-sidebar-group-label');
            const module = moduleFromLabel(labelNode ? labelNode.textContent : '');
            const isActive = Boolean(group.querySelector('.fi-sidebar-item.fi-active'));

            if (module) {
                group.dataset.dthModule = module;
            }

            group.dataset.dthActive = isActive ? 'true' : 'false';

            if (isActive && module) {
                activeModule = module;
            }
        });

        document.body.dataset.dthModule = activeModule || moduleFromPath() || 'configuration';
    }

    function findSwitch(source) {
        if (source.matches && source.matches('[role="switch"]')) {
            return source;
        }

        return source.querySelector('[role="switch"], button[aria-checked], input[type="checkbox"]');
    }

    function switchIsOn(toggle) {
        if (!toggle) return false;
        if (toggle.matches('input[type="checkbox"]')) return Boolean(toggle.checked);
        return toggle.getAttribute('aria-checked') === 'true' || toggle.dataset.state === 'on';
    }

    function stateLabelFingerprint(value) {
        return normalize(value)
            .replace(/[^a-z0-9\s]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function findNativeToggleLabel(field, toggle, source) {
        let label = null;

        if (toggle.id) {
            try {
                label = field.querySelector(`label[for="${CSS.escape(toggle.id)}"]`);
            } catch (error) {
                label = null;
            }
        }

        if (!label) {
            label = Array.from(field.querySelectorAll('label')).find(function (candidate) {
                return !candidate.closest('.dth-helper-tooltip__trigger')
                    && !candidate.classList.contains('dth-state-toggle__label')
                    && Boolean((candidate.textContent || '').trim());
            }) || null;
        }

        if (!label && source !== field) {
            label = Array.from(source.querySelectorAll('label')).find(function (candidate) {
                return !candidate.classList.contains('dth-state-toggle__label')
                    && Boolean((candidate.textContent || '').trim());
            }) || null;
        }

        return label;
    }

    function enhanceStateToggle(source) {
        if (!source) return;

        const previous = toggleEnhancements.get(source);
        if (previous?.label?.isConnected) return;
        if (previous) {
            previous.observer.disconnect();
            previous.toggle.removeEventListener('change', previous.update);
            previous.toggle.removeEventListener('click', previous.clickHandler);
            toggleEnhancements.delete(source);
        }

        const onLabel = source.dataset.dthOnLabel;
        const offLabel = source.dataset.dthOffLabel;
        const toggle = findSwitch(source);

        if (!onLabel || !offLabel || !toggle) return;

        const field = source.closest('.fi-fo-field-wrp') || source;
        const visualRow = source.querySelector('.fi-fo-toggle') || toggle.parentElement || source;
        const nativeLabel = findNativeToggleLabel(field, toggle, source);
        const nativeText = stateLabelFingerprint(nativeLabel?.textContent || '');
        const onText = stateLabelFingerprint(onLabel);
        const offText = stateLabelFingerprint(offLabel);
        const nativeIsStateLabel = Boolean(nativeText && (nativeText === onText || nativeText === offText));

        field.querySelectorAll('.dth-state-toggle__label[data-dth-generated="true"]').forEach(function (duplicate) {
            duplicate.remove();
        });
        field.querySelectorAll('.dth-state-toggle__native-label--sr-only').forEach(function (labelNode) {
            labelNode.classList.remove('dth-state-toggle__native-label--sr-only');
        });

        const label = document.createElement(toggle.id ? 'label' : 'span');
        label.className = 'dth-state-toggle__label';
        label.dataset.dthGenerated = 'true';
        label.setAttribute('aria-live', 'polite');
        if (toggle.id) {
            label.htmlFor = toggle.id;
        }

        if (nativeIsStateLabel && nativeLabel) {
            nativeLabel.classList.add('dth-state-toggle__native-label--sr-only');
            field.dataset.dthStateLabelMode = 'deduplicated';
        } else {
            field.dataset.dthStateLabelMode = 'context-and-state';
        }

        visualRow.classList.add('dth-state-toggle');
        visualRow.appendChild(label);
        source.dataset.dthToggleEnhanced = 'true';

        function update() {
            const isOn = switchIsOn(toggle);
            const stateText = isOn ? onLabel : offLabel;
            label.textContent = stateText;
            label.title = stateText;
            visualRow.dataset.dthState = isOn ? 'on' : 'off';
            field.dataset.dthState = isOn ? 'on' : 'off';
            toggle.setAttribute('aria-description', stateText);
        }

        update();

        const clickHandler = function () { window.requestAnimationFrame(update); };
        const observer = new MutationObserver(update);

        toggle.addEventListener('change', update);
        toggle.addEventListener('click', clickHandler);
        observer.observe(toggle, {
            attributes: true,
            attributeFilter: ['aria-checked', 'data-state', 'class'],
        });

        toggleEnhancements.set(source, {
            clickHandler: clickHandler,
            label: label,
            observer: observer,
            toggle: toggle,
            update: update,
        });
    }

    function enhanceStateToggles(root) {
        (root || document).querySelectorAll('[data-dth-on-label][data-dth-off-label]').forEach(enhanceStateToggle);
    }

    function enhanceHelperText(helper) {
        if (!helper) return;

        const wrapper = helper.closest('.fi-fo-field-wrp');
        const labelArea = wrapper ? wrapper.querySelector('.fi-fo-field-wrp-label') : null;

        if (helper.dataset.dthHelperEnhanced === 'true' && labelArea?.querySelector('.dth-helper-tooltip__trigger')) {
            return;
        }

        const text = (helper.textContent || '').replace(/\s+/g, ' ').trim();
        if (!text) return;

        if (!labelArea) return;

        labelArea.querySelectorAll('.dth-helper-tooltip__trigger').forEach(function (trigger) {
            trigger.remove();
        });

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'dth-helper-tooltip__trigger';
        trigger.textContent = '?';
        trigger.title = text;
        trigger.setAttribute('aria-label', text);
        trigger.setAttribute('data-tooltip', text);

        labelArea.appendChild(trigger);
        helper.classList.add('dth-helper-tooltip__source');
        helper.dataset.dthHelperEnhanced = 'true';
    }

    function enhanceHelperTexts(root) {
        (root || document).querySelectorAll('.fi-fo-field-wrp-helper-text').forEach(enhanceHelperText);
    }

    function iconForText(text) {
        const value = normalize(text);

        if (/\b(tao|create|add|them|new)\b/.test(value)) return ICONS.plus;
        if (/\b(luu|save|update|cap nhat|apply)\b/.test(value)) return ICONS.save;
        if (/\b(quay lai|back|return)\b/.test(value)) return ICONS.back;
        if (/\b(huy|cancel|close|dong)\b/.test(value)) return ICONS.close;
        if (/\b(xoa|delete|remove)\b/.test(value)) return ICONS.trash;
        if (/\b(xem|view|preview|chi tiet|detail)\b/.test(value)) return ICONS.eye;
        if (/\b(sua|edit|chinh sua)\b/.test(value)) return ICONS.pencil;
        if (/\b(gui|send|email|notify)\b/.test(value)) return ICONS.send;
        if (/\b(xuat|export|download|tai xuong|pdf|csv)\b/.test(value)) return ICONS.download;
        if (/\b(duyet|approve|xac nhan|confirm|complete|hoan tat)\b/.test(value)) return ICONS.check;
        if (/\b(loc|filter)\b/.test(value)) return ICONS.filter;
        if (/\b(dat lai|reset|refresh|lam moi)\b/.test(value)) return ICONS.refresh;

        return ICONS.arrow;
    }

    function shouldSkipButton(button) {
        if (!button) return true;
        if (button.dataset.dthIconEnhanced === 'true' && button.querySelector('.dth-auto-icon')) return true;
        if (button.dataset.dthIconEnhanced === 'true') delete button.dataset.dthIconEnhanced;
        if (button.matches('.fi-icon-btn, [aria-label]:not(.fi-btn), [role="switch"], .fi-tabs-item, .fi-fo-tabs-tab')) return true;
        if (button.closest('.dth-color-swatch-picker, .fi-fo-radio, .fi-fo-checkbox-list')) return true;
        if (button.querySelector('svg:not(.fi-loading-indicator)')) return true;

        const text = (button.textContent || '').replace(/\s+/g, ' ').trim();
        return !text || text.length > 80;
    }

    function enhanceButton(button) {
        if (shouldSkipButton(button)) return;

        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('fill', 'none');
        svg.setAttribute('stroke', 'currentColor');
        svg.setAttribute('aria-hidden', 'true');
        svg.classList.add('dth-auto-icon');
        svg.innerHTML = iconForText(button.textContent || '');

        button.insertBefore(svg, button.firstChild);
        button.dataset.dthIconEnhanced = 'true';
    }

    function enhanceButtons(root) {
        (root || document).querySelectorAll('.fi-btn').forEach(enhanceButton);
    }

    function markPageActionBars(root) {
        (root || document).querySelectorAll('.fi-form-actions').forEach(function (actions) {
            if (actions.closest('.fi-modal, .fi-dropdown-panel')) return;
            actions.classList.add('dth-page-form-actions');
        });
    }

    function enhance(root) {
        markModules();
        enhanceStateToggles(root || document);
        enhanceHelperTexts(root || document);
        enhanceButtons(root || document);
        markPageActionBars(root || document);
    }

    let scheduled = false;
    function scheduleEnhance(root) {
        if (scheduled) return;
        scheduled = true;

        window.requestAnimationFrame(function () {
            scheduled = false;
            enhance(root || document);
        });
    }

    function boot() {
        enhance(document);

        if (window.__dthUiV3Observer) return;

        window.__dthUiV3Observer = new MutationObserver(function (mutations) {
            const addedRoots = [];

            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        addedRoots.push(node);
                    }
                });
            });

            if (addedRoots.length) {
                scheduleEnhance(document);
            }
        });

        window.__dthUiV3Observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    }

    document.addEventListener('DOMContentLoaded', boot);
    document.addEventListener('livewire:navigated', function () { scheduleEnhance(document); });
    document.addEventListener('livewire:initialized', function () { scheduleEnhance(document); });

    if (document.readyState !== 'loading') {
        boot();
    }
})();
</script>
