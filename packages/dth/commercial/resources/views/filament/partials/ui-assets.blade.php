@php
    $path = trim(request()->path(), '/');
    $segments = ['commercial-overview', 'commercial-services', 'commercial-products', 'commercial-service-packages', 'commercial-opportunities'];
    $isCommercialPage = collect($segments)->contains(fn (string $segment): bool => str_contains($path, $segment));
    $isCommercialFormPage = $isCommercialPage && (
        str_ends_with($path, '/create') ||
        (bool) preg_match('#commercial-(services|products|service-packages|opportunities)/[^/]+/edit$#', $path)
    );
@endphp

@if ($isCommercialPage)
<style>
    :root {
        --dth-com-bg: #f4fafb;
        --dth-com-bg-strong: #edf7f8;
        --dth-com-surface: #ffffff;
        --dth-com-surface-soft: #f8fcfc;
        --dth-com-border: #dfecee;
        --dth-com-border-strong: #c9dde0;
        --dth-com-heading: #102a34;
        --dth-com-text: #425b65;
        --dth-com-muted: #78909a;
        --dth-com-primary: #0f8f95;
        --dth-com-primary-strong: #087178;
        --dth-com-primary-deep: #075e65;
        --dth-com-primary-soft: #e7f8f8;
        --dth-com-accent: #49cbd0;
        --dth-com-shadow: 0 14px 38px rgba(18, 67, 74, .07);
        --dth-com-shadow-soft: 0 8px 24px rgba(18, 67, 74, .05);
        --dth-com-radius-xl: 20px;
        --dth-com-radius-lg: 16px;
        --dth-com-radius-md: 12px;
    }

    .fi-main {
        background:
            radial-gradient(circle at 82% -5%, rgba(73, 203, 208, .12), transparent 31rem),
            radial-gradient(circle at 18% 20%, rgba(15, 143, 149, .045), transparent 25rem),
            linear-gradient(180deg, #fbfefe 0%, var(--dth-com-bg) 100%) !important;
    }

    .fi-main .fi-page {
        width: min(100%, 1560px) !important;
        max-width: 1560px !important;
        margin-inline: auto !important;
        gap: 1rem !important;
    }

    .fi-header {
        align-items: center !important;
        margin-bottom: .15rem !important;
    }

    .fi-header-heading {
        color: var(--dth-com-heading) !important;
        font-size: clamp(1.75rem, 2vw, 2.1rem) !important;
        line-height: 1.1 !important;
        font-weight: 820 !important;
        letter-spacing: -.04em !important;
    }


    /* Page headings reuse the exact navigation icon of the current Commercial function. */
    .dth-com-page-title {
        display: inline-flex;
        align-items: center;
        gap: .68rem;
        min-width: 0;
    }

    .dth-com-page-title__icon {
        display: inline-flex;
        width: 1.72rem;
        height: 1.72rem;
        flex: 0 0 1.72rem;
        align-items: center;
        justify-content: center;
        color: rgb(var(--primary-600, 8 113 120));
    }

    .dth-com-page-title__icon svg {
        width: 100%;
        height: 100%;
        stroke-width: 1.8;
    }

    .dth-com-page-title__text {
        min-width: 0;
    }

    .fi-header-subheading {
        max-width: 52rem;
        margin-top: .38rem !important;
        color: var(--dth-com-muted) !important;
        font-size: .92rem !important;
        line-height: 1.6 !important;
    }

    /* ---------- Actions: same geometry and hierarchy as Email / Marketing ---------- */
    .fi-header-actions .fi-btn,
    .fi-fo-actions .fi-btn,
    .fi-ac-btn-action,
    .fi-ta-header-toolbar .fi-btn,
    .fi-modal-footer-actions .fi-btn {
        min-height: 42px;
        border-radius: 12px !important;
        font-weight: 720 !important;
        gap: .45rem !important;
    }

    /* Form submit actions follow the same module pattern as CRM / Marketing:
       primary action uses the module accent, secondary remains a neutral outline. */
    .fi-btn-color-primary,
    .fi-ac-btn-action[data-color="primary"] {
        background: linear-gradient(180deg, #17a1a6 0%, #0f8f95 100%) !important;
        border: 1px solid #0f8f95 !important;
        color: #fff !important;
        box-shadow: 0 9px 20px rgba(15, 143, 149, .16) !important;
    }

    .fi-btn-color-primary:hover,
    .fi-ac-btn-action[data-color="primary"]:hover {
        background: linear-gradient(180deg, #11969c 0%, #087178 100%) !important;
        border-color: #087178 !important;
        color: #fff !important;
    }

    .fi-ac-btn-action[data-color="primary"] svg,
    .fi-ac-btn-action[data-color="primary"] .fi-btn-icon,
    .fi-ac-btn-action[data-color="primary"] .fi-btn-icon * {
        color: #fff !important;
    }

    .fi-btn-color-gray,
    .fi-ac-btn-action[data-color="gray"],
    .fi-btn-outlined {
        background: #fff !important;
        color: var(--dth-com-text) !important;
        border: 1px solid var(--dth-com-border) !important;
        box-shadow: none !important;
    }

    .fi-btn-color-gray:hover,
    .fi-ac-btn-action[data-color="gray"]:hover,
    .fi-btn-outlined:hover {
        background: #fbfcfe !important;
        border-color: #cbd5e1 !important;
        color: #344054 !important;
    }


    /* DTH_FORM_ACTION_TONE:commercial */
    /* Explicit resource form actions, following the Human Resource pattern.
       These selectors are intentionally stronger than Filament / host theme
       primary colors so each module keeps its own identity. */
    html body .fi-btn.dth-com-form-action--primary {
        min-height: 42px !important;
        padding: 0 16px !important;
        border: 1px solid #0f8f95 !important;
        border-radius: 12px !important;
        background: linear-gradient(180deg, #17a1a6 0%, #0f8f95 100%) !important;
        color: #ffffff !important;
        box-shadow: 0 9px 20px rgba(15, 143, 149, .18) !important;
        font-weight: 720 !important;
        gap: .45rem !important;
    }
    html body .fi-btn.dth-com-form-action--primary * { color: #ffffff !important; }
    html body .fi-btn.dth-com-form-action--primary:hover {
        background: linear-gradient(180deg, #0f8f95 0%, #087178 100%) !important;
        border-color: #087178 !important;
        transform: translateY(-1px);
    }

    html body .fi-btn.dth-com-form-action--secondary {
        min-height: 42px !important;
        padding: 0 16px !important;
        border: 1px solid var(--dth-com-border) !important;
        border-radius: 12px !important;
        background: #fff !important;
        color: var(--dth-com-text) !important;
        box-shadow: none !important;
        font-weight: 720 !important;
        gap: .45rem !important;
    }
    html body .fi-btn.dth-com-form-action--secondary * { color: var(--dth-com-text) !important; }
    html body .fi-btn.dth-com-form-action--secondary:hover {
        background: #f8fafc !important;
        border-color: #cbd5e1 !important;
    }

    /* Modal submit/cancel actions on the module's own pages use the same tone. */
    html body .fi-main .fi-modal-footer-actions .fi-btn.fi-btn-color-primary,
    html body .fi-modal-footer-actions .fi-btn.fi-btn-color-primary {
        background: linear-gradient(180deg, #17a1a6 0%, #0f8f95 100%) !important;
        border-color: #0f8f95 !important;
        color: #ffffff !important;
        box-shadow: 0 9px 20px rgba(15, 143, 149, .18) !important;
    }
    html body .fi-main .fi-modal-footer-actions .fi-btn.fi-btn-color-primary *,
    html body .fi-modal-footer-actions .fi-btn.fi-btn-color-primary * { color: #ffffff !important; }
    html body .fi-main .fi-modal-footer-actions .fi-btn.fi-btn-color-gray,
    html body .fi-modal-footer-actions .fi-btn.fi-btn-color-gray {
        background: #fff !important;
        color: var(--dth-com-text) !important;
        border: 1px solid var(--dth-com-border) !important;
        box-shadow: none !important;
    }

    /* Create / input entry actions mirror the other DTH modules: white card-like
       button, compact geometry, module-color text instead of a heavy filled CTA. */
    .dth-com-entry-action.fi-btn {
        min-height: 40px !important;
        padding: 0 14px !important;
        border: 1px solid #dbe3ec !important;
        border-radius: 12px !important;
        background: #fff !important;
        box-shadow: 0 3px 10px rgba(15, 23, 42, .04) !important;
        font-size: .8rem !important;
        font-weight: 720 !important;
        gap: .48rem !important;
        transition: transform .15s ease, border-color .15s ease, background .15s ease, box-shadow .15s ease;
    }

    .dth-com-entry-action.fi-btn:hover {
        transform: translateY(-1px);
        background: #fbfcfe !important;
        border-color: #cbd5e1 !important;
        box-shadow: 0 5px 14px rgba(15, 23, 42, .06) !important;
    }

    .dth-com-entry-action--teal.fi-btn,
    .dth-com-entry-action--teal.fi-btn * {
        color: var(--dth-com-primary-strong) !important;
    }

    /* Dashboard report actions intentionally match the button style used by Email
       and Marketing (the reference supplied by the user). */
    .dth-com-report-action.fi-btn {
        display: inline-flex !important;
        min-height: 38px !important;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 0 13px !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 9px !important;
        color: #344054 !important;
        background: #fff !important;
        box-shadow: 0 2px 5px rgba(16, 24, 40, .025) !important;
        font-size: .76rem !important;
        font-weight: 650 !important;
        text-decoration: none;
        transition: border-color .15s ease, background .15s ease, transform .15s ease;
    }

    .dth-com-report-action.fi-btn:hover {
        transform: translateY(-1px);
        background: #fbfcfe !important;
        border-color: #d4dce8 !important;
    }

    .dth-com-report-action.fi-btn > span,
    .dth-com-report-action.fi-btn > svg,
    .dth-com-report-action.fi-btn .fi-btn-icon,
    .dth-com-report-action.fi-btn .fi-btn-icon * {
        color: inherit !important;
    }

    .dth-com-report-action--analysis.fi-btn,
    .dth-com-report-action--analysis.fi-btn * { color: #344054 !important; }
    .dth-com-report-action--pdf.fi-btn,
    .dth-com-report-action--pdf.fi-btn * { color: #c87500 !important; }
    .dth-com-report-action--excel.fi-btn,
    .dth-com-report-action--excel.fi-btn * { color: #15803d !important; }
    .dth-com-report-action--csv.fi-btn,
    .dth-com-report-action--csv.fi-btn * { color: #344054 !important; }

    .dth-com-report-action.fi-btn svg { width: 17px; height: 17px; }

    .fi-btn-color-danger { box-shadow: 0 8px 18px rgba(220, 38, 38, .1) !important; }
    .fi-btn-color-success { box-shadow: 0 8px 18px rgba(22, 163, 74, .1) !important; }

    /* Keep primary semantic badges teal even if the host panel uses purple. */
    .fi-badge.fi-color-primary,
    .fi-badge[class*="fi-color-primary"] {
        background: var(--dth-com-primary-soft) !important;
        color: var(--dth-com-primary-strong) !important;
    }

    /* ---------- Sections & forms ---------- */
    .fi-section,
    .fi-ta,
    .fi-modal-window {
        border: 1px solid var(--dth-com-border) !important;
        border-radius: var(--dth-com-radius-xl) !important;
        background: rgba(255, 255, 255, .97) !important;
        box-shadow: var(--dth-com-shadow-soft) !important;
    }

    .fi-section-header {
        padding: 1.05rem 1.25rem .95rem !important;
        border-bottom: 1px solid #edf4f5 !important;
    }

    .fi-section-header-heading {
        color: var(--dth-com-heading) !important;
        font-size: .98rem !important;
        font-weight: 800 !important;
        letter-spacing: -.018em;
    }

    .fi-section-header-description {
        max-width: 58rem;
        color: var(--dth-com-muted) !important;
        line-height: 1.55 !important;
    }

    .fi-section-content {
        row-gap: 1rem !important;
        padding: 1.15rem 1.25rem 1.25rem !important;
    }

    .fi-form {
        gap: 1rem !important;
    }

    .fi-input-wrp,
    .fi-select-input,
    .choices__inner,
    .trix-button-group,
    .fi-fo-field-wrp .fi-input-wrp,
    .fi-fo-field-wrp .fi-select-input,
    .fi-fo-rich-editor,
    .fi-ta-search-field .fi-input-wrp,
    .fi-ta-filters .fi-input-wrp,
    .fi-ta-filters .fi-select-input {
        border-width: 1px !important;
        border-style: solid !important;
        border-color: var(--dth-com-border-strong) !important;
        border-radius: var(--dth-com-radius-md) !important;
        background: #fff !important;
        box-shadow: 0 1px 0 rgba(16, 42, 52, .015) !important;
    }

    .fi-input-wrp { min-height: 44px; }

    .fi-input-wrp .fi-input,
    .fi-input-wrp .fi-input-wrp-input,
    .fi-input-wrp .fi-select-input,
    .fi-fo-rich-editor .ProseMirror {
        border: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    .fi-input,
    .fi-select-input,
    .fi-input-wrp-input {
        color: var(--dth-com-text) !important;
    }

    .fi-input::placeholder,
    textarea::placeholder {
        color: #a5b5bb !important;
    }

    .fi-input-wrp:focus-within,
    .choices.is-focused .choices__inner,
    .trix-button-group:focus-within,
    .fi-fo-rich-editor:focus-within,
    .fi-ta-search-field .fi-input-wrp:focus-within {
        border-color: #65bec2 !important;
        box-shadow: 0 0 0 4px rgba(15, 143, 149, .105) !important;
    }

    .fi-fo-field-wrp-label span,
    .fi-fo-field-wrp-label label,
    .fi-ta-header-cell-label {
        color: #2f4b55 !important;
        font-weight: 720 !important;
    }

    .fi-fo-field-wrp-helper-text,
    .fi-fo-field-wrp-hint {
        color: #8da0a8 !important;
        line-height: 1.45 !important;
    }

    /* ---------- Tables ---------- */
    .fi-ta {
        overflow: visible !important;
    }

    .fi-ta-header {
        padding: 1rem 1.05rem .2rem !important;
        gap: .85rem !important;
    }

    .fi-ta-header-toolbar {
        gap: .7rem !important;
        align-items: center !important;
    }

    .fi-ta-search-field {
        width: min(100%, 470px) !important;
        max-width: 470px !important;
    }

    .fi-ta-filters .fi-input-wrp,
    .fi-ta-filters .fi-select-input,
    .fi-ta-filters .choices__inner {
        min-height: 42px !important;
        border-radius: 11px !important;
    }

    .fi-ta-header-cell {
        background: #f6fbfb !important;
        border-bottom: 1px solid #e8f1f2 !important;
    }

    .fi-ta-header-cell,
    .fi-ta-cell {
        padding-top: .85rem !important;
        padding-bottom: .85rem !important;
    }

    .fi-ta-row {
        background: #fff;
        transition: background .13s ease, box-shadow .13s ease;
    }

    .fi-ta-row:hover {
        background: #f9fdfd !important;
    }

    .fi-ta-row:not(:last-child) .fi-ta-cell {
        border-bottom: 1px solid #eef4f4 !important;
    }

    .fi-badge {
        border-radius: 999px !important;
        padding: .24rem .62rem !important;
        font-size: .71rem !important;
        font-weight: 740 !important;
        box-shadow: none !important;
    }

    .fi-pagination {
        gap: .75rem !important;
        padding: .9rem 1rem 1rem !important;
    }

    .fi-pagination-items .fi-pagination-item {
        width: 38px;
        height: 38px;
        border-radius: 11px !important;
        border: 1px solid var(--dth-com-border) !important;
        background: #fff !important;
    }

    .fi-pagination-items .fi-pagination-item[aria-current="page"] {
        background: var(--dth-com-primary) !important;
        border-color: var(--dth-com-primary) !important;
        color: #fff !important;
    }

    /* ---------- List stat widgets ---------- */
    .fi-wi.dth-com-list-stats-widget,
    .fi-wi.dth-com-pipeline-widget {
        margin: 0 !important;
    }

    .fi-wi.dth-com-list-stats-widget > div,
    .fi-wi.dth-com-pipeline-widget > div {
        padding: 0 !important;
        border: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    .dth-com-list-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .8rem;
    }

    .dth-com-list-stat {
        --tone: 15, 143, 149;
        position: relative;
        min-height: 116px;
        display: grid;
        grid-template-columns: 46px minmax(0, 1fr);
        gap: .9rem;
        align-items: start;
        padding: 1rem 1.05rem;
        overflow: hidden;
        border: 1px solid var(--dth-com-border);
        border-radius: 16px;
        background: rgba(255, 255, 255, .96);
        box-shadow: var(--dth-com-shadow-soft);
    }

    .dth-com-list-stat::after {
        content: '';
        position: absolute;
        width: 100px;
        height: 100px;
        right: -34px;
        bottom: -42px;
        border-radius: 999px;
        background: rgba(var(--tone), .07);
    }

    .dth-com-list-stat[data-tone="green"] { --tone: 22, 163, 74; }
    .dth-com-list-stat[data-tone="amber"] { --tone: 217, 119, 6; }
    .dth-com-list-stat[data-tone="blue"] { --tone: 37, 99, 235; }
    .dth-com-list-stat[data-tone="slate"] { --tone: 100, 116, 139; }

    .dth-com-list-stat__icon {
        width: 46px;
        height: 46px;
        display: grid;
        place-items: center;
        border-radius: 13px;
        color: rgb(var(--tone));
        background: rgba(var(--tone), .105);
    }

    .dth-com-list-stat__icon svg {
        width: 23px;
        height: 23px;
    }

    .dth-com-list-stat__body {
        min-width: 0;
        display: flex;
        flex-direction: column;
    }

    .dth-com-list-stat__label {
        color: #6f8790;
        font-size: .76rem;
        font-weight: 650;
    }

    .dth-com-list-stat__value {
        margin-top: .18rem;
        color: var(--dth-com-heading);
        font-size: clamp(1.28rem, 1.6vw, 1.62rem);
        line-height: 1.12;
        font-weight: 840;
        letter-spacing: -.035em;
        white-space: nowrap;
    }

    .dth-com-list-stat__meta {
        margin-top: .3rem;
        color: #95a7ad;
        font-size: .68rem;
        line-height: 1.35;
    }

    /* ---------- Opportunity pipeline ---------- */
    .dth-com-pipeline {
        border: 1px solid var(--dth-com-border);
        border-radius: 18px;
        background: rgba(255, 255, 255, .97);
        box-shadow: var(--dth-com-shadow-soft);
        overflow: hidden;
    }

    .dth-com-pipeline__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.1rem;
        border-bottom: 1px solid #edf4f5;
    }

    .dth-com-eyebrow {
        display: inline-block;
        color: var(--dth-com-primary);
        font-size: .58rem;
        line-height: 1;
        font-weight: 850;
        letter-spacing: .12em;
    }

    .dth-com-pipeline__header h2 {
        margin: .12rem 0 0;
        color: var(--dth-com-heading);
        font-size: 1.04rem;
        font-weight: 820;
        letter-spacing: -.02em;
    }

    .dth-com-pipeline__header p {
        margin: .2rem 0 0;
        color: var(--dth-com-muted);
        font-size: .76rem;
    }

    .dth-com-pipeline__scroll {
        overflow-x: auto;
        padding: .9rem;
        scrollbar-width: thin;
        scrollbar-color: #bfdadd transparent;
    }

    .dth-com-pipeline__grid {
        min-width: 1340px;
        display: grid;
        grid-template-columns: repeat(6, minmax(205px, 1fr));
        gap: .72rem;
    }

    .dth-com-stage {
        --stage: 14, 165, 233;
        min-height: 260px;
        padding: .72rem;
        border: 1px solid rgba(var(--stage), .18);
        border-radius: 15px;
        background: linear-gradient(180deg, rgba(var(--stage), .085), rgba(var(--stage), .035));
    }

    .dth-com-stage[data-tone="cyan"] { --stage: 6, 182, 212; }
    .dth-com-stage[data-tone="amber"] { --stage: 245, 158, 11; }
    .dth-com-stage[data-tone="orange"] { --stage: 249, 115, 22; }
    .dth-com-stage[data-tone="green"] { --stage: 22, 163, 74; }
    .dth-com-stage[data-tone="rose"] { --stage: 244, 63, 94; }

    .dth-com-stage__header {
        margin-bottom: .65rem;
        padding: .1rem .08rem .55rem;
        border-bottom: 1px solid rgba(var(--stage), .14);
    }

    .dth-com-stage__title-row {
        display: flex;
        align-items: center;
        gap: .42rem;
    }

    .dth-com-stage__title-row strong {
        min-width: 0;
        color: rgb(var(--stage));
        font-size: .78rem;
        font-weight: 820;
        line-height: 1.28;
    }

    .dth-com-stage__dot {
        width: .52rem;
        height: .52rem;
        flex: 0 0 .52rem;
        border-radius: 999px;
        background: rgb(var(--stage));
        box-shadow: 0 0 0 4px rgba(var(--stage), .09);
    }

    .dth-com-stage__count {
        margin-left: auto;
        min-width: 1.35rem;
        height: 1.35rem;
        display: grid;
        place-items: center;
        padding-inline: .28rem;
        border-radius: 999px;
        color: rgb(var(--stage));
        background: rgba(var(--stage), .1);
        font-size: .66rem;
        font-weight: 800;
    }

    .dth-com-stage__value {
        margin-top: .35rem;
        color: #39525b;
        font-size: .74rem;
        font-weight: 760;
    }

    .dth-com-stage__cards {
        display: grid;
        gap: .55rem;
    }

    .dth-com-opportunity-card {
        display: block;
        padding: .72rem;
        border: 1px solid rgba(215, 228, 230, .95);
        border-radius: 12px;
        background: rgba(255, 255, 255, .98);
        color: inherit;
        text-decoration: none;
        box-shadow: 0 5px 14px rgba(27, 72, 78, .045);
        transition: transform .14s ease, box-shadow .14s ease, border-color .14s ease;
    }

    .dth-com-opportunity-card:hover {
        transform: translateY(-1px);
        border-color: rgba(var(--stage), .33);
        box-shadow: 0 9px 20px rgba(27, 72, 78, .075);
    }

    .dth-com-opportunity-card__head {
        display: flex;
        align-items: flex-start;
        gap: .4rem;
    }

    .dth-com-opportunity-card__head strong {
        color: #17333d;
        font-size: .76rem;
        line-height: 1.35;
        font-weight: 800;
    }

    .dth-com-opportunity-card__head svg {
        width: .85rem;
        height: .85rem;
        margin-left: auto;
        color: #a1b2b8;
        flex: 0 0 .85rem;
    }

    .dth-com-opportunity-card__customer {
        display: block;
        margin-top: .16rem;
        color: #82969d;
        font-size: .67rem;
    }

    .dth-com-opportunity-card__service {
        display: inline-block;
        max-width: 100%;
        margin-top: .42rem;
        padding: .18rem .4rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        border-radius: 999px;
        background: #edf9f9;
        color: var(--dth-com-primary-strong);
        font-size: .62rem;
        font-weight: 720;
    }

    .dth-com-opportunity-card__numbers {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        margin-top: .52rem;
    }

    .dth-com-opportunity-card__numbers strong {
        color: #17333d;
        font-size: .72rem;
    }

    .dth-com-opportunity-card__numbers span {
        color: rgb(var(--stage));
        font-size: .68rem;
        font-weight: 820;
    }

    .dth-com-opportunity-card__footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        margin-top: .5rem;
        color: #84979e;
        font-size: .62rem;
    }

    .dth-com-opportunity-card__footer > span:first-child {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
    }

    .dth-com-opportunity-card__footer svg {
        width: .72rem;
        height: .72rem;
    }

    .dth-com-owner-chip {
        width: 1.45rem;
        height: 1.45rem;
        display: grid;
        place-items: center;
        border-radius: 999px;
        background: #17333d;
        color: #fff;
        font-size: .53rem;
        font-weight: 800;
    }

    .dth-com-stage__empty {
        padding: .8rem .6rem;
        text-align: center;
        color: #98a9af;
        font-size: .68rem;
        line-height: 1.45;
    }

    /* ---------- Dashboard ---------- */
    .dth-commercial-overview {
        display: grid;
        gap: .9rem;
    }

    .dth-com-dashboard-kpis {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .8rem;
    }

    .dth-com-kpi {
        --tone: 15, 143, 149;
        position: relative;
        min-width: 0;
        min-height: 112px;
        overflow: hidden;
        display: grid;
        grid-template-columns: 44px minmax(0, 1fr);
        align-items: start;
        gap: .8rem;
        padding: 1rem 1.05rem;
        border: 1px solid var(--dth-com-border);
        border-radius: 17px;
        background: rgba(255, 255, 255, .98);
        box-shadow: var(--dth-com-shadow-soft);
    }

    .dth-com-kpi::after {
        content: '';
        position: absolute;
        width: 112px;
        height: 112px;
        right: -46px;
        bottom: -56px;
        border-radius: 999px;
        background: rgba(var(--tone), .06);
        pointer-events: none;
    }

    .dth-com-kpi[data-tone="teal"] { --tone: 15, 143, 149; }
    .dth-com-kpi[data-tone="blue"] { --tone: 37, 99, 235; }
    .dth-com-kpi[data-tone="green"] { --tone: 22, 163, 74; }
    .dth-com-kpi[data-tone="amber"] { --tone: 217, 119, 6; }

    .dth-com-kpi__icon {
        width: 44px;
        height: 44px;
        display: grid;
        place-items: center;
        color: rgb(var(--tone));
        border-radius: 13px;
        background: rgba(var(--tone), .10);
    }

    .dth-com-kpi__icon svg { width: 22px; height: 22px; }

    .dth-com-kpi__body {
        position: relative;
        z-index: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
    }

    .dth-com-kpi__body > span {
        color: #70858e;
        font-size: .7rem;
        font-weight: 650;
        line-height: 1.3;
    }

    .dth-com-kpi__body strong {
        margin-top: .24rem;
        color: var(--dth-com-heading);
        font-size: clamp(1.12rem, 1.45vw, 1.5rem);
        line-height: 1.12;
        font-weight: 850;
        letter-spacing: -.04em;
        white-space: nowrap;
    }

    .dth-com-kpi__body small {
        margin-top: .32rem;
        color: #94a5ab;
        font-size: .61rem;
        line-height: 1.42;
    }

    .dth-com-dashboard-grid {
        display: grid;
        grid-template-columns: .94fr 1.28fr .98fr;
        gap: .8rem;
        align-items: stretch;
    }

    .dth-com-dashboard-card {
        min-width: 0;
        overflow: hidden;
        border: 1px solid var(--dth-com-border);
        border-radius: 18px;
        background: rgba(255, 255, 255, .985);
        box-shadow: var(--dth-com-shadow-soft);
    }

    .dth-com-card-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .92rem 1rem .78rem;
        border-bottom: 1px solid #eef4f5;
    }

    .dth-com-card-heading h3 {
        margin: .18rem 0 0;
        color: var(--dth-com-heading);
        font-size: .88rem;
        font-weight: 820;
        letter-spacing: -.018em;
    }

    .dth-com-card-kicker {
        display: block;
        color: var(--dth-com-primary);
        font-size: .56rem;
        line-height: 1;
        font-weight: 850;
        letter-spacing: .12em;
    }

    .dth-com-card-heading__meta {
        color: #8da2a9;
        font-size: .66rem;
        font-weight: 680;
        white-space: nowrap;
    }

    .dth-com-card-link {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        color: var(--dth-com-primary-strong);
        text-decoration: none;
        font-size: .66rem;
        font-weight: 760;
        white-space: nowrap;
    }

    .dth-com-card-link:hover { color: var(--dth-com-primary-deep); }
    .dth-com-card-link svg { width: .78rem; height: .78rem; }

    .dth-com-funnel {
        display: grid;
        gap: .55rem;
        padding: 1rem;
    }

    .dth-com-funnel__row {
        --stage: 14, 165, 233;
        display: grid;
        grid-template-columns: minmax(105px, 1fr) minmax(138px, 1.08fr);
        align-items: center;
        gap: .8rem;
    }

    .dth-com-funnel__row[data-tone="cyan"] { --stage: 6, 182, 212; }
    .dth-com-funnel__row[data-tone="amber"] { --stage: 245, 158, 11; }
    .dth-com-funnel__row[data-tone="orange"] { --stage: 249, 115, 22; }
    .dth-com-funnel__row[data-tone="green"] { --stage: 22, 163, 74; }
    .dth-com-funnel__row[data-tone="rose"] { --stage: 244, 63, 94; }

    .dth-com-funnel__shape-wrap { display: flex; justify-content: center; }

    .dth-com-funnel__shape {
        height: 28px;
        border-radius: 7px 7px 11px 11px;
        clip-path: polygon(0 0, 100% 0, 88% 100%, 12% 100%);
        background: linear-gradient(90deg, rgba(var(--stage), .9), rgba(var(--stage), .55));
        box-shadow: inset 0 0 0 1px rgba(255,255,255,.18);
    }

    .dth-com-funnel__label {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto auto;
        align-items: center;
        gap: .45rem;
        color: #607983;
        font-size: .67rem;
    }

    .dth-com-funnel__label span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dth-com-funnel__label strong { color: var(--dth-com-heading); font-size: .76rem; }
    .dth-com-funnel__label small {
        padding: .14rem .32rem;
        border-radius: 999px;
        background: #f2f7f8;
        color: #8a9ea5;
        font-size: .58rem;
        font-weight: 750;
    }

    .dth-com-trend-chart { padding: .9rem .75rem .2rem; }
    .dth-com-trend-chart svg { display: block; width: 100%; height: auto; }
    .dth-com-chart-gridline { stroke: #e9f1f2; stroke-width: 1; }
    .dth-com-chart-area { fill: url(#dthCommercialArea); }
    .dth-com-chart-line {
        fill: none;
        stroke: var(--dth-com-primary);
        stroke-width: 3;
        stroke-linecap: round;
        stroke-linejoin: round;
    }
    .dth-com-chart-dot { fill: #fff; stroke: var(--dth-com-primary); stroke-width: 3; }
    .dth-com-chart-label { fill: #8aa0a7; font-size: 11px; font-weight: 650; }

    .dth-com-trend-summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .55rem;
        padding: .1rem 1rem 1rem;
    }

    .dth-com-trend-summary > div {
        padding: .62rem .72rem;
        border: 1px solid #edf4f5;
        border-radius: 12px;
        background: #f8fbfc;
    }
    .dth-com-trend-summary span { display: block; color: #81969e; font-size: .61rem; }
    .dth-com-trend-summary strong { display: block; margin-top: .18rem; color: #24434d; font-size: .76rem; }

    .dth-com-catalog-metrics {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .55rem;
        padding: .9rem 1rem .25rem;
    }

    .dth-com-catalog-metrics > div {
        display: grid;
        grid-template-columns: 32px minmax(0, 1fr) auto;
        align-items: center;
        gap: .45rem;
        min-width: 0;
        padding: .55rem .6rem;
        border: 1px solid #e9f1f2;
        border-radius: 12px;
        background: #f9fcfc;
    }

    .dth-com-catalog-metric__icon {
        width: 32px;
        height: 32px;
        display: grid;
        place-items: center;
        border-radius: 9px;
        color: var(--dth-com-primary);
        background: var(--dth-com-primary-soft);
    }
    .dth-com-catalog-metric__icon svg { width: 16px; height: 16px; }
    .dth-com-catalog-metrics > div > span:not(.dth-com-catalog-metric__icon) {
        min-width: 0;
        color: #72878f;
        font-size: .61rem;
        line-height: 1.3;
    }
    .dth-com-catalog-metrics strong { color: var(--dth-com-heading); font-size: .94rem; font-weight: 840; }

    .dth-com-top-services {
        display: grid;
        gap: .68rem;
        padding: .75rem 1rem 1rem;
    }

    .dth-com-service-rank {
        display: grid;
        grid-template-columns: 27px minmax(0, 1fr);
        gap: .58rem;
        align-items: start;
    }

    .dth-com-service-rank__number {
        width: 27px;
        height: 27px;
        display: grid;
        place-items: center;
        border-radius: 999px;
        color: #52717b;
        background: #eff7f8;
        font-size: .62rem;
        font-weight: 820;
    }

    .dth-com-service-rank__body { min-width: 0; }
    .dth-com-service-rank__body > div:first-child {
        display: flex;
        justify-content: space-between;
        gap: .65rem;
        align-items: baseline;
    }
    .dth-com-service-rank__body strong {
        min-width: 0;
        overflow: hidden;
        color: #294650;
        font-size: .67rem;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .dth-com-service-rank__body > div:first-child span {
        color: #294650;
        font-size: .63rem;
        font-weight: 780;
        white-space: nowrap;
    }
    .dth-com-service-rank__track {
        height: 5px;
        overflow: hidden;
        margin-top: .3rem;
        border-radius: 999px;
        background: #eaf1f2;
    }
    .dth-com-service-rank__track span {
        display: block;
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, var(--dth-com-primary), #38c7cc);
    }
    .dth-com-service-rank__body small {
        display: block;
        margin-top: .18rem;
        color: #98a9af;
        font-size: .56rem;
    }

    .dth-com-recent__table-wrap { overflow-x: auto; }
    .dth-com-recent__table {
        width: 100%;
        min-width: 980px;
        border-collapse: collapse;
        font-size: .68rem;
    }
    .dth-com-recent__table th {
        padding: .65rem .85rem;
        text-align: left;
        color: #6e858e;
        background: #f6fbfb;
        font-size: .62rem;
        font-weight: 760;
    }
    .dth-com-recent__table td {
        padding: .72rem .85rem;
        border-top: 1px solid #edf3f4;
        color: #4f6871;
        vertical-align: middle;
    }
    .dth-com-recent__table td > a {
        color: var(--dth-com-primary-strong);
        font-weight: 750;
        text-decoration: none;
    }
    .dth-com-recent__table td strong { display: block; color: #2a4650; font-size: .69rem; }
    .dth-com-recent__table td small { display: block; margin-top: .12rem; color: #91a3a9; font-size: .59rem; }

    .dth-com-stage-badge {
        display: inline-flex;
        align-items: center;
        padding: .22rem .48rem;
        border-radius: 999px;
        color: #0b719c;
        background: #e9f7ff;
        font-size: .59rem;
        font-weight: 760;
        white-space: nowrap;
    }
    .dth-com-stage-badge[data-stage="qualified"] { color: #087d8d; background: #e5f9fb; }
    .dth-com-stage-badge[data-stage="proposal"] { color: #a86004; background: #fff6df; }
    .dth-com-stage-badge[data-stage="negotiation"] { color: #b34f0c; background: #fff0e7; }
    .dth-com-stage-badge[data-stage="won"] { color: #15803d; background: #e9f9ef; }
    .dth-com-stage-badge[data-stage="lost"] { color: #be123c; background: #fff0f3; }
    .dth-com-stage-badge[data-stage="cancelled"] { color: #64748b; background: #f1f5f9; }

    .dth-com-empty-state {
        padding: 1.1rem;
        color: #91a4aa;
        text-align: center;
        font-size: .7rem;
    }

    /* ---------- Statistical analysis modal ---------- */
    .dth-com-insights { display: grid; gap: .85rem; }
    .dth-com-insights__summary {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .65rem;
    }
    .dth-com-insights__summary > div {
        padding: .75rem .8rem;
        border: 1px solid #e7eef2;
        border-radius: 13px;
        background: #f9fbfc;
    }
    .dth-com-insights__summary span { display: block; color: #7b879c; font-size: .7rem; }
    .dth-com-insights__summary strong {
        display: block;
        margin-top: .2rem;
        color: #102a34;
        font-size: 1.05rem;
        font-weight: 820;
        letter-spacing: -.025em;
    }
    .dth-com-insights__list { display: grid; gap: .55rem; }
    .dth-com-insight {
        --insight: 100, 116, 139;
        display: grid;
        grid-template-columns: 38px minmax(0, 1fr);
        gap: .7rem;
        padding: .75rem .8rem;
        border: 1px solid #e8eef2;
        border-radius: 13px;
        background: #fff;
    }
    .dth-com-insight[data-level="positive"] { --insight: 22, 163, 74; }
    .dth-com-insight[data-level="warning"] { --insight: 217, 119, 6; }
    .dth-com-insight[data-level="info"] { --insight: 15, 143, 149; }
    .dth-com-insight__icon {
        width: 38px;
        height: 38px;
        display: grid;
        place-items: center;
        border-radius: 11px;
        color: rgb(var(--insight));
        background: rgba(var(--insight), .09);
    }
    .dth-com-insight__icon svg { width: 19px; height: 19px; }
    .dth-com-insight__copy { min-width: 0; }
    .dth-com-insight__title-row {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: .75rem;
    }
    .dth-com-insight__title-row strong { color: #263f49; font-size: .78rem; font-weight: 790; }
    .dth-com-insight__title-row span { color: rgb(var(--insight)); font-size: .72rem; font-weight: 780; white-space: nowrap; }
    .dth-com-insight p { margin: .2rem 0 0; color: #738890; font-size: .7rem; line-height: 1.55; }

    /* ---------- Tabs ---------- */
    .fi-tabs {
        display: inline-flex !important;
        width: max-content !important;
        gap: .4rem !important;
        padding: .38rem !important;
        border: 1px solid var(--dth-com-border) !important;
        border-radius: 14px !important;
        background: rgba(255, 255, 255, .92) !important;
        box-shadow: var(--dth-com-shadow-soft) !important;
    }

    .fi-tabs-item {
        min-height: 38px !important;
        padding: .5rem .9rem !important;
        border-radius: 10px !important;
        border: 1px solid transparent !important;
        background: transparent !important;
        color: #758b94 !important;
        font-weight: 720 !important;
    }

    .fi-tabs-item:hover {
        background: #f4fbfb !important;
        color: #46636d !important;
    }

    .fi-tabs-item[aria-selected="true"] {
        background: var(--dth-com-primary-soft) !important;
        border-color: #bfe5e7 !important;
        color: var(--dth-com-primary-strong) !important;
        box-shadow: none !important;
    }

    .fi-modal-window { overflow: hidden; }
    .fi-modal-content { max-height: min(82vh, 980px); }
    .ProseMirror { min-height: 220px; }

    @media (max-width: 1280px) {
        .dth-com-dashboard-grid { grid-template-columns: 1fr 1.15fr; }
        .dth-com-dashboard-card--services { grid-column: 1 / -1; }
        .dth-com-top-services { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 1100px) {
        .dth-com-list-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .dth-com-dashboard-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .dth-com-dashboard-grid { grid-template-columns: 1fr; }
        .dth-com-dashboard-card--services { grid-column: auto; }
        .dth-com-top-services { grid-template-columns: 1fr; }
    }

    @media (max-width: 768px) {
        .fi-header { align-items: flex-start !important; }
        .fi-header-actions { width: 100%; flex-wrap: wrap; }
        .fi-tabs { width: 100% !important; display: flex !important; }
        .fi-tabs-item { flex: 1 1 0; justify-content: center; }
        .dth-com-pipeline__header { align-items: flex-start; flex-direction: column; }
        .dth-com-insights__summary { grid-template-columns: 1fr; }
    }

    @media (max-width: 620px) {
        .dth-com-list-stats,
        .dth-com-dashboard-kpis { grid-template-columns: 1fr; }
        .dth-com-funnel__row { grid-template-columns: 95px minmax(0, 1fr); }
        .dth-com-trend-summary,
        .dth-com-catalog-metrics { grid-template-columns: 1fr; }
        .dth-com-report-action.fi-btn { flex: 1 1 auto; }
    }
</style>

@if ($isCommercialFormPage)
<style>
    /* Form pages intentionally use a centered composition instead of Filament's left-heavy default. */
    .fi-main .fi-page {
        width: min(100%, 1180px) !important;
        max-width: 1180px !important;
        margin-inline: auto !important;
    }

    .fi-page-content,
    .fi-form,
    .fi-form > div,
    .fi-form .fi-sc,
    .fi-form .fi-section {
        width: 100% !important;
        max-width: none !important;
    }

    .fi-form .fi-section + .fi-section {
        margin-top: .15rem;
    }

    /* Keep the same form action placement as CRM / Marketing instead of a
       floating sticky tray. The page remains centered, but actions stay native. */
    .fi-fo-actions {
        gap: .65rem !important;
        padding-top: .25rem !important;
        margin-top: .25rem !important;
    }

    /* Create / Edit actions use the exact same semantic button treatment as the
       other DTH modules. The save action is Filament primary, which is mapped
       above to Commercial ocean-teal; cancel remains a neutral outline. */
    .fi-fo-actions {
        justify-content: flex-start !important;
    }

    @media (max-width: 700px) {
        .fi-fo-actions { flex-wrap: wrap; }
        .fi-fo-actions .fi-btn { min-width: 0; }
    }
</style>
@endif
@endif
