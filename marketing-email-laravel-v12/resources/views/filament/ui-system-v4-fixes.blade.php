{{--
    DTH UI/UX Design System V4 — interaction and contrast hardening
    --------------------------------------------------------------------------
    This layer only corrects presentation and navigation ergonomics identified
    during browser UAT. It does not alter Livewire actions, form state, queries,
    validation, authorization, routes, or any business workflow.
--}}
<style>
    /* ---------------------------------------------------------------------
       1. Toggle state labels — one visible state, never duplicated
       ------------------------------------------------------------------ */
    .dth-state-toggle__native-label--sr-only {
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

    .dth-state-toggle {
        min-height: 2rem;
        gap: .5rem !important;
    }

    .dth-state-toggle > .dth-state-toggle__label[data-dth-generated="true"] ~
    .dth-state-toggle__label[data-dth-generated="true"] {
        display: none !important;
    }

    .dth-state-toggle__label {
        max-width: min(100%, 22rem);
        white-space: normal;
        cursor: pointer;
    }

    /* ---------------------------------------------------------------------
       2. Opaque account dropdowns, notification drawers and modal surfaces
       ------------------------------------------------------------------ */
    body.fi-body .fi-dropdown-panel,
    body.fi-body .fi-modal-window,
    body.fi-body .fi-global-search-results-ctn {
        opacity: 1 !important;
        isolation: isolate;
        border-color: rgb(var(--dth-v3-border-strong)) !important;
        background-color: rgb(var(--dth-v3-panel)) !important;
        background-image: linear-gradient(
            145deg,
            rgb(var(--dth-v3-panel-raised)),
            rgb(var(--dth-v3-panel))
        ) !important;
        box-shadow: 0 24px 60px rgba(var(--dth-v3-shadow), .22),
            0 0 0 1px rgba(var(--dth-v3-border), .35) !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }

    .dark body.fi-body .fi-dropdown-panel,
    .dark body.fi-body .fi-modal-window,
    .dark body.fi-body .fi-global-search-results-ctn {
        border-color: rgb(66 72 84) !important;
        background-color: rgb(18 20 25) !important;
        background-image: linear-gradient(145deg, rgb(27 30 37), rgb(18 20 25)) !important;
        box-shadow: 0 28px 68px rgba(0, 0, 0, .58),
            0 0 0 1px rgba(255, 255, 255, .035) !important;
    }

    body.fi-body .fi-modal-header,
    body.fi-body .fi-modal-content,
    body.fi-body .fi-modal-footer {
        opacity: 1 !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }

    body.fi-body .fi-modal-header {
        background-color: rgb(var(--dth-v3-panel-raised)) !important;
        background-image: linear-gradient(
            90deg,
            rgba(var(--dth-v3-accent), .08),
            rgb(var(--dth-v3-panel-raised)) 42%
        ) !important;
    }

    body.fi-body .fi-modal-content {
        background: rgb(var(--dth-v3-panel)) !important;
    }

    body.fi-body .fi-modal-footer {
        background: rgb(var(--dth-v3-panel-soft)) !important;
    }

    .dark body.fi-body .fi-modal-header {
        background-color: rgb(26 29 35) !important;
        background-image: linear-gradient(
            90deg,
            rgba(var(--dth-v3-accent), .1),
            rgb(26 29 35) 42%
        ) !important;
    }

    .dark body.fi-body .fi-modal-content {
        background: rgb(18 20 25) !important;
    }

    .dark body.fi-body .fi-modal-footer {
        background: rgb(24 27 33) !important;
    }

    body.fi-body .fi-dropdown-list,
    body.fi-body .fi-dropdown-header,
    body.fi-body .fi-dropdown-list-item {
        opacity: 1 !important;
    }

    body.fi-body .fi-modal-close-overlay {
        background: rgba(2, 6, 23, .76) !important;
        backdrop-filter: blur(7px) !important;
        -webkit-backdrop-filter: blur(7px) !important;
    }

    /* ---------------------------------------------------------------------
       3. Form fields — visible borders in every module and every modal
       ------------------------------------------------------------------ */
    body.fi-body .fi-input-wrp,
    body.fi-body .fi-select-input,
    body.fi-body .fi-fo-textarea textarea,
    body.fi-body .fi-fo-rich-editor,
    body.fi-body .fi-fo-markdown-editor,
    body.fi-body .fi-fo-tags-input,
    body.fi-body .fi-fo-key-value,
    body.fi-body .fi-fo-file-upload-input-ctn,
    body.fi-body .fi-fo-builder,
    body.fi-body .fi-fo-repeater,
    body.fi-body .fi-fo-file-upload .filepond--panel-root {
        border: 1px solid rgb(203 213 225) !important;
        background-color: rgb(255 255 255) !important;
        box-shadow: inset 0 0 0 1px rgba(15, 23, 42, .018),
            0 1px 2px rgba(15, 23, 42, .045) !important;
    }

    .dark body.fi-body .fi-input-wrp,
    .dark body.fi-body .fi-select-input,
    .dark body.fi-body .fi-fo-textarea textarea,
    .dark body.fi-body .fi-fo-rich-editor,
    .dark body.fi-body .fi-fo-markdown-editor,
    .dark body.fi-body .fi-fo-tags-input,
    .dark body.fi-body .fi-fo-key-value,
    .dark body.fi-body .fi-fo-file-upload-input-ctn,
    .dark body.fi-body .fi-fo-builder,
    .dark body.fi-body .fi-fo-repeater,
    .dark body.fi-body .fi-fo-file-upload .filepond--panel-root {
        border-color: rgb(78 84 98) !important;
        background-color: rgb(25 28 34) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .035),
            0 1px 2px rgba(0, 0, 0, .22) !important;
    }

    body.fi-body .fi-input-wrp .fi-input,
    body.fi-body .fi-input-wrp .fi-select-input {
        border: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    body.fi-body .fi-input-wrp:hover,
    body.fi-body .fi-select-input:hover,
    body.fi-body .fi-fo-textarea textarea:hover,
    body.fi-body .fi-fo-rich-editor:hover,
    body.fi-body .fi-fo-markdown-editor:hover,
    body.fi-body .fi-fo-tags-input:hover,
    body.fi-body .fi-fo-key-value:hover,
    body.fi-body .fi-fo-file-upload-input-ctn:hover {
        border-color: rgb(148 163 184) !important;
    }

    .dark body.fi-body .fi-input-wrp:hover,
    .dark body.fi-body .fi-select-input:hover,
    .dark body.fi-body .fi-fo-textarea textarea:hover,
    .dark body.fi-body .fi-fo-rich-editor:hover,
    .dark body.fi-body .fi-fo-markdown-editor:hover,
    .dark body.fi-body .fi-fo-tags-input:hover,
    .dark body.fi-body .fi-fo-key-value:hover,
    .dark body.fi-body .fi-fo-file-upload-input-ctn:hover {
        border-color: rgb(105 113 130) !important;
    }

    body.fi-body .fi-input-wrp:focus-within,
    body.fi-body .fi-select-input:focus,
    body.fi-body .fi-fo-textarea textarea:focus,
    body.fi-body .fi-fo-rich-editor:focus-within,
    body.fi-body .fi-fo-markdown-editor:focus-within,
    body.fi-body .fi-fo-tags-input:focus-within,
    body.fi-body .fi-fo-key-value:focus-within,
    body.fi-body .fi-fo-file-upload-input-ctn:focus-within {
        border-color: rgb(var(--dth-v3-accent)) !important;
        box-shadow: 0 0 0 3px rgba(var(--dth-v3-accent), .17),
            0 5px 16px rgba(var(--dth-v3-accent), .07) !important;
        outline: none !important;
    }

    body.fi-body .fi-input-wrp:has(input:disabled),
    body.fi-body .fi-input-wrp:has(input[readonly]),
    body.fi-body .fi-select-input:disabled,
    body.fi-body .fi-fo-textarea textarea:disabled,
    body.fi-body .fi-fo-textarea textarea[readonly] {
        border-style: dashed !important;
        border-color: rgb(148 163 184) !important;
        background-color: rgb(241 245 249) !important;
    }

    .dark body.fi-body .fi-input-wrp:has(input:disabled),
    .dark body.fi-body .fi-input-wrp:has(input[readonly]),
    .dark body.fi-body .fi-select-input:disabled,
    .dark body.fi-body .fi-fo-textarea textarea:disabled,
    .dark body.fi-body .fi-fo-textarea textarea[readonly] {
        border-color: rgb(91 100 117) !important;
        background-color: rgb(30 33 40) !important;
    }

    /* ---------------------------------------------------------------------
       4. Data tables — visible row separation and stronger zebra contrast
       ------------------------------------------------------------------ */
    body.fi-body .fi-ta-table > tbody > .fi-ta-row {
        background: rgb(255 255 255) !important;
    }

    body.fi-body .fi-ta-table > tbody > .fi-ta-row:nth-child(even) {
        background: rgb(247 249 252) !important;
    }

    .dark body.fi-body .fi-ta-table > tbody > .fi-ta-row {
        background: rgb(19 21 26) !important;
    }

    .dark body.fi-body .fi-ta-table > tbody > .fi-ta-row:nth-child(even) {
        background: rgb(25 28 34) !important;
    }

    body.fi-body .fi-ta-table > tbody > .fi-ta-row > td {
        border-bottom: 1px solid rgb(226 232 240) !important;
    }

    .dark body.fi-body .fi-ta-table > tbody > .fi-ta-row > td {
        border-bottom-color: rgb(52 57 68) !important;
    }

    body.fi-body .fi-ta-table > tbody > .fi-ta-row:last-child > td {
        border-bottom: 0 !important;
    }

    body.fi-body .fi-ta-table > tbody > .fi-ta-row:hover {
        background: rgb(255 251 235) !important;
        box-shadow: inset 3px 0 0 rgb(var(--dth-v3-accent)) !important;
    }

    .dark body.fi-body .fi-ta-table > tbody > .fi-ta-row:hover {
        background: rgb(32 35 43) !important;
    }

    body.fi-body .fi-ta-header-cell {
        background: rgb(241 245 249) !important;
        backdrop-filter: none !important;
    }

    .dark body.fi-body .fi-ta-header-cell {
        background: rgb(34 37 44) !important;
    }

    /* ---------------------------------------------------------------------
       5. Page action bars — no overlap with color pickers or form controls
       ------------------------------------------------------------------ */
    body.fi-body .dth-page-form-actions {
        position: static !important;
        z-index: auto !important;
        inset: auto !important;
        width: 100% !important;
        max-width: none !important;
        clear: both;
        margin-top: 1.35rem !important;
        padding: .75rem !important;
        border-color: rgb(var(--dth-v3-border-strong)) !important;
        background: rgb(var(--dth-v3-panel)) !important;
        box-shadow: 0 8px 24px rgba(var(--dth-v3-shadow), .075) !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }

    .dark body.fi-body .dth-page-form-actions {
        border-color: rgb(58 63 74) !important;
        background: rgb(20 22 27) !important;
        box-shadow: 0 10px 28px rgba(0, 0, 0, .26) !important;
    }

    body.fi-body .fi-fo-color-picker {
        position: relative;
        z-index: 30;
    }

    body.fi-body .fi-fo-color-picker-panel {
        z-index: 80 !important;
    }

    /* ---------------------------------------------------------------------
       6. Sidebar — nested surfaces instead of fragile connector rails
       ------------------------------------------------------------------ */
    body.fi-body .fi-sidebar-nav {
        scrollbar-gutter: stable;
        overscroll-behavior: contain;
        scroll-behavior: auto !important;
    }

    body.fi-body .fi-sidebar-group-items::before,
    body.fi-body .fi-sidebar-sub-group-items::before,
    body.fi-body .fi-sidebar-item-grouped-border,
    body.fi-body .dth-config-sidebar-scope .fi-sidebar-item-grouped-border {
        display: none !important;
        content: none !important;
    }

    body.fi-body .fi-sidebar-group-items,
    body.fi-body .dth-config-sidebar-scope > .fi-sidebar-group-items {
        padding-left: 0 !important;
    }

    body.fi-body .fi-sidebar-sub-group-items,
    body.fi-body .dth-config-sidebar-scope .fi-sidebar-sub-group-items {
        gap: .18rem !important;
        margin: .25rem .18rem .35rem 1rem !important;
        padding: .32rem !important;
        border: 1px solid rgba(var(--dth-v3-border), .88) !important;
        border-left: 1px solid rgba(var(--dth-v3-border), .88) !important;
        border-radius: .72rem !important;
        background: rgba(var(--dth-v3-panel-soft), .72) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .22) !important;
    }

    .dark body.fi-body .fi-sidebar-sub-group-items,
    .dark body.fi-body .dth-config-sidebar-scope .fi-sidebar-sub-group-items {
        border-color: rgb(47 52 62) !important;
        background: rgb(15 17 21) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .025) !important;
    }

    body.fi-body .fi-sidebar-item-button,
    body.fi-body .fi-sidebar-item-button:hover {
        transform: none !important;
    }

    body.fi-body .fi-sidebar-sub-group-items > .fi-sidebar-item > .fi-sidebar-item-button,
    body.fi-body .dth-config-sidebar-scope .fi-sidebar-sub-group-items > .fi-sidebar-item > .fi-sidebar-item-button {
        min-height: 2.35rem !important;
        padding: .42rem .55rem !important;
        border-radius: .58rem !important;
    }

    body.fi-body .fi-sidebar-sub-group-items > .fi-sidebar-item.fi-active > .fi-sidebar-item-button {
        background: rgba(var(--dth-v3-accent), .13) !important;
        box-shadow: inset 3px 0 0 rgb(var(--dth-v3-accent)) !important;
    }

    body.fi-body .fi-sidebar-group[data-dth-active="true"] {
        background: transparent !important;
    }

    body.fi-body .fi-sidebar-group[data-dth-active="true"] > .fi-sidebar-group-button,
    body.fi-body .fi-sidebar-group[data-dth-active="true"] > div > .fi-sidebar-group-button {
        background: rgba(var(--dth-v3-accent), .1) !important;
        box-shadow: inset 3px 0 0 rgb(var(--dth-v3-accent)) !important;
    }

    /* ---------------------------------------------------------------------
       7. Import HTML actions — bordered, recognizable secondary buttons
       ------------------------------------------------------------------ */
    body.fi-body .fi-btn.dth-import-html-action,
    body.fi-body .dth-import-html-action.fi-btn {
        border: 1px solid rgb(var(--dth-v3-border-strong)) !important;
        color: rgb(var(--dth-v3-text-soft)) !important;
        background: rgb(var(--dth-v3-panel-raised)) !important;
        box-shadow: 0 2px 8px rgba(var(--dth-v3-shadow), .08) !important;
    }

    .dark body.fi-body .fi-btn.dth-import-html-action,
    .dark body.fi-body .dth-import-html-action.fi-btn {
        border-color: rgb(79 86 100) !important;
        color: rgb(226 232 240) !important;
        background: rgb(28 31 38) !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, .24) !important;
    }

    body.fi-body .fi-btn.dth-import-html-action:hover,
    body.fi-body .dth-import-html-action.fi-btn:hover {
        border-color: rgb(var(--dth-v3-accent)) !important;
        color: rgb(var(--dth-v3-accent-strong)) !important;
        background: rgba(var(--dth-v3-accent), .09) !important;
        box-shadow: 0 7px 18px rgba(var(--dth-v3-accent), .13) !important;
    }

    @media (max-width: 767px) {
        body.fi-body .dth-page-form-actions {
            padding: .6rem !important;
        }

        body.fi-body .fi-sidebar-sub-group-items,
        body.fi-body .dth-config-sidebar-scope .fi-sidebar-sub-group-items {
            margin-left: .45rem !important;
        }
    }
</style>

<script>
(function () {
    const SIDEBAR_SCROLL_KEY = 'dth.ui.sidebar.scroll.v4';
    let boundSidebar = null;
    let saveFrame = null;
    let refreshFrame = null;

    function safeSessionGet(key) {
        try {
            return window.sessionStorage.getItem(key);
        } catch (error) {
            return null;
        }
    }

    function safeSessionSet(key, value) {
        try {
            window.sessionStorage.setItem(key, value);
        } catch (error) {
            // Storage can be unavailable in strict privacy modes. The sidebar
            // remains fully functional; only scroll persistence is skipped.
        }
    }

    function findSidebarScrollContainer() {
        const candidates = Array.from(document.querySelectorAll('.fi-sidebar-nav, .fi-sidebar'));

        return candidates.find(function (element) {
            const style = window.getComputedStyle(element);
            return /(auto|scroll)/.test(style.overflowY) && element.scrollHeight > element.clientHeight + 4;
        }) || document.querySelector('.fi-sidebar-nav');
    }

    function readSavedScroll() {
        const raw = safeSessionGet(SIDEBAR_SCROLL_KEY);
        if (!raw) return null;

        try {
            const value = JSON.parse(raw);
            if (!Number.isFinite(value.top) || !Number.isFinite(value.savedAt)) return null;
            if (Date.now() - value.savedAt > 60 * 60 * 1000) return null;
            return value;
        } catch (error) {
            return null;
        }
    }

    function saveSidebarScroll() {
        const container = findSidebarScrollContainer();
        if (!container) return;

        safeSessionSet(SIDEBAR_SCROLL_KEY, JSON.stringify({
            top: Math.max(0, Math.round(container.scrollTop)),
            savedAt: Date.now(),
        }));
    }

    function scheduleSidebarSave() {
        if (saveFrame !== null) return;

        saveFrame = window.requestAnimationFrame(function () {
            saveFrame = null;
            saveSidebarScroll();
        });
    }

    function restoreSidebarScroll(container) {
        const saved = readSavedScroll();

        if (!saved) {
            const activeItem = container.querySelector('.fi-sidebar-item.fi-active');
            if (activeItem) {
                const containerRect = container.getBoundingClientRect();
                const itemRect = activeItem.getBoundingClientRect();
                const isOutside = itemRect.top < containerRect.top || itemRect.bottom > containerRect.bottom;

                if (isOutside) {
                    container.scrollTop += itemRect.top - containerRect.top
                        - (container.clientHeight / 2)
                        + (itemRect.height / 2);
                }
            }
            return;
        }

        const apply = function () {
            const maximum = Math.max(0, container.scrollHeight - container.clientHeight);
            container.scrollTop = Math.min(saved.top, maximum);
        };

        apply();
        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(apply);
        });
        window.setTimeout(apply, 80);
    }

    function bindSidebarScroll() {
        const container = findSidebarScrollContainer();
        if (!container) return;

        if (boundSidebar === container) {
            return;
        }

        if (boundSidebar) {
            boundSidebar.removeEventListener('scroll', scheduleSidebarSave);
        }

        boundSidebar = container;
        boundSidebar.addEventListener('scroll', scheduleSidebarSave, { passive: true });
        restoreSidebarScroll(container);
    }

    function markImportHtmlActions(root) {
        const scope = root || document;
        const selectors = [
            '[wire\\:click*="import_html"]',
            '[x-on\\:click*="import_html"]',
            '[data-action="import_html"]',
        ];

        scope.querySelectorAll(selectors.join(',')).forEach(function (action) {
            const button = action.matches('.fi-btn') ? action : action.closest('.fi-btn');
            (button || action).classList.add('dth-import-html-action');
        });
    }

    function bootUiV4(root) {
        markImportHtmlActions(root || document);
        bindSidebarScroll();
    }

    function scheduleUiV4(root) {
        if (refreshFrame !== null) return;

        refreshFrame = window.requestAnimationFrame(function () {
            refreshFrame = null;
            bootUiV4(root || document);
        });
    }

    document.addEventListener('click', function (event) {
        if (event.target.closest('.fi-sidebar a')) {
            saveSidebarScroll();
        }
    }, true);

    document.addEventListener('livewire:navigating', saveSidebarScroll);
    document.addEventListener('livewire:navigated', function () { scheduleUiV4(document); });
    document.addEventListener('livewire:initialized', function () { bootUiV4(document); });
    document.addEventListener('DOMContentLoaded', function () { bootUiV4(document); });
    window.addEventListener('pagehide', saveSidebarScroll);

    if (document.readyState !== 'loading') {
        bootUiV4(document);
    }

    if (!window.__dthUiV4Observer) {
        window.__dthUiV4Observer = new MutationObserver(function (mutations) {
            let shouldRefresh = false;

            mutations.forEach(function (mutation) {
                if (mutation.addedNodes.length > 0) {
                    shouldRefresh = true;
                }
            });

            if (shouldRefresh) {
                scheduleUiV4(document);
            }
        });

        const startObserver = function () {
            if (!document.body) return;
            window.__dthUiV4Observer.observe(document.body, { childList: true, subtree: true });
        };

        if (document.body) {
            startObserver();
        } else {
            document.addEventListener('DOMContentLoaded', startObserver, { once: true });
        }
    }
})();
</script>
