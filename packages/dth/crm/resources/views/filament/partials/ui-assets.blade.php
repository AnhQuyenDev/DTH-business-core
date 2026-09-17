@php
    $path = trim(request()->path(), '/');
    $crmSegments = [
        'crm-overview', 'contacts', 'personal-contacts', 'business-contacts', 'companies', 'leads',
        'contact-qualifications', 'customers', 'crm-agent-profiles', 'customer-distribution-batches',
    ];
    $isCrmUiPage = collect($crmSegments)->contains(fn (string $segment): bool => str_contains($path, $segment));
@endphp

@if ($isCrmUiPage)
<style>
    :root {
        --dth-crm-bg: #f5f8fb;
        --dth-crm-surface: #ffffff;
        --dth-crm-border: #e2e8f0;
        --dth-crm-border-strong: #cfd9e6;
        --dth-crm-heading: #10212b;
        --dth-crm-text: #344054;
        --dth-crm-muted: #7a8899;
        --dth-crm-primary: #0f766e;
        --dth-crm-primary-strong: #0b655e;
        --dth-crm-primary-soft: #ecfdf8;
        --dth-crm-blue: #2563eb;
        --dth-crm-green: #16a34a;
        --dth-crm-amber: #d97706;
        --dth-crm-red: #dc2626;
        --dth-crm-violet: #7c3aed;
        --dth-crm-shadow: 0 10px 30px rgba(15, 23, 42, .045);
        --dth-crm-radius-lg: 16px;
        --dth-crm-radius-md: 12px;
    }

    .fi-main {
        background:
            radial-gradient(circle at 84% 1%, rgba(15,118,110,.045), transparent 30rem),
            linear-gradient(180deg, #f8fbfd 0%, var(--dth-crm-bg) 100%) !important;
    }
    .fi-main .fi-page { gap: 16px !important; }
    .fi-header { align-items:center; margin-bottom:.15rem; }
    .fi-header-heading { color:var(--dth-crm-heading) !important; font-size:1.82rem !important; line-height:1.12 !important; font-weight:820 !important; letter-spacing:-.036em !important; }
    .fi-header-subheading { color:var(--dth-crm-muted) !important; font-size:.9rem !important; }

    .dth-crm-page-title { display:inline-flex; align-items:center; gap:.85rem; }
    .dth-crm-page-title__icon { width:3.35rem; height:3.35rem; flex:0 0 3.35rem; display:grid; place-items:center; color:var(--dth-crm-primary); border:1px solid #ccebe6; border-radius:1rem; background:linear-gradient(145deg,#ebfcf8 0%,#fff 100%); box-shadow:0 10px 26px rgba(15,118,110,.09); }
    .dth-crm-page-title__icon svg { width:1.55rem; height:1.55rem; }
    .dth-crm-page-title--blue .dth-crm-page-title__icon { color:#2563eb; border-color:#dbe7ff; background:linear-gradient(145deg,#f1f6ff 0%,#fff 100%); }
    .dth-crm-page-title--amber .dth-crm-page-title__icon { color:#d97706; border-color:#f5dfbc; background:linear-gradient(145deg,#fff8e8 0%,#fff 100%); }
    .dth-crm-page-title--violet .dth-crm-page-title__icon { color:#7c3aed; border-color:#eadcff; background:linear-gradient(145deg,#f8f2ff 0%,#fff 100%); }
    .dth-crm-page-title--rose .dth-crm-page-title__icon { color:#e11d48; border-color:#f8dbe4; background:linear-gradient(145deg,#fff3f6 0%,#fff 100%); }

    .fi-section, .fi-ta, .fi-wi > div, .fi-modal-window {
        border-color:var(--dth-crm-border) !important;
        border-radius:var(--dth-crm-radius-lg) !important;
        box-shadow:var(--dth-crm-shadow) !important;
    }
    .fi-section { background:rgba(255,255,255,.98) !important; }
    .fi-section-header { padding-bottom:.9rem !important; border-bottom:1px solid #edf2f6; }
    .fi-section-header-heading { color:var(--dth-crm-heading) !important; font-weight:800 !important; letter-spacing:-.02em; }
    .fi-section-header-description { color:var(--dth-crm-muted) !important; }

    /* All visible CRM actions follow the module tone and have room for icons. */
    .fi-header-actions .fi-btn, .fi-fo-actions .fi-btn, .fi-ta-header-toolbar .fi-btn, .fi-modal-footer-actions .fi-btn {
        min-height:42px; border-radius:12px !important; font-weight:720 !important; gap:.45rem !important;
    }
    .fi-btn-color-primary {
        background:linear-gradient(180deg,#14877d 0%,#0f766e 100%) !important;
        border-color:#0f766e !important; color:#fff !important;
        box-shadow:0 9px 20px rgba(15,118,110,.18) !important;
    }
    .fi-btn-color-gray, .fi-btn-outlined {
        background:#fff !important; color:var(--dth-crm-text) !important;
        border:1px solid var(--dth-crm-border) !important; box-shadow:none !important;
    }
    .fi-btn-color-danger { box-shadow:0 8px 18px rgba(220,38,38,.12) !important; }
    .fi-btn-color-success { box-shadow:0 8px 18px rgba(22,163,74,.12) !important; }

    /* CRM page entry actions that open forms/modals should look like first-class module controls. */
    .dth-crm-entry-action.fi-btn {
        min-height:40px !important;
        padding:0 14px !important;
        border-radius:12px !important;
        border:1px solid #dbe3ec !important;
        background:#fff !important;
        box-shadow:0 3px 10px rgba(15,23,42,.04) !important;
        font-size:.8rem !important;
        font-weight:720 !important;
        gap:.48rem !important;
    }
    .dth-crm-entry-action.fi-btn:hover {
        transform:translateY(-1px);
        background:#fbfcfe !important;
        border-color:#cbd5e1 !important;
    }
    .dth-crm-entry-action--teal.fi-btn, .dth-crm-entry-action--teal.fi-btn * { color:var(--dth-crm-primary) !important; }
    .dth-crm-entry-action--blue.fi-btn, .dth-crm-entry-action--blue.fi-btn * { color:var(--dth-crm-blue) !important; }
    .dth-crm-entry-action--amber.fi-btn, .dth-crm-entry-action--amber.fi-btn * { color:var(--dth-crm-amber) !important; }
    .dth-crm-entry-action--violet.fi-btn, .dth-crm-entry-action--violet.fi-btn * { color:var(--dth-crm-violet) !important; }

    /* Inputs/search/filter controls must stay visibly bounded on Filament v4. */
    .fi-input-wrp, .fi-select-input, .choices__inner, .trix-button-group,
    .fi-fo-field-wrp .fi-input-wrp, .fi-fo-field-wrp .fi-select-input, .fi-fo-rich-editor,
    .fi-ta-search-field .fi-input-wrp, .fi-ta-filters .fi-input-wrp, .fi-ta-filters .fi-select-input {
        border-width:1px !important; border-style:solid !important; border-color:var(--dth-crm-border-strong) !important;
        border-radius:12px !important; background:#fff !important; box-shadow:none !important;
    }
    .fi-input-wrp { min-height:44px; }
    .fi-input-wrp .fi-input, .fi-input-wrp .fi-input-wrp-input, .fi-input-wrp .fi-select-input, .fi-fo-rich-editor .ProseMirror {
        border:0 !important; background:transparent !important; box-shadow:none !important;
    }
    .fi-input, .fi-select-input, .fi-input-wrp-input { color:var(--dth-crm-text) !important; }
    .fi-input-wrp:focus-within, .choices.is-focused .choices__inner, .trix-button-group:focus-within,
    .fi-fo-rich-editor:focus-within, .fi-ta-search-field .fi-input-wrp:focus-within {
        border-color:#42b7ad !important; box-shadow:0 0 0 4px rgba(15,118,110,.11) !important;
    }
    .fi-fo-field-wrp-label span, .fi-fo-field-wrp-label label, .fi-ta-header-cell-label { color:#344054 !important; font-weight:700 !important; }
    .fi-fo-field-wrp-helper-text, .fi-fo-field-wrp-hint { color:var(--dth-crm-muted) !important; }

    .fi-ta { overflow:visible !important; background:#fff !important; }
    .fi-ta-header { padding:.95rem 1rem 0 !important; gap:.85rem !important; }
    .fi-ta-header-toolbar { gap:.7rem !important; }
    .fi-ta-search-field { max-width:470px; }

    /*
    * Native Filament table filters.
    * Chỉ style form controls, không style dropdown container.
    */
    .fi-ta-filters .fi-input-wrp,
    .fi-ta-filters .fi-select-input,
    .fi-ta-filters .choices__inner {
        min-height: 42px !important;
        border: 1px solid var(--dth-crm-border-strong) !important;
        border-radius: 10px !important;
        background: #fff !important;
        box-shadow: none !important;
    }

    .fi-ta-filters .fi-input-wrp:focus-within,
    .fi-ta-filters .choices.is-focused .choices__inner {
        border-color: #42b7ad !important;
        box-shadow: 0 0 0 4px rgba(15, 118, 110, .11) !important;
    }
    /* Table header cells */
    .fi-ta-header-cell { background:#fafcfd !important; border-bottom:1px solid #edf1f6 !important; }
    .fi-ta-header-cell, .fi-ta-cell { padding-top:.86rem !important; padding-bottom:.86rem !important; }
    .fi-ta-row { background:#fff; transition:background .12s ease; }
    .fi-ta-row:hover { background:#f9fcfc !important; }
    .fi-ta-row:not(:last-child) .fi-ta-cell { border-bottom:1px solid #f0f3f7 !important; }
    .fi-badge { border-radius:999px !important; padding:.24rem .62rem !important; font-size:.72rem !important; font-weight:720 !important; box-shadow:none !important; }

    .fi-pagination { gap:.75rem !important; padding:.9rem 1rem 1rem !important; }
    .fi-pagination-items .fi-pagination-item { width:38px; height:38px; border-radius:11px !important; border:1px solid var(--dth-crm-border) !important; background:#fff !important; }
    .fi-pagination-items .fi-pagination-item[aria-current="page"] { background:var(--dth-crm-primary) !important; border-color:var(--dth-crm-primary) !important; color:#fff !important; }

    /* Contact tabs become a segmented control instead of plain loose pills. */
    .fi-tabs {
        display:inline-flex !important;
        width:max-content !important;
        gap:.45rem !important;
        padding:.42rem !important;
        border:1px solid #e2e8f0 !important;
        border-radius:16px !important;
        background:rgba(255,255,255,.92) !important;
        box-shadow:0 10px 24px rgba(15,23,42,.04) !important;
    }
    .fi-tabs-item {
        min-height:40px !important;
        padding:.55rem 1rem !important;
        border-radius:12px !important;
        border:1px solid transparent !important;
        background:transparent !important;
        color:#667085 !important;
        font-weight:720 !important;
        transition:all .15s ease;
    }
    .fi-tabs-item:hover { background:#f8fafc !important; color:#344054 !important; }
    .fi-tabs-item[aria-selected="true"] {
        background:linear-gradient(180deg,#f3fffc 0%,#ebfcf8 100%) !important;
        border-color:#b7e3dc !important;
        color:#0f766e !important;
        box-shadow:0 6px 16px rgba(15,118,110,.09) !important;
    }

    .fi-fo-actions { gap:.65rem; padding-top:.25rem; }
    .fi-form { gap:1rem !important; }
    .fi-section-content { row-gap:1rem !important; }
    /* Keep Filament's native modal scrolling/overflow behavior.
     * Forcing overflow:hidden / a custom max-height clips non-native Select dropdowns
     * and can make the modal footer overlap the form on shorter viewports. */
    .fi-header-actions { position:relative; z-index:5; }
    .ProseMirror { min-height:220px; }

    /* Import/export buttons: white surface, colored semantic icon/text like Email. */
    .dth-crm-data-action.fi-btn { min-height:38px !important; padding:0 13px !important; border:1px solid #dbe3ec !important; border-radius:10px !important; background:#fff !important; box-shadow:0 2px 6px rgba(15,23,42,.035) !important; font-size:.76rem !important; font-weight:700 !important; }
    .dth-crm-data-action--import.fi-btn, .dth-crm-data-action--import.fi-btn * { color:#2563eb !important; }
    .dth-crm-data-action--excel.fi-btn, .dth-crm-data-action--excel.fi-btn * { color:#15803d !important; }
    .dth-crm-data-action--csv.fi-btn, .dth-crm-data-action--csv.fi-btn * { color:#344054 !important; }
    .dth-crm-data-action.fi-btn:hover { transform:translateY(-1px); border-color:#cbd5e1 !important; background:#fbfcfe !important; }

    @media (max-width: 768px) {
        .fi-header { align-items:flex-start; }
        .fi-header-actions { width:100%; flex-wrap:wrap; }
        .fi-header-actions .fi-btn { width:auto; }
        .dth-crm-page-title__icon { width:2.8rem; height:2.8rem; flex-basis:2.8rem; }
        .fi-tabs { width:100% !important; display:flex !important; }
        .fi-tabs-item { flex:1 1 0; justify-content:center; }
    }
</style>
@endif
