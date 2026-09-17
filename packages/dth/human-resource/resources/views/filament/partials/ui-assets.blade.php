@php
    $path = trim(request()->path(), '/');
    $humanResourceSegments = [
        'human-resource-overview',
        'employees',
        'departments',
        'positions',
    ];

    $isHumanResourceUiPage = collect($humanResourceSegments)
        ->contains(fn (string $segment): bool => str_contains($path, $segment));
@endphp

@if ($isHumanResourceUiPage)
<style>
    :root {
        --dth-hr-bg: #f5f8fc;
        --dth-hr-surface: #ffffff;
        --dth-hr-border: #e2e8f0;
        --dth-hr-border-strong: #cfd9e6;
        --dth-hr-heading: #122033;
        --dth-hr-text: #344054;
        --dth-hr-muted: #7b8799;
        --dth-hr-primary: #2563eb;
        --dth-hr-primary-strong: #1d4ed8;
        --dth-hr-primary-soft: #eff6ff;
        --dth-hr-violet: #7c3aed;
        --dth-hr-green: #16a34a;
        --dth-hr-amber: #d97706;
        --dth-hr-red: #dc2626;
        --dth-hr-cyan: #0891b2;
        --dth-hr-shadow: 0 10px 30px rgba(15, 23, 42, .045);
        --dth-hr-radius-lg: 16px;
        --dth-hr-radius-md: 12px;
    }

    .fi-main {
        background:
            radial-gradient(circle at 86% 1%, rgba(37, 99, 235, .05), transparent 30rem),
            linear-gradient(180deg, #f9fbfe 0%, var(--dth-hr-bg) 100%) !important;
    }

    .fi-main .fi-page { gap: 16px !important; }
    .fi-header { align-items: center; margin-bottom: .15rem; }
    .fi-header-heading {
        color: var(--dth-hr-heading) !important;
        font-size: 1.82rem !important;
        line-height: 1.12 !important;
        font-weight: 820 !important;
        letter-spacing: -.036em !important;
    }
    .fi-header-subheading { color: var(--dth-hr-muted) !important; font-size: .9rem !important; }

    .dth-hr-page-title { display: inline-flex; align-items: center; gap: .85rem; }
    .dth-hr-page-title__icon {
        width: 3.35rem;
        height: 3.35rem;
        flex: 0 0 3.35rem;
        display: grid;
        place-items: center;
        color: var(--dth-hr-primary);
        border: 1px solid #d6e4ff;
        border-radius: 1rem;
        background: linear-gradient(145deg, #eff6ff 0%, #fff 100%);
        box-shadow: 0 10px 26px rgba(37, 99, 235, .09);
    }
    .dth-hr-page-title__icon svg { width: 1.55rem; height: 1.55rem; }
    .dth-hr-page-title--violet .dth-hr-page-title__icon { color: var(--dth-hr-violet); border-color: #eadcff; background: linear-gradient(145deg, #f8f2ff 0%, #fff 100%); }
    .dth-hr-page-title--amber .dth-hr-page-title__icon { color: var(--dth-hr-amber); border-color: #f5dfbc; background: linear-gradient(145deg, #fff8e8 0%, #fff 100%); }
    .dth-hr-page-title--green .dth-hr-page-title__icon { color: var(--dth-hr-green); border-color: #d7efdf; background: linear-gradient(145deg, #f0fdf4 0%, #fff 100%); }
    .dth-hr-page-title--cyan .dth-hr-page-title__icon { color: var(--dth-hr-cyan); border-color: #d5edf3; background: linear-gradient(145deg, #effbfe 0%, #fff 100%); }

    .fi-section,
    .fi-ta,
    .fi-wi > div,
    .fi-modal-window {
        border-color: var(--dth-hr-border) !important;
        border-radius: var(--dth-hr-radius-lg) !important;
        box-shadow: var(--dth-hr-shadow) !important;
    }
    .fi-section { background: rgba(255,255,255,.98) !important; }
    .fi-section-header { padding-bottom: .9rem !important; border-bottom: 1px solid #edf2f6; }
    .fi-section-header-heading { color: var(--dth-hr-heading) !important; font-weight: 800 !important; letter-spacing: -.02em; }
    .fi-section-header-description { color: var(--dth-hr-muted) !important; }
    .fi-section-header-icon { color: var(--dth-hr-primary) !important; }

    .fi-header-actions .fi-btn,
    .fi-fo-actions .fi-btn,
    .fi-ta-header-toolbar .fi-btn,
    .fi-modal-footer-actions .fi-btn {
        min-height: 42px;
        border-radius: 12px !important;
        font-weight: 720 !important;
        gap: .45rem !important;
    }
    /* Keep HR actions isolated from colors injected by other modules.
       The extra specificity is intentional because all module render hooks
       can coexist in the same Filament panel. */
    html body .fi-main .fi-btn.fi-btn-color-primary,
    html body .fi-modal-footer-actions .fi-btn.fi-btn-color-primary {
        background: linear-gradient(180deg, #3674ef 0%, #2563eb 100%) !important;
        border-color: #2563eb !important;
        color: #fff !important;
        box-shadow: 0 9px 20px rgba(37, 99, 235, .18) !important;
    }
    html body .fi-main .fi-btn.fi-btn-color-primary *,
    html body .fi-modal-footer-actions .fi-btn.fi-btn-color-primary * { color: #fff !important; }

    html body .fi-main .fi-btn.fi-btn-color-gray,
    html body .fi-main .fi-btn.fi-btn-outlined,
    html body .fi-modal-footer-actions .fi-btn.fi-btn-color-gray {
        background: #fff !important;
        color: var(--dth-hr-text) !important;
        border: 1px solid var(--dth-hr-border) !important;
        box-shadow: none !important;
    }
    html body .fi-main .fi-btn.fi-btn-color-danger { box-shadow: 0 8px 18px rgba(220,38,38,.12) !important; }
    html body .fi-main .fi-btn.fi-btn-color-success { box-shadow: 0 8px 18px rgba(22,163,74,.12) !important; }

    /* Resource create/edit form actions. These classes keep Save/Create and
       Cancel visually identical on Employee, Department and Job-title forms. */
    html body .fi-btn.dth-hr-form-action--primary,
    html body .fi-btn.dth-hr-modal-action--primary {
        min-height: 42px !important;
        padding: 0 16px !important;
        border: 1px solid #2563eb !important;
        border-radius: 12px !important;
        background: linear-gradient(180deg, #3674ef 0%, #2563eb 100%) !important;
        color: #fff !important;
        box-shadow: 0 9px 20px rgba(37, 99, 235, .18) !important;
        font-weight: 720 !important;
    }
    html body .fi-btn.dth-hr-form-action--primary *,
    html body .fi-btn.dth-hr-modal-action--primary * { color: #fff !important; }
    html body .fi-btn.dth-hr-form-action--primary:hover,
    html body .fi-btn.dth-hr-modal-action--primary:hover {
        background: linear-gradient(180deg, #2f68da 0%, #1d4ed8 100%) !important;
        border-color: #1d4ed8 !important;
        transform: translateY(-1px);
    }

    html body .fi-btn.dth-hr-form-action--secondary,
    html body .fi-btn.dth-hr-modal-action--secondary {
        min-height: 42px !important;
        padding: 0 16px !important;
        border: 1px solid var(--dth-hr-border) !important;
        border-radius: 12px !important;
        background: #fff !important;
        color: var(--dth-hr-text) !important;
        box-shadow: none !important;
        font-weight: 720 !important;
    }
    html body .fi-btn.dth-hr-form-action--secondary *,
    html body .fi-btn.dth-hr-modal-action--secondary * { color: var(--dth-hr-text) !important; }
    html body .fi-btn.dth-hr-form-action--secondary:hover,
    html body .fi-btn.dth-hr-modal-action--secondary:hover {
        background: #f8fafc !important;
        border-color: #cbd5e1 !important;
    }

    .dth-hr-entry-action.fi-btn {
        min-height: 40px !important;
        padding: 0 14px !important;
        border-radius: 12px !important;
        border: 1px solid #dbe3ec !important;
        background: #fff !important;
        box-shadow: 0 3px 10px rgba(15,23,42,.04) !important;
        font-size: .8rem !important;
        font-weight: 720 !important;
        gap: .48rem !important;
    }
    .dth-hr-entry-action.fi-btn:hover { transform: translateY(-1px); background: #fbfcfe !important; border-color: #cbd5e1 !important; }
    .dth-hr-entry-action--blue.fi-btn, .dth-hr-entry-action--blue.fi-btn * { color: var(--dth-hr-primary) !important; }
    .dth-hr-entry-action--violet.fi-btn, .dth-hr-entry-action--violet.fi-btn * { color: var(--dth-hr-violet) !important; }
    .dth-hr-entry-action--amber.fi-btn, .dth-hr-entry-action--amber.fi-btn * { color: var(--dth-hr-amber) !important; }
    .dth-hr-entry-action--green.fi-btn, .dth-hr-entry-action--green.fi-btn * { color: var(--dth-hr-green) !important; }

    /* Import / export actions follow the same compact treatment used by CRM. */
    .dth-hr-data-action.fi-btn {
        min-height: 38px !important;
        padding: 0 13px !important;
        border: 1px solid #dbe3ec !important;
        border-radius: 10px !important;
        background: #fff !important;
        box-shadow: 0 2px 6px rgba(15,23,42,.035) !important;
        font-size: .76rem !important;
        font-weight: 700 !important;
        gap: .42rem !important;
    }
    .dth-hr-data-action--import.fi-btn, .dth-hr-data-action--import.fi-btn * { color: var(--dth-hr-primary) !important; }
    .dth-hr-data-action--excel.fi-btn, .dth-hr-data-action--excel.fi-btn * { color: #15803d !important; }
    .dth-hr-data-action--csv.fi-btn, .dth-hr-data-action--csv.fi-btn * { color: #475467 !important; }
    .dth-hr-data-action.fi-btn:hover {
        transform: translateY(-1px);
        border-color: #cbd5e1 !important;
        background: #fbfcfe !important;
        box-shadow: 0 5px 12px rgba(15,23,42,.05) !important;
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
        border-color: var(--dth-hr-border-strong) !important;
        border-radius: 12px !important;
        background: #fff !important;
        box-shadow: none !important;
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
    .fi-input, .fi-select-input, .fi-input-wrp-input { color: var(--dth-hr-text) !important; }
    .fi-input-wrp:focus-within,
    .choices.is-focused .choices__inner,
    .trix-button-group:focus-within,
    .fi-fo-rich-editor:focus-within,
    .fi-ta-search-field .fi-input-wrp:focus-within {
        border-color: #78a5f7 !important;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .10) !important;
    }
    .fi-fo-field-wrp-label span,
    .fi-fo-field-wrp-label label,
    .fi-ta-header-cell-label { color: #344054 !important; font-weight: 700 !important; }
    .fi-fo-field-wrp-helper-text, .fi-fo-field-wrp-hint { color: var(--dth-hr-muted) !important; }

    .fi-ta { overflow: visible !important; background: #fff !important; }
    .fi-ta-header { padding: .95rem 1rem 0 !important; gap: .85rem !important; }
    .fi-ta-header-toolbar { gap: .7rem !important; }
    .fi-ta-search-field { max-width: 470px; }

    /* Keep Filament's native filter popover layout; only style its controls. */
    .fi-ta-filters .fi-input-wrp,
    .fi-ta-filters .fi-select-input,
    .fi-ta-filters .choices__inner {
        min-height: 42px !important;
        border: 1px solid var(--dth-hr-border-strong) !important;
        border-radius: 10px !important;
        background: #fff !important;
        box-shadow: none !important;
    }
    .fi-ta-filters .fi-input-wrp:focus-within,
    .fi-ta-filters .choices.is-focused .choices__inner {
        border-color: #78a5f7 !important;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .10) !important;
    }

    .fi-ta-header-cell { background: #fafcff !important; border-bottom: 1px solid #edf1f6 !important; }
    .fi-ta-header-cell, .fi-ta-cell { padding-top: .86rem !important; padding-bottom: .86rem !important; }
    .fi-ta-row { background: #fff; transition: background .12s ease; }
    .fi-ta-row:hover { background: #f9fbff !important; }
    .fi-ta-row:not(:last-child) .fi-ta-cell { border-bottom: 1px solid #f0f3f7 !important; }
    .fi-badge { border-radius: 999px !important; padding: .24rem .62rem !important; font-size: .72rem !important; font-weight: 720 !important; box-shadow: none !important; }

    .fi-pagination { gap: .75rem !important; padding: .9rem 1rem 1rem !important; }
    .fi-pagination-items .fi-pagination-item { width: 38px; height: 38px; border-radius: 11px !important; border: 1px solid var(--dth-hr-border) !important; background: #fff !important; }
    .fi-pagination-items .fi-pagination-item[aria-current="page"] { background: var(--dth-hr-primary) !important; border-color: var(--dth-hr-primary) !important; color: #fff !important; }

    .fi-tabs {
        display: inline-flex !important;
        width: max-content !important;
        gap: .45rem !important;
        padding: .42rem !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 16px !important;
        background: rgba(255,255,255,.92) !important;
        box-shadow: 0 10px 24px rgba(15,23,42,.04) !important;
    }
    .fi-tabs-item {
        min-height: 40px !important;
        padding: .55rem 1rem !important;
        border-radius: 12px !important;
        border: 1px solid transparent !important;
        background: transparent !important;
        color: #667085 !important;
        font-weight: 720 !important;
        transition: all .15s ease;
    }
    .fi-tabs-item:hover { background: #f8fafc !important; color: #344054 !important; }
    .fi-tabs-item[aria-selected="true"] {
        background: linear-gradient(180deg,#f5f9ff 0%,#eef5ff 100%) !important;
        border-color: #c9dafd !important;
        color: var(--dth-hr-primary) !important;
        box-shadow: 0 6px 16px rgba(37,99,235,.08) !important;
    }

    .fi-fo-actions { gap: .65rem; padding-top: .25rem; }
    .fi-form { gap: 1rem !important; }
    .fi-section-content { row-gap: 1rem !important; }
    .fi-modal-window { overflow: hidden; }
    .fi-modal-content { max-height: min(82vh, 980px); }

    .dark .fi-main { background: #0f172a !important; }
    .dark .fi-section, .dark .fi-ta, .dark .fi-modal-window { background: #111827 !important; border-color: #334155 !important; }
    .dark .fi-header-heading, .dark .fi-section-header-heading { color: #f8fafc !important; }
    .dark .fi-header-subheading, .dark .fi-section-header-description { color: #94a3b8 !important; }
    .dark .fi-input-wrp, .dark .fi-select-input, .dark .choices__inner { background: #111827 !important; border-color: #475569 !important; }
    .dark .fi-ta-header-cell, .dark .fi-ta-row { background: #111827 !important; }
    .dark .fi-ta-row:hover { background: #172033 !important; }

    @media (max-width: 768px) {
        .fi-header { align-items: flex-start; }
        .fi-header-actions { width: 100%; flex-wrap: wrap; }
        .fi-header-actions .fi-btn { width: auto; }
        .dth-hr-page-title__icon { width: 2.8rem; height: 2.8rem; flex-basis: 2.8rem; }
        .fi-tabs { width: 100% !important; display: flex !important; }
        .fi-tabs-item { flex: 1 1 0; justify-content: center; }
    }
</style>
@endif
