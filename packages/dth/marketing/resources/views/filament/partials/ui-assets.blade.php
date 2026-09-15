@php
    $path = trim(request()->path(), '/');
    $marketingSegments = [
        'marketing-dashboard',
        'marketing-campaigns',
        'landing-pages',
        'landing-page-submissions',
        'form-templates',
        'contact-lists',
        'segments',
    ];
    $isMarketingUiPage = collect($marketingSegments)->contains(fn (string $segment): bool => str_contains($path, $segment));
@endphp

@if ($isMarketingUiPage)
<style>
    :root {
        --dth-mkt-bg: #f5f7fb;
        --dth-mkt-surface: #ffffff;
        --dth-mkt-border: #e5eaf1;
        --dth-mkt-border-strong: #d7dee9;
        --dth-mkt-heading: #101828;
        --dth-mkt-text: #344054;
        --dth-mkt-muted: #7b879c;
        --dth-mkt-indigo: #5b5cf0;
        --dth-mkt-indigo-soft: #eef0ff;
        --dth-mkt-orange: #f59e0b;
        --dth-mkt-green: #12b76a;
        --dth-mkt-blue: #2f80ed;
        --dth-mkt-violet: #8b5cf6;
        --dth-mkt-red: #ef4444;
        --dth-mkt-shadow: 0 10px 30px rgba(15, 23, 42, .045);
        --dth-mkt-radius-lg: 16px;
        --dth-mkt-radius-md: 12px;
    }

    .fi-main {
        background:
            radial-gradient(circle at 82% 0%, rgba(91, 92, 240, .04), transparent 28rem),
            linear-gradient(180deg, #f8faff 0%, var(--dth-mkt-bg) 100%) !important;
    }

    .fi-main .fi-page { gap: 16px !important; }

    .fi-header { align-items: center; margin-bottom: .15rem; }
    .fi-header-heading {
        color: var(--dth-mkt-heading) !important;
        font-size: 1.82rem !important;
        line-height: 1.12 !important;
        font-weight: 820 !important;
        letter-spacing: -.036em !important;
    }
    .fi-header-subheading { color: var(--dth-mkt-muted) !important; font-size: .9rem !important; }

    .dth-mkt-page-title { display:inline-flex; align-items:center; gap:.85rem; }
    .dth-mkt-page-title__icon {
        width:3.35rem; height:3.35rem; flex:0 0 3.35rem; display:grid; place-items:center;
        color:var(--dth-mkt-indigo); border:1px solid #dfe2ff; border-radius:1rem;
        background:linear-gradient(145deg,#f1f2ff 0%,#fff 100%);
        box-shadow:0 10px 26px rgba(91,92,240,.09);
    }
    .dth-mkt-page-title__icon svg { width:1.55rem; height:1.55rem; }
    .dth-mkt-page-title--amber .dth-mkt-page-title__icon { color:#e98400; border-color:#f8e3bc; background:linear-gradient(145deg,#fff8e8 0%,#fff 100%); }
    .dth-mkt-page-title--green .dth-mkt-page-title__icon { color:#0f9f5a; border-color:#d7f1e4; background:linear-gradient(145deg,#effdf5 0%,#fff 100%); }
    .dth-mkt-page-title--blue .dth-mkt-page-title__icon { color:#2563eb; border-color:#dbe7ff; background:linear-gradient(145deg,#f1f6ff 0%,#fff 100%); }
    .dth-mkt-page-title--rose .dth-mkt-page-title__icon { color:#e84c78; border-color:#f8dce5; background:linear-gradient(145deg,#fff3f7 0%,#fff 100%); }

    .fi-section, .fi-ta, .fi-wi > div, .fi-modal-window {
        border-color:var(--dth-mkt-border) !important;
        border-radius:var(--dth-mkt-radius-lg) !important;
        box-shadow:var(--dth-mkt-shadow) !important;
    }
    .fi-section { background:rgba(255,255,255,.98) !important; }
    .fi-section-header { border-bottom:1px solid #eef2f6; padding-bottom:.9rem !important; }
    .fi-section-header-heading { color:var(--dth-mkt-heading) !important; font-weight:800 !important; letter-spacing:-.02em; }
    .fi-section-header-description { color:var(--dth-mkt-muted) !important; }

    .fi-header-actions .fi-btn, .fi-fo-actions .fi-btn, .fi-ta-header-toolbar .fi-btn, .fi-modal-footer-actions .fi-btn {
        min-height:42px; border-radius:12px !important; font-weight:720 !important;
    }
    .fi-btn-color-primary {
        background:linear-gradient(180deg,#6969f6 0%,#5657e8 100%) !important;
        border-color:#5b5cf0 !important; color:#fff !important;
        box-shadow:0 9px 20px rgba(91,92,240,.18) !important;
    }
    .fi-btn-color-gray, .fi-btn-outlined {
        background:#fff !important; color:var(--dth-mkt-text) !important;
        border-color:var(--dth-mkt-border) !important; box-shadow:none !important;
    }

    /* Report export actions mirror the Email module buttons. */
    .dth-mkt-export-action.fi-btn {
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

    .dth-mkt-export-action.fi-btn:hover {
        transform: translateY(-1px);
        background: #fbfcfe !important;
        border-color: #d4dce8 !important;
    }

    /* PDF */
    .dth-mkt-export-action--pdf.fi-btn,
    .dth-mkt-export-action--pdf.fi-btn svg,
    .dth-mkt-export-action--pdf.fi-btn span {
        color: #c87500 !important;
    }

    /* Excel */
    .dth-mkt-export-action--excel.fi-btn,
    .dth-mkt-export-action--excel.fi-btn svg,
    .dth-mkt-export-action--excel.fi-btn span {
        color: #15803d !important;
    }

    /* CSV */
    .dth-mkt-export-action--csv.fi-btn,
    .dth-mkt-export-action--csv.fi-btn svg,
    .dth-mkt-export-action--csv.fi-btn span {
        color: #344054 !important;
    }

    .dth-mkt-export-action.fi-btn svg {
        width: 17px;
        height: 17px;
    }

    /* Keep Filament form/search/filter controls visibly bounded. Filament v4 often
       puts the visual border on the wrapper, so setting border-color alone is not enough. */
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
        border-width:1px !important;
        border-style:solid !important;
        border-color:var(--dth-mkt-border-strong) !important;
        border-radius:12px !important;
        background:#fff !important;
        box-shadow:none !important;
    }
    .fi-input-wrp { min-height:44px; }
    .fi-input-wrp .fi-input,
    .fi-input-wrp .fi-input-wrp-input,
    .fi-input-wrp .fi-select-input,
    .fi-fo-rich-editor .ProseMirror {
        border:0 !important;
        border-color:transparent !important;
        background:transparent !important;
        box-shadow:none !important;
    }
    .fi-input,
    .fi-select-input,
    .fi-input-wrp-input { color:var(--dth-mkt-text) !important; }
    .fi-input-wrp:focus-within,
    .choices.is-focused .choices__inner,
    .trix-button-group:focus-within,
    .fi-fo-rich-editor:focus-within,
    .fi-ta-search-field .fi-input-wrp:focus-within {
        border-color:#8b8cf7 !important;
        box-shadow:0 0 0 4px rgba(91,92,240,.12) !important;
    }
    .fi-fo-field-wrp .fi-input-wrp:not(:focus-within),
    .fi-fo-field-wrp .fi-select-input:not(:focus),
    .fi-fo-rich-editor:not(:focus-within) {
        border-width:1px !important;
        border-style:solid !important;
        border-color:var(--dth-mkt-border-strong) !important;
    }
    .fi-fo-field-wrp-label span, .fi-fo-field-wrp-label label, .fi-ta-header-cell-label { color:#344054 !important; font-weight:700 !important; }
    .fi-fo-field-wrp-helper-text, .fi-fo-field-wrp-hint { color:var(--dth-mkt-muted) !important; }

    .fi-ta { overflow:hidden; background:#fff !important; }
    .fi-ta-header { padding:.95rem 1rem 0 !important; gap:.85rem !important; }
    .fi-ta-header-toolbar { gap:.7rem !important; }
    .fi-ta-search-field { max-width:470px; }
    .fi-ta-filters-above-content { gap:.75rem !important; padding-inline:1rem; padding-bottom:.55rem; }
    .fi-ta-filters { padding:0 !important; border:0 !important; background:transparent !important; box-shadow:none !important; }
    .fi-ta-header-cell { background:#fbfcfe !important; border-bottom:1px solid #edf1f6 !important; }
    .fi-ta-header-cell, .fi-ta-cell { padding-top:.86rem !important; padding-bottom:.86rem !important; }
    .fi-ta-row { background:#fff; transition:background .12s ease; }
    .fi-ta-row:hover { background:#fbfcff !important; }
    .fi-ta-row:not(:last-child) .fi-ta-cell { border-bottom:1px solid #f0f3f7 !important; }

    .fi-badge { border-radius:999px !important; padding:.24rem .62rem !important; font-size:.72rem !important; font-weight:720 !important; box-shadow:none !important; }

    .fi-pagination { gap:.75rem !important; padding:.9rem 1rem 1rem !important; }
    .fi-pagination-items .fi-pagination-item {
        width:38px; height:38px; border-radius:11px !important; border:1px solid var(--dth-mkt-border) !important; background:#fff !important;
    }
    .fi-pagination-items .fi-pagination-item[aria-current="page"] { background:#5b5cf0 !important; border-color:#5b5cf0 !important; color:#fff !important; }

    .fi-tabs { border:0 !important; background:transparent !important; box-shadow:none !important; padding:0 !important; }
    .fi-tabs-item { min-height:35px !important; padding:.4rem .82rem !important; border-radius:999px !important; border:1px solid transparent !important; background:#f5f7fb !important; color:#667085 !important; font-weight:700 !important; }
    .fi-tabs-item[aria-selected="true"] { background:#eff0ff !important; border-color:#cfd2ff !important; color:#4d4ee0 !important; }

    .fi-fo-actions { gap:.65rem; padding-top:.25rem; }
    .fi-form { gap:1rem !important; }
    .fi-section-content { row-gap:1rem !important; }
    .fi-modal-window { overflow:hidden; }
    .ProseMirror { min-height:220px; }

    /* Marketing dashboard */
    .dth-mkt-dashboard-header { display:flex; align-items:center; justify-content:space-between; gap:18px; min-height:66px; }
    .dth-mkt-dashboard-heading { display:flex; align-items:center; gap:16px; min-width:0; }
    .dth-mkt-dashboard-icon { width:54px; height:54px; flex:0 0 54px; display:grid; place-items:center; border-radius:16px; border:1px solid #dfe2ff; color:var(--dth-mkt-indigo); background:linear-gradient(145deg,#f0f1ff,#fff); box-shadow:0 10px 24px rgba(91,92,240,.10); }
    .dth-mkt-dashboard-icon svg { width:26px; height:26px; }
    .dth-mkt-dashboard-title { margin:0; color:var(--dth-mkt-heading); font-size:1.75rem; font-weight:820; letter-spacing:-.035em; line-height:1.1; }
    .dth-mkt-dashboard-subtitle { margin-top:6px; color:var(--dth-mkt-muted); font-size:.88rem; }

    @media (max-width: 768px) {
        .fi-header { align-items:flex-start; }
        .fi-header-actions { width:100%; flex-wrap:wrap; }
        .dth-mkt-page-title__icon { width:2.8rem; height:2.8rem; flex-basis:2.8rem; }
        .dth-mkt-dashboard-header { align-items:flex-start; flex-direction:column; }
    }
</style>
@endif
