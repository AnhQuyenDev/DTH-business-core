@php
    $path = trim(request()->path(), '/');
    $emailUiSegments = [
        'email-dashboard',
        'email-campaigns',
        'email-templates',
        'email-template-categories',
        'email-delivery-logs',
        'email-suppressions',
        'sending-accounts',
        'sending-domains',
    ];

    $isEmailUiPage = collect($emailUiSegments)->contains(fn (string $segment): bool => str_contains($path, $segment));
@endphp

@if ($isEmailUiPage)
    <style>
        :root {
            --dth-email-bg: #f5f7fb;
            --dth-email-surface: #ffffff;
            --dth-email-border: #e5ebf2;
            --dth-email-border-strong: #d7e0eb;
            --dth-email-heading: #111827;
            --dth-email-text: #344054;
            --dth-email-muted: #7b879c;
            --dth-email-orange: #f59e0b;
            --dth-email-orange-deep: #ea8a00;
            --dth-email-blue: #3b82f6;
            --dth-email-green: #22c55e;
            --dth-email-red: #ef4444;
            --dth-email-violet: #8b5cf6;
            --dth-email-radius-lg: 16px;
            --dth-email-radius-md: 12px;
            --dth-email-radius-sm: 10px;
            --dth-email-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
        }

        .fi-main {
            background: linear-gradient(180deg, #f7f9fd 0%, #f3f6fb 100%) !important;
        }

        .fi-main .fi-page {
            gap: 16px;
        }

        .fi-section,
        .fi-ta,
        .fi-wi > div,
        .fi-in-entry,
        .fi-modal-window {
            border-color: var(--dth-email-border) !important;
            border-radius: var(--dth-email-radius-lg) !important;
            box-shadow: var(--dth-email-shadow) !important;
        }

        .fi-section {
            background: var(--dth-email-surface) !important;
        }

        .fi-section-header {
            padding-bottom: .9rem !important;
            border-bottom: 1px solid #eef2f6;
        }

        .fi-section-header-heading,
        .fi-section-content-ctn .fi-section-header-heading {
            color: var(--dth-email-heading) !important;
            font-weight: 800 !important;
            letter-spacing: -.02em;
        }

        .fi-section-header-description,
        .fi-header-subheading {
            color: var(--dth-email-muted) !important;
        }

        .fi-header {
            align-items: center;
            margin-bottom: .1rem;
        }

        .fi-header-heading {
            color: var(--dth-email-heading) !important;
            font-size: 1.8rem !important;
            font-weight: 800 !important;
            letter-spacing: -.035em !important;
        }

        .dth-email-page-title {
            display: inline-flex;
            align-items: center;
            gap: .85rem;
        }

        .dth-email-page-title__icon {
            width: 3.35rem;
            height: 3.35rem;
            flex: 0 0 3.35rem;
            display: grid;
            place-items: center;
            border: 1px solid #f6e3b9;
            border-radius: 1rem;
            color: var(--dth-email-orange);
            background: linear-gradient(180deg, #fff8ea 0%, #ffffff 100%);
            box-shadow: 0 10px 28px rgba(245, 158, 11, .10);
        }

        .dth-email-page-title--violet .dth-email-page-title__icon {
            color: #6d5efc;
            border-color: #e2ddff;
            background: linear-gradient(180deg, #f7f5ff 0%, #ffffff 100%);
            box-shadow: 0 10px 28px rgba(109, 94, 252, .09);
        }

        .dth-email-page-title--green .dth-email-page-title__icon {
            color: #16a34a;
            border-color: #d8f2df;
            background: linear-gradient(180deg, #f1fff5 0%, #ffffff 100%);
            box-shadow: 0 10px 28px rgba(34, 197, 94, .08);
        }

        .dth-email-page-title--blue .dth-email-page-title__icon {
            color: #2563eb;
            border-color: #dbe8ff;
            background: linear-gradient(180deg, #f3f8ff 0%, #ffffff 100%);
            box-shadow: 0 10px 28px rgba(37, 99, 235, .08);
        }

        .dth-email-page-title--red .dth-email-page-title__icon {
            color: #ef4444;
            border-color: #fde0e0;
            background: linear-gradient(180deg, #fff6f6 0%, #ffffff 100%);
            box-shadow: 0 10px 28px rgba(239, 68, 68, .08);
        }

        .dth-email-page-title__icon svg {
            width: 1.55rem;
            height: 1.55rem;
        }

        .fi-header-actions .fi-btn,
        .fi-fo-actions .fi-btn,
        .fi-ac-btn-action,
        .fi-ta-header-toolbar .fi-btn,
        .fi-pagination .fi-btn {
            min-height: 42px;
            border-radius: 12px !important;
            font-weight: 700 !important;
        }

        .fi-btn-color-primary,
        .fi-ac-btn-action[data-color="primary"] {
            box-shadow: 0 10px 22px rgba(245, 158, 11, .18) !important;
        }

        .fi-btn-color-primary {
            background: linear-gradient(180deg, #ffbd12 0%, #f6a800 100%) !important;
            border-color: #f6b21a !important;
            color: #5d3a00 !important;
        }

        .fi-btn-color-gray,
        .fi-btn-color-info,
        .fi-btn-color-secondary,
        .fi-btn-outlined {
            border-color: var(--dth-email-border) !important;
            background: #fff !important;
            color: var(--dth-email-text) !important;
            box-shadow: none !important;
        }


    /* DTH_FORM_ACTION_TONE:email */
    /* Explicit resource form actions, following the Human Resource pattern.
       These selectors are intentionally stronger than Filament / host theme
       primary colors so each module keeps its own identity. */
    html body .fi-btn.dth-email-form-action--primary {
        min-height: 42px !important;
        padding: 0 16px !important;
        border: 1px solid #f6a800 !important;
        border-radius: 12px !important;
        background: linear-gradient(180deg, #ffbd12 0%, #f6a800 100%) !important;
        color: #5d3a00 !important;
        box-shadow: 0 9px 20px rgba(245, 158, 11, .18) !important;
        font-weight: 720 !important;
        gap: .45rem !important;
    }
    html body .fi-btn.dth-email-form-action--primary * { color: #5d3a00 !important; }
    html body .fi-btn.dth-email-form-action--primary:hover {
        background: linear-gradient(180deg, #f6a800 0%, #ea8a00 100%) !important;
        border-color: #ea8a00 !important;
        transform: translateY(-1px);
    }

    html body .fi-btn.dth-email-form-action--secondary {
        min-height: 42px !important;
        padding: 0 16px !important;
        border: 1px solid var(--dth-email-border) !important;
        border-radius: 12px !important;
        background: #fff !important;
        color: var(--dth-email-text) !important;
        box-shadow: none !important;
        font-weight: 720 !important;
        gap: .45rem !important;
    }
    html body .fi-btn.dth-email-form-action--secondary * { color: var(--dth-email-text) !important; }
    html body .fi-btn.dth-email-form-action--secondary:hover {
        background: #f8fafc !important;
        border-color: #cbd5e1 !important;
    }

    /* Modal submit/cancel actions on the module's own pages use the same tone. */
    html body .fi-main .fi-modal-footer-actions .fi-btn.fi-btn-color-primary,
    html body .fi-modal-footer-actions .fi-btn.fi-btn-color-primary {
        background: linear-gradient(180deg, #ffbd12 0%, #f6a800 100%) !important;
        border-color: #f6a800 !important;
        color: #5d3a00 !important;
        box-shadow: 0 9px 20px rgba(245, 158, 11, .18) !important;
    }
    html body .fi-main .fi-modal-footer-actions .fi-btn.fi-btn-color-primary *,
    html body .fi-modal-footer-actions .fi-btn.fi-btn-color-primary * { color: #5d3a00 !important; }
    html body .fi-main .fi-modal-footer-actions .fi-btn.fi-btn-color-gray,
    html body .fi-modal-footer-actions .fi-btn.fi-btn-color-gray {
        background: #fff !important;
        color: var(--dth-email-text) !important;
        border: 1px solid var(--dth-email-border) !important;
        box-shadow: none !important;
    }

        .fi-input-wrp,
        .fi-select-input,
        .choices__inner,
        .trix-button-group,
        .fi-fo-field-wrp .fi-input-wrp,
        .fi-fo-field-wrp .fi-select-input,
        .fi-fo-rich-editor,
        .fi-ta-search-field .fi-input-wrp,
        .fi-ta-filters .fi-input-wrp {
            border-width: 1px !important;
            border-style: solid !important;
            border-color: var(--dth-email-border-strong) !important;
            border-radius: 12px !important;
            box-shadow: none !important;
            background: #fff !important;
        }

        .fi-input-wrp {
            min-height: 44px;
        }

        .fi-input-wrp .fi-input,
        .fi-input-wrp .fi-input-wrp-input,
        .fi-input-wrp .fi-select-input,
        .fi-fo-rich-editor .ProseMirror {
            border: 0 !important;
            border-color: transparent !important;
            background: transparent !important;
            box-shadow: none !important;
        }

        .fi-input,
        .fi-select-input,
        .fi-input-wrp-input {
            color: var(--dth-email-text) !important;
        }

        .fi-input-wrp:focus-within,
        .choices.is-focused .choices__inner,
        .trix-button-group:focus-within,
        .fi-fo-rich-editor:focus-within,
        .fi-ta-search-field .fi-input-wrp:focus-within {
            border-color: #fec84b !important;
            box-shadow: 0 0 0 4px rgba(254, 200, 75, .18) !important;
        }

        .fi-fo-field-wrp .fi-input-wrp:not(:focus-within),
        .fi-fo-field-wrp .fi-select-input:not(:focus),
        .fi-fo-rich-editor:not(:focus-within),
        .fi-fo-rich-editor .ProseMirror:not(:focus) {
            border-color: var(--dth-email-border-strong) !important;
            border-width: 1px !important;
            border-style: solid !important;
        }

        .fi-fo-rich-editor .ProseMirror:not(:focus) {
            border: 0 !important;
        }

        .fi-fo-field-wrp-label span,
        .fi-fo-field-wrp-label label,
        .fi-ta-header-cell-label,
        .fi-ta-summary-header-cell {
            color: #364152 !important;
            font-weight: 700 !important;
        }

        .fi-fo-field-wrp-helper-text,
        .fi-fo-field-wrp-hint,
        .fi-fo-field-wrp-label .fi-fo-field-wrp-hint {
            color: var(--dth-email-muted) !important;
        }

        .fi-ta {
            overflow: hidden;
            background: var(--dth-email-surface);
        }

        .fi-ta-header {
            padding: .95rem 1rem 0 !important;
            gap: .85rem !important;
        }

        .fi-ta-header-toolbar {
            gap: .75rem !important;
        }

        .fi-ta-search-field {
            max-width: 470px;
        }

        /* Native Filament table filters.
           Do not override dropdown / popover geometry. Filament owns placement,
           width, padding, overflow and responsiveness. Email only keeps the
           field border and focus tone inside the native filter panel. */
        .fi-ta-filters .fi-input-wrp,
        .fi-ta-filters .fi-select-input,
        .fi-ta-filters .choices__inner {
            min-height: 42px !important;
            border: 1px solid var(--dth-email-border-strong) !important;
            border-radius: 10px !important;
            background: #fff !important;
            box-shadow: none !important;
        }

        .fi-ta-filters .fi-input-wrp:focus-within,
        .fi-ta-filters .choices.is-focused .choices__inner {
            border-color: #f3b43f !important;
            box-shadow: 0 0 0 4px rgba(245, 158, 11, .11) !important;
        }

        .fi-ta-content,
        .fi-ta-table {
            background: transparent !important;
        }

        .fi-ta-header-cell,
        .fi-ta-cell {
            padding-top: .9rem !important;
            padding-bottom: .9rem !important;
        }

        .fi-ta-header-cell {
            background: #fff !important;
            border-bottom: 1px solid #edf1f6 !important;
        }

        .fi-ta-row {
            background: #fff;
        }

        .fi-ta-row:not(:last-child) .fi-ta-cell {
            border-bottom: 1px solid #f0f3f7 !important;
        }

        .fi-badge,
        .fi-ta-cell .fi-badge {
            border-radius: 999px !important;
            padding: .24rem .65rem !important;
            font-size: .72rem !important;
            font-weight: 700 !important;
            box-shadow: none !important;
        }

        .fi-pagination {
            gap: .75rem !important;
            padding: .85rem 1rem 1rem !important;
        }

        .fi-pagination .fi-select-input,
        .fi-pagination .fi-input-wrp {
            min-height: 36px;
            border-radius: 10px !important;
        }

        .fi-pagination-items .fi-pagination-item {
            width: 38px;
            height: 38px;
            border-radius: 11px !important;
            border: 1px solid var(--dth-email-border) !important;
            background: #fff !important;
        }

        .fi-pagination-items .fi-pagination-item[aria-current="page"] {
            border-color: #f6b21a !important;
            background: #ffb400 !important;
            color: #5d3a00 !important;
        }

        .fi-tabs {
            padding: 0 !important;
            border: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
        }

        .fi-tabs-item {
            border-radius: 999px !important;
            background: #f5f7fb !important;
            border: 1px solid transparent !important;
            min-height: 35px !important;
            padding: .35rem .8rem !important;
            color: #667085 !important;
            font-weight: 700 !important;
        }

        .fi-tabs-item[aria-selected="true"] {
            background: #fff7e8 !important;
            border-color: #f6b21a !important;
            color: var(--dth-email-orange-deep) !important;
        }

        .fi-fo-actions {
            justify-content: flex-start;
            gap: .65rem;
            padding-top: .25rem;
        }

        .fi-form {
            gap: 1rem !important;
        }

        .fi-section-content {
            row-gap: 1rem !important;
        }

        .fi-modal-window {
            overflow: hidden;
        }

        .trix-content,
        .ProseMirror {
            min-height: 230px;
        }

        @media (min-width: 1280px) {
            .fi-form > .grid {
                column-gap: 1rem !important;
            }
        }
    </style>
@endif
