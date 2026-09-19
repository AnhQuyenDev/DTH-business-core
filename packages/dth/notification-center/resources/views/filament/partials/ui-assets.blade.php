<style>
    :root {
        --dth-notify-primary: #2563eb;
        --dth-notify-primary-strong: #1d4ed8;
        --dth-notify-primary-soft: #eff6ff;
        --dth-notify-heading: #10243a;
        --dth-notify-text: #344054;
        --dth-notify-muted: #7a8799;
        --dth-notify-border: #e2e8f0;
        --dth-notify-bg: #f5f8fc;
        --dth-notify-surface: #fff;
    }

    [x-cloak] { display: none !important; }

    .dth-notify-page-title { display: inline-flex; align-items: center; gap: .85rem; }
    .dth-notify-page-title__icon {
        width: 3.3rem; height: 3.3rem; flex: 0 0 3.3rem; display: grid; place-items: center;
        border: 1px solid #d6e4ff; border-radius: 1rem; color: var(--dth-notify-primary);
        background: linear-gradient(145deg,#eff6ff 0%,#fff 100%); box-shadow: 0 10px 26px rgba(37,99,235,.09);
    }
    .dth-notify-page-title__icon svg { width: 1.55rem; height: 1.55rem; }


    .dth-notify-bell { position: relative; display: inline-flex; align-items: center; }
    .dth-notify-bell__trigger {
        position: relative; width: 2.5rem; height: 2.5rem; display: grid; place-items: center;
        border: 1px solid transparent; border-radius: 999px; color: #475467; background: transparent;
        transition: all .15s ease; cursor: pointer;
    }
    .dth-notify-bell__trigger:hover { background: #f4f7fb; color: var(--dth-notify-primary); border-color: #e5ebf3; }
    .dth-notify-bell__trigger svg { width: 1.35rem; height: 1.35rem; }
    .dth-notify-bell__badge {
        position: absolute; top: -.18rem; right: -.26rem; min-width: 1.12rem; height: 1.12rem; padding: 0 .27rem;
        display: inline-flex; align-items: center; justify-content: center; border: 2px solid #fff; border-radius: 999px;
        background: #ef4444; color: #fff; font-size: .59rem; line-height: 1; font-weight: 800;
    }
    .dth-notify-bell__panel {
        position: absolute; z-index: 70; top: calc(100% + .65rem); right: 0; width: min(420px, calc(100vw - 24px));
        overflow: hidden; border: 1px solid var(--dth-notify-border); border-radius: 16px; background: #fff;
        box-shadow: 0 22px 60px rgba(15,23,42,.16);
    }
    .dth-notify-bell__header { display: flex; justify-content: space-between; gap: .8rem; padding: .9rem 1rem; border-bottom: 1px solid #edf1f6; background: #fbfcff; }
    .dth-notify-bell__header > div { display: grid; gap: .12rem; }
    .dth-notify-bell__header strong { color: var(--dth-notify-heading); font-size: .92rem; }
    .dth-notify-bell__header span { color: var(--dth-notify-muted); font-size: .72rem; }
    .dth-notify-link-button, .dth-notify-clear-button { border: 0; background: transparent; color: var(--dth-notify-primary); font-size: .72rem; font-weight: 700; cursor: pointer; }
    .dth-notify-clear-button { color: #b42318; }
    .dth-notify-bell__list { max-height: 31rem; overflow: auto; }
    .dth-notify-bell__item { position: relative; display: block; color: inherit; border-bottom: 1px solid #f0f3f7; background: #fff; }
    .dth-notify-bell__item:hover { background: #f8fbff; }
    .dth-notify-bell__item.is-unread { background: linear-gradient(90deg,#f5f9ff 0%,#fff 74%); }
    .dth-notify-bell__item-link { display: grid; grid-template-columns: 38px minmax(0,1fr) 8px; gap: .7rem; padding: .82rem 2.5rem .82rem 1rem; color: inherit; text-decoration: none; }
    .dth-notify-bell__delete { position: absolute; right: .52rem; bottom: .58rem; width: 1.65rem; height: 1.65rem; display: grid; place-items: center; border: 0; border-radius: 8px; background: transparent; color: #98a2b3; opacity: 0; cursor: pointer; transition: all .14s ease; }
    .dth-notify-bell__delete svg { width: .9rem; height: .9rem; }
    .dth-notify-bell__item:hover .dth-notify-bell__delete { opacity: 1; }
    .dth-notify-bell__delete:hover { background: #fef2f2; color: #b42318; }
    .dth-notify-bell__copy { min-width: 0; display: grid; gap: .18rem; }
    .dth-notify-bell__title { color: var(--dth-notify-heading); font-size: .79rem; font-weight: 760; line-height: 1.3; }
    .dth-notify-bell__body { color: #667085; font-size: .71rem; line-height: 1.42; }
    .dth-notify-bell__meta { color: #98a2b3; font-size: .64rem; }
    .dth-notify-bell__channels { display: flex; flex-wrap: wrap; gap: .28rem; margin-top: .06rem; }
    .dth-notify-bell__channels i { padding: .15rem .38rem; border-radius: 999px; background: #f1f5f9; color: #64748b; font-size: .56rem; line-height: 1.2; font-style: normal; font-weight: 700; }
    .dth-notify-bell__footer { display: flex; align-items: center; justify-content: space-between; gap: .8rem; padding: .75rem 1rem; background: #fbfcff; border-top: 1px solid #edf1f6; }
    .dth-notify-bell__footer a { display: inline-flex; align-items: center; gap: .35rem; color: var(--dth-notify-primary); text-decoration: none; font-size: .75rem; font-weight: 760; }
    .dth-notify-bell__footer svg { width: .9rem; height: .9rem; }
    .dth-notify-bell__empty { display: grid; place-items: center; gap: .35rem; padding: 2rem 1rem; text-align: center; color: #98a2b3; }
    .dth-notify-bell__empty svg { width: 1.7rem; height: 1.7rem; }
    .dth-notify-bell__empty strong { color: #667085; font-size: .8rem; }
    .dth-notify-bell__empty span { font-size: .7rem; }

    .dth-notify-page { display: grid; gap: 14px; }
    .dth-notify-filter-card, .dth-notify-list-card, .dth-notify-detail-card, .dth-notify-settings-card {
        border: 1px solid var(--dth-notify-border); border-radius: 16px; background: #fff; box-shadow: 0 10px 30px rgba(15,23,42,.04);
    }
    .dth-notify-filter-card { overflow: hidden; }
    .dth-notify-tabs { display: flex; flex-wrap: wrap; gap: .2rem; padding: .65rem .75rem .45rem; border-bottom: 1px solid #edf1f6; }
    .dth-notify-tab { display: inline-flex; align-items: center; gap: .45rem; min-height: 38px; padding: 0 .8rem; border: 0; border-bottom: 2px solid transparent; background: transparent; color: #667085; font-size: .78rem; font-weight: 720; cursor: pointer; }
    .dth-notify-tab b { min-width: 1.35rem; height: 1.35rem; display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; background: #f2f4f7; color: #667085; font-size: .65rem; }
    .dth-notify-tab.is-active { color: var(--dth-notify-primary); border-bottom-color: var(--dth-notify-primary); }
    .dth-notify-tab.is-active b { background: #dbeafe; color: #1d4ed8; }
    .dth-notify-filters { display: grid; grid-template-columns: minmax(260px,1.6fr) repeat(3,minmax(140px,.7fr)) auto; gap: .65rem; padding: .75rem; align-items: center; }
    .dth-notify-search { min-height: 42px; display: flex; align-items: center; gap: .55rem; padding: 0 .8rem; border: 1px solid #d8e1ec; border-radius: 11px; background: #fff; }
    .dth-notify-search svg { width: 1rem; height: 1rem; color: #98a2b3; }
    .dth-notify-search input { width: 100%; border: 0; outline: 0; background: transparent; color: #344054; font-size: .76rem; }
    .dth-notify-select { min-height: 42px; padding: 0 .7rem; border: 1px solid #d8e1ec; border-radius: 11px; background: #fff; color: #475467; font-size: .75rem; }
    .dth-notify-toolbar-button { min-height: 42px; display: inline-flex; align-items: center; justify-content: center; gap: .4rem; padding: 0 .75rem; border: 1px solid #d8e1ec; border-radius: 11px; background: #fff; color: #475467; font-size: .72rem; font-weight: 720; cursor: pointer; }
    .dth-notify-toolbar-button svg { width: 1rem; height: 1rem; }

    .dth-notify-master-detail { display: grid; grid-template-columns: minmax(0,1.55fr) minmax(340px,.9fr); gap: 14px; align-items: start; }
    .dth-notify-list-card, .dth-notify-detail-card { min-height: 590px; overflow: hidden; }
    .dth-notify-list-head { display: flex; align-items: center; justify-content: space-between; gap: .8rem; padding: .85rem 1rem; border-bottom: 1px solid #edf1f6; background: #fbfcff; }
    .dth-notify-list-head > div { display: grid; gap: .12rem; }
    .dth-notify-list-head span { color: var(--dth-notify-primary); font-size: .58rem; font-weight: 800; letter-spacing: .11em; }
    .dth-notify-list-head strong { color: #667085; font-size: .72rem; font-weight: 650; }
    .dth-notify-list { max-height: 720px; overflow: auto; }
    .dth-notify-row { position: relative; width: 100%; display: grid; grid-template-columns: 44px minmax(0,1fr) 10px; gap: .8rem; padding: .9rem 1rem; text-align: left; border: 0; border-bottom: 1px solid #f0f3f7; background: #fff; cursor: pointer; }
    .dth-notify-row:hover { background: #fafcff; }
    .dth-notify-row.is-unread { background: linear-gradient(90deg,#f4f8ff 0%,#fff 68%); }
    .dth-notify-row.is-selected { box-shadow: inset 3px 0 0 var(--dth-notify-primary); background: #f7faff; }
    .dth-notify-row__main { min-width: 0; display: grid; gap: .28rem; }
    .dth-notify-row__top { display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem; }
    .dth-notify-row__top strong { color: var(--dth-notify-heading); font-size: .82rem; line-height: 1.35; }
    .dth-notify-row__top time { flex: 0 0 auto; color: #98a2b3; font-size: .64rem; }
    .dth-notify-row__body { color: #667085; font-size: .72rem; line-height: 1.45; }
    .dth-notify-row__meta { display: flex; align-items: center; flex-wrap: wrap; gap: .35rem; color: #98a2b3; font-size: .63rem; }
    .dth-notify-row__meta i { font-style: normal; }

    .dth-notify-icon { width: 38px; height: 38px; display: inline-grid; place-items: center; border-radius: 12px; background: #f1f5f9; color: #475569; }
    .dth-notify-icon svg { width: 18px; height: 18px; }
    .dth-notify-icon--large { width: 48px; height: 48px; border-radius: 14px; }
    .dth-notify-icon--large svg { width: 22px; height: 22px; }
    .dth-notify-icon--blue { color: #2563eb; background: #eff6ff; }
    .dth-notify-icon--green { color: #15803d; background: #f0fdf4; }
    .dth-notify-icon--amber { color: #b45309; background: #fff7ed; }
    .dth-notify-icon--violet { color: #7c3aed; background: #f5f3ff; }
    .dth-notify-icon--cyan { color: #0e7490; background: #ecfeff; }
    .dth-notify-icon--indigo { color: #4338ca; background: #eef2ff; }
    .dth-notify-icon--slate { color: #475569; background: #f1f5f9; }
    .dth-notify-pill { display: inline-flex; align-items: center; padding: .22rem .5rem; border-radius: 999px; font-size: .59rem; font-weight: 750; background: #f1f5f9; color: #475569; }
    .dth-notify-pill--blue { color: #1d4ed8; background: #eff6ff; }
    .dth-notify-pill--green { color: #15803d; background: #f0fdf4; }
    .dth-notify-pill--amber { color: #b45309; background: #fff7ed; }
    .dth-notify-pill--violet { color: #7c3aed; background: #f5f3ff; }
    .dth-notify-pill--cyan { color: #0e7490; background: #ecfeff; }
    .dth-notify-pill--indigo { color: #4338ca; background: #eef2ff; }
    .dth-notify-pill--slate { color: #475569; background: #f1f5f9; }
    .dth-notify-unread-dot { align-self: center; width: 7px; height: 7px; border-radius: 999px; background: #2563eb; box-shadow: 0 0 0 3px #dbeafe; }

    .dth-notify-detail-card { position: sticky; top: 5rem; padding: 1.15rem; }
    .dth-notify-detail-head { display: flex; align-items: flex-start; gap: .85rem; padding-bottom: 1rem; border-bottom: 1px solid #edf1f6; }
    .dth-notify-detail-head > div { min-width: 0; }
    .dth-notify-detail-head h2 { margin: .48rem 0 0; color: var(--dth-notify-heading); font-size: 1.05rem; line-height: 1.35; font-weight: 800; letter-spacing: -.02em; }
    .dth-notify-detail-meta { display: grid; grid-template-columns: 1fr 1fr; gap: .7rem .9rem; padding: 1rem 0; margin: 0; border-bottom: 1px solid #edf1f6; }
    .dth-notify-detail-meta > div { display: grid; gap: .15rem; }
    .dth-notify-detail-meta > div.is-wide { grid-column: 1 / -1; }
    .dth-notify-detail-meta dt { color: #98a2b3; font-size: .62rem; font-weight: 680; }
    .dth-notify-detail-meta dd { margin: 0; color: #344054; font-size: .72rem; font-weight: 650; }
    .dth-notify-channel-list { display: flex; flex-wrap: wrap; gap: .4rem; }
    .dth-notify-channel-list span { display: inline-flex; padding: .2rem .45rem; border-radius: 999px; background: #eef4ff; color: #2563eb; font-size: .6rem; }
    .dth-notify-channel-list span.is-status { background: #f8fafc; color: #667085; }
    .dth-notify-detail-body { padding: 1rem 0 1.15rem; color: #475467; font-size: .78rem; line-height: 1.65; }
    .dth-notify-detail-body p { margin: 0 0 .85rem; color: #344054; font-weight: 650; }
    .dth-notify-detail-body div { white-space: normal; }
    .dth-notify-attachments { display: grid; gap: .55rem; padding: .85rem 0 1rem; border-top: 1px solid #edf1f6; }
    .dth-notify-attachments__label { color: #667085; font-size: .68rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; }
    .dth-notify-attachments__list { display: grid; gap: .45rem; }
    .dth-notify-attachment { display: grid; grid-template-columns: 18px minmax(0,1fr) 18px; gap: .55rem; align-items: center; padding: .62rem .72rem; border: 1px solid #e1e8f0; border-radius: 10px; background: #fbfcfe; color: #475467; text-decoration: none; font-size: .72rem; font-weight: 700; }
    .dth-notify-attachment:hover { border-color: #bfd0ee; background: #f6f9ff; color: #1d4ed8; }
    .dth-notify-attachment svg { width: 16px; height: 16px; }
    .dth-notify-detail-actions { display: flex; flex-wrap: wrap; gap: .55rem; padding-top: .9rem; border-top: 1px solid #edf1f6; }

    .dth-notify-primary-link, .dth-notify-secondary-button, .dth-notify-danger-button {
        min-height: 40px; display: inline-flex; align-items: center; justify-content: center; gap: .42rem; padding: 0 .85rem;
        border-radius: 11px; font-size: .72rem; font-weight: 750; text-decoration: none; cursor: pointer;
    }
    .dth-notify-primary-link { border: 1px solid #2563eb; background: linear-gradient(180deg,#3775ef,#2563eb); color: #fff; box-shadow: 0 8px 18px rgba(37,99,235,.16); }
    .dth-notify-secondary-button { border: 1px solid #d8e1ec; background: #fff; color: #475467; }
    .dth-notify-danger-button { border: 1px solid #fecaca; background: #fff5f5; color: #b42318; }
    .dth-notify-primary-link svg, .dth-notify-secondary-button svg, .dth-notify-danger-button svg { width: 1rem; height: 1rem; }
    .dth-notify-empty-state { min-height: 320px; display: grid; place-items: center; align-content: center; gap: .4rem; padding: 2rem; text-align: center; color: #98a2b3; }
    .dth-notify-empty-state svg { width: 2rem; height: 2rem; }
    .dth-notify-empty-state strong { color: #667085; font-size: .82rem; }
    .dth-notify-empty-state span { font-size: .72rem; }
    .dth-notify-empty-state--detail { min-height: 540px; }

    html body .fi-btn.dth-notify-entry-action { min-height: 40px !important; padding: 0 14px !important; border: 1px solid #dbe3ec !important; border-radius: 12px !important; background: #fff !important; color: #475467 !important; box-shadow: 0 3px 10px rgba(15,23,42,.04) !important; font-weight: 720 !important; }
    html body .fi-btn.dth-notify-entry-action--primary { border-color: #2563eb !important; background: linear-gradient(180deg,#3775ef,#2563eb) !important; color: #fff !important; box-shadow: 0 8px 18px rgba(37,99,235,.16) !important; }
    html body .fi-btn.dth-notify-entry-action--primary * { color: #fff !important; }
    html body .fi-btn.dth-notify-modal-action--primary { min-height: 42px !important; padding: 0 16px !important; border: 1px solid #2563eb !important; border-radius: 12px !important; background: linear-gradient(180deg,#3775ef,#2563eb) !important; color: #fff !important; box-shadow: 0 8px 18px rgba(37,99,235,.16) !important; font-weight: 720 !important; }
    html body .fi-btn.dth-notify-modal-action--primary * { color: #fff !important; }
    html body .fi-btn.dth-notify-modal-action--secondary { min-height: 42px !important; padding: 0 16px !important; border: 1px solid #dbe3ec !important; border-radius: 12px !important; background: #fff !important; color: #475467 !important; box-shadow: none !important; font-weight: 720 !important; }

    .dth-notify-settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .dth-notify-settings-card { padding: 1rem; }
    .dth-notify-settings-card--wide, .dth-notify-settings-actions { grid-column: 1 / -1; }
    .dth-notify-settings-card__head { display: flex; align-items: flex-start; gap: .8rem; padding-bottom: .85rem; border-bottom: 1px solid #edf1f6; }
    .dth-notify-settings-card__head h2 { margin: 0; color: var(--dth-notify-heading); font-size: .92rem; }
    .dth-notify-settings-card__head p { margin: .18rem 0 0; color: var(--dth-notify-muted); font-size: .7rem; line-height: 1.45; }
    .dth-notify-setting-row { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .9rem 0; border-bottom: 1px solid #f1f4f7; cursor: pointer; }
    .dth-notify-setting-row:last-child { border-bottom: 0; }
    .dth-notify-setting-row > span { display: grid; gap: .15rem; }
    .dth-notify-setting-row strong { color: #344054; font-size: .78rem; }
    .dth-notify-setting-row small { color: #98a2b3; font-size: .68rem; line-height: 1.4; }
    .dth-notify-switch-input { width: 2.45rem; height: 1.35rem; accent-color: #2563eb; }
    .dth-notify-module-options { display: grid; grid-template-columns: repeat(4,minmax(0,1fr)); gap: .55rem; padding-top: .9rem; }
    .dth-notify-module-options label { display: flex; align-items: center; gap: .45rem; padding: .65rem .7rem; border: 1px solid #e4eaf1; border-radius: 10px; background: #fbfcfe; color: #475467; font-size: .72rem; cursor: pointer; }
    .dth-notify-module-options input { accent-color: #2563eb; }
    .dth-notify-settings-actions { display: flex; justify-content: flex-end; }

    @media (max-width: 1200px) {
        .dth-notify-filters { grid-template-columns: 1fr 1fr 1fr; }
        .dth-notify-search { grid-column: 1 / -1; }
        .dth-notify-toolbar-button { grid-column: auto; }
        .dth-notify-master-detail { grid-template-columns: 1fr; }
        .dth-notify-detail-card { position: static; min-height: auto; }
        .dth-notify-empty-state--detail { min-height: 280px; }
    }
    @media (max-width: 760px) {
        .dth-notify-bell__panel { position: fixed; top: 4.2rem; right: .75rem; left: .75rem; width: auto; }
        .dth-notify-filters { grid-template-columns: 1fr; }
        .dth-notify-search { grid-column: auto; }
        .dth-notify-tabs { overflow-x: auto; flex-wrap: nowrap; }
        .dth-notify-tab { flex: 0 0 auto; }
        .dth-notify-detail-meta { grid-template-columns: 1fr; }
        .dth-notify-detail-meta > div.is-wide { grid-column: auto; }
        .dth-notify-settings-grid { grid-template-columns: 1fr; }
        .dth-notify-settings-card--wide, .dth-notify-settings-actions { grid-column: auto; }
        .dth-notify-module-options { grid-template-columns: 1fr 1fr; }
    }
</style>

@php
    $dthNotifyPath = trim(request()->path(), '/');
    $dthNotifyPage = str_contains($dthNotifyPath, 'notifications')
        || str_contains($dthNotifyPath, 'notification-settings')
        || str_contains($dthNotifyPath, 'notification-templates')
        || str_contains($dthNotifyPath, 'notification-delivery-log');
@endphp
@if($dthNotifyPage)
<style>
    .fi-main {
        background:
            radial-gradient(circle at 88% 0%, rgba(37,99,235,.055), transparent 28rem),
            linear-gradient(180deg,#fafcff 0%,#f5f8fc 100%) !important;
    }
    .fi-main .fi-page { gap: 16px !important; }
    html body .fi-main .fi-btn.fi-btn-color-primary {
        background: linear-gradient(180deg,#3775ef 0%,#2563eb 100%) !important;
        border-color: #2563eb !important;
        color: #fff !important;
        box-shadow: 0 8px 18px rgba(37,99,235,.16) !important;
    }
    html body .fi-main .fi-btn.fi-btn-color-primary * { color: #fff !important; }
    .fi-section, .fi-ta, .fi-modal-window {
        border: 1px solid #e2e8f0 !important;
        border-radius: 16px !important;
        background: #fff !important;
        box-shadow: 0 10px 30px rgba(15,23,42,.04) !important;
    }
    .fi-section-header { border-bottom: 1px solid #edf1f6 !important; }
    .fi-section-header-heading { color: var(--dth-notify-heading) !important; font-weight: 800 !important; }
    .fi-section-header-description { color: var(--dth-notify-muted) !important; }
    .fi-modal-header {
        border-bottom: 1px solid #edf1f6 !important;
        background: linear-gradient(180deg,#fbfcff 0%,#f7faff 100%) !important;
    }
    .fi-modal-heading { color: var(--dth-notify-heading) !important; font-weight: 800 !important; }
    .fi-modal-description { color: var(--dth-notify-muted) !important; }
    .fi-modal-footer { border-top: 1px solid #edf1f6 !important; background: #fbfcff !important; }

    html body .fi-main .fi-fo-field-wrp .fi-input-wrp,
    html body .fi-main .fi-fo-field-wrp .choices__inner,
    html body .fi-modal-window .fi-fo-field-wrp .fi-input-wrp,
    html body .fi-modal-window .fi-fo-field-wrp .choices__inner,
    html body .fi-modal-window .fi-fo-field-wrp textarea,
    html body .fi-main .fi-fo-field-wrp textarea {
        border-width: 1px !important;
        border-style: solid !important;
        border-color: #cfd9e6 !important;
        border-radius: 11px !important;
        background: #fff !important;
        box-shadow: none !important;
    }
    html body .fi-main .fi-input-wrp .fi-input,
    html body .fi-main .fi-input-wrp .fi-select-input,
    html body .fi-modal-window .fi-input-wrp .fi-input,
    html body .fi-modal-window .fi-input-wrp .fi-select-input {
        border: 0 !important;
        background: transparent !important;
        box-shadow: none !important;
    }
    html body .fi-main .fi-fo-field-wrp .fi-input-wrp:focus-within,
    html body .fi-modal-window .fi-fo-field-wrp .fi-input-wrp:focus-within,
    html body .fi-main .fi-fo-field-wrp textarea:focus,
    html body .fi-modal-window .fi-fo-field-wrp textarea:focus {
        border-color: #78a5f7 !important;
        box-shadow: 0 0 0 4px rgba(37,99,235,.10) !important;
        outline: 0 !important;
    }
    html body .fi-main .fi-fo-field-wrp-label label,
    html body .fi-modal-window .fi-fo-field-wrp-label label { color: #344054 !important; font-weight: 700 !important; }
    html body .fi-main .fi-fo-field-wrp-helper-text,
    html body .fi-modal-window .fi-fo-field-wrp-helper-text { color: var(--dth-notify-muted) !important; line-height: 1.45 !important; }
    html body .fi-modal-window .fi-fo-field-wrp { row-gap: .35rem !important; }
    html body .fi-modal-window .fi-grid { row-gap: .8rem !important; }
    html body .fi-modal-window .fi-fo-file-upload { margin-top: .1rem; }

    html body .fi-btn.dth-notify-form-action--primary {
        min-height: 42px !important; padding: 0 16px !important; border: 1px solid #2563eb !important;
        border-radius: 12px !important; background: linear-gradient(180deg,#3775ef,#2563eb) !important;
        color: #fff !important; box-shadow: 0 8px 18px rgba(37,99,235,.16) !important; font-weight: 720 !important;
    }
    html body .fi-btn.dth-notify-form-action--primary * { color: #fff !important; }
    html body .fi-btn.dth-notify-form-action--secondary {
        min-height: 42px !important; padding: 0 16px !important; border: 1px solid #dbe3ec !important;
        border-radius: 12px !important; background: #fff !important; color: #475467 !important; box-shadow: none !important; font-weight: 720 !important;
    }
</style>
@endif

<style>
    .dth-notify-delivery-modal { display: grid; gap: .85rem; }
    .dth-notify-delivery-summary { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: .65rem; }
    .dth-notify-delivery-summary span { display: grid; gap: .15rem; padding: .75rem .85rem; border: 1px solid #e4eaf1; border-radius: 11px; background: #f9fbfe; color: #667085; font-size: .68rem; }
    .dth-notify-delivery-summary strong { color: #10243a; font-size: 1rem; }
    .dth-notify-delivery-table-wrap { overflow: auto; border: 1px solid #e4eaf1; border-radius: 12px; }
    .dth-notify-delivery-table { width: 100%; border-collapse: collapse; font-size: .72rem; }
    .dth-notify-delivery-table th { padding: .7rem .8rem; text-align: left; background: #f8fafc; color: #667085; font-size: .64rem; }
    .dth-notify-delivery-table td { padding: .75rem .8rem; border-top: 1px solid #edf1f6; color: #475467; }
    .dth-notify-delivery-table td strong, .dth-notify-delivery-table td small { display: block; }
    .dth-notify-delivery-table td small { margin-top: .12rem; color: #98a2b3; }
    .dth-notify-email-status { display: inline-flex; padding: .2rem .45rem; border-radius: 999px; background: #f1f5f9; color: #475569; font-size: .6rem; font-weight: 740; }
    .dth-notify-email-status--sent { background: #f0fdf4; color: #15803d; }
    .dth-notify-email-status--failed { background: #fef2f2; color: #b42318; }
    .dth-notify-email-status--queued { background: #eff6ff; color: #1d4ed8; }
    @media (max-width: 760px) { .dth-notify-delivery-summary { grid-template-columns: 1fr; } }
</style>

<style>
    /* Notification mailbox: make the direction of a message explicit. */
    .dth-notify-mailboxes {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .65rem;
        padding: .8rem .85rem .2rem;
        background: #fff;
    }
    .dth-notify-mailbox {
        position: relative;
        display: grid;
        grid-template-columns: 38px minmax(0,1fr) auto;
        align-items: center;
        gap: .7rem;
        min-height: 62px;
        padding: .65rem .8rem;
        border: 1px solid #e1e7ef;
        border-radius: 13px;
        background: #fbfcfe;
        color: #475467;
        text-align: left;
        cursor: pointer;
        transition: all .15s ease;
    }
    .dth-notify-mailbox:hover { border-color: #cbd7e6; background: #f8fbff; transform: translateY(-1px); }
    .dth-notify-mailbox.is-active {
        border-color: #bad0ff;
        background: linear-gradient(180deg,#f5f9ff 0%,#edf5ff 100%);
        box-shadow: 0 8px 18px rgba(37,99,235,.07);
    }
    .dth-notify-mailbox__icon {
        width: 38px; height: 38px; display: grid; place-items: center;
        border-radius: 11px; background: #fff; border: 1px solid #e3e9f1; color: #64748b;
    }
    .dth-notify-mailbox.is-active .dth-notify-mailbox__icon { color: #2563eb; border-color: #cfe0ff; background: #fff; }
    .dth-notify-mailbox__icon svg { width: 18px; height: 18px; }
    .dth-notify-mailbox__copy { min-width: 0; display: grid; gap: .08rem; }
    .dth-notify-mailbox__copy strong { color: #1f344a; font-size: .8rem; font-weight: 780; }
    .dth-notify-mailbox__copy small { color: #8a96a8; font-size: .65rem; }
    .dth-notify-mailbox > b {
        min-width: 1.55rem; height: 1.55rem; padding: 0 .35rem; display: inline-flex; align-items: center; justify-content: center;
        border-radius: 999px; background: #eef2f7; color: #667085; font-size: .66rem; font-weight: 800;
    }
    .dth-notify-mailbox.is-active > b { background: #dbeafe; color: #1d4ed8; }
    .dth-notify-mailbox > i {
        position: absolute; top: -.3rem; right: -.25rem; min-width: 1.2rem; height: 1.2rem; padding: 0 .28rem;
        display: inline-flex; align-items: center; justify-content: center; border: 2px solid #fff; border-radius: 999px;
        background: #ef4444; color: #fff; font-size: .56rem; font-style: normal; font-weight: 800;
    }

    .dth-notify-route {
        display: flex; align-items: center; flex-wrap: wrap; gap: .42rem; color: #667085;
    }
    .dth-notify-route > span { display: inline-flex; align-items: center; gap: .28rem; min-width: 0; }
    .dth-notify-route em { color: #98a2b3; font-size: .6rem; font-style: normal; font-weight: 680; text-transform: uppercase; letter-spacing: .045em; }
    .dth-notify-route strong { max-width: 230px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #475467; font-size: .68rem; font-weight: 760; }
    .dth-notify-route svg { width: .9rem; height: .9rem; color: #c0c8d4; }
    .dth-notify-row.is-sent { background: #fff; }
    .dth-notify-row.is-sent:hover { background: #fbfcff; }
    .dth-notify-sent-marker { align-self: center; color: #98a2b3; }
    .dth-notify-sent-marker svg { width: 1rem; height: 1rem; }
    .dth-notify-mini-channel {
        display: inline-flex; align-items: center; padding: .14rem .38rem; border-radius: 999px;
        background: #f1f5f9; color: #64748b !important; font-size: .58rem; font-weight: 720;
    }
    .dth-notify-sent-note {
        display: inline-flex !important; align-items: center; gap: .35rem; color: #8a96a8 !important;
        font-size: .65rem !important; font-weight: 650 !important; letter-spacing: 0 !important;
    }
    .dth-notify-sent-note svg { width: .9rem; height: .9rem; }

    .dth-notify-message-route {
        display: grid; grid-template-columns: minmax(0,1fr); gap: .72rem; align-items: stretch;
        margin: .85rem 0 0; padding: .9rem; border: 1px solid #e5eaf1; border-radius: 14px;
        background: linear-gradient(180deg,#fbfcff 0%,#f8fafc 100%);
    }
    .dth-notify-message-party {
        min-width: 0; display: grid; grid-template-columns: 40px minmax(0,1fr);
        gap: .7rem; align-items: start; padding: .7rem .75rem;
        border: 1px solid #e8edf3; border-radius: 12px; background: #ffffff;
    }
    .dth-notify-message-party__avatar {
        width: 40px; height: 40px; display: grid; place-items: center; border-radius: 12px; background: #eff6ff; color: #2563eb;
        box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .08);
    }
    .dth-notify-message-party__avatar.is-target { background: #f0fdf4; color: #15803d; box-shadow: inset 0 0 0 1px rgba(21, 128, 61, .08); }
    .dth-notify-message-party__avatar svg { width: 18px; height: 18px; }
    .dth-notify-message-party > span:last-child { min-width: 0; display: grid; gap: .15rem; }
    .dth-notify-message-party small { color: #98a2b3; font-size: .58rem; font-weight: 720; text-transform: uppercase; letter-spacing: .06em; }
    .dth-notify-message-party strong {
        color: #25364a; font-size: .75rem; font-weight: 780; line-height: 1.35;
        white-space: normal; overflow-wrap: anywhere; word-break: break-word;
    }
    .dth-notify-message-party em {
        color: #8a96a8; font-size: .64rem; font-style: normal; line-height: 1.45;
        white-space: normal; overflow-wrap: anywhere; word-break: break-word;
    }
    .dth-notify-message-route__arrow {
        display: grid; place-items: center; width: 34px; height: 34px; margin: -0.15rem auto;
        border-radius: 999px; background: #f8fafc; color: #b5c0ce; border: 1px solid #e8edf3;
    }
    .dth-notify-message-route__arrow svg { width: 1.05rem; height: 1.05rem; transform: rotate(90deg); }

    .dth-notify-delivery-strip {
        display: flex; flex-wrap: wrap; gap: .5rem; padding: .75rem 0; border-bottom: 1px solid #edf1f6;
    }
    .dth-notify-delivery-strip > span {
        min-width: 90px; display: grid; gap: .08rem; padding: .5rem .6rem; border: 1px solid #e3e9f1; border-radius: 10px; background: #fafcff;
    }
    .dth-notify-delivery-strip small { color: #98a2b3; font-size: .57rem; }
    .dth-notify-delivery-strip strong { color: #344054; font-size: .78rem; }
    .dth-notify-delivery-strip .is-pending { background: #fffaf0; border-color: #f5dfb8; }
    .dth-notify-delivery-strip .is-failed { background: #fff5f5; border-color: #f2caca; }
    .dth-notify-delivery-strip .is-failed strong { color: #b42318; }

    .dth-notify-recipient-preview { padding: .8rem 0; border-bottom: 1px solid #edf1f6; }
    .dth-notify-recipient-preview__label { display: block; margin-bottom: .5rem; color: #667085; font-size: .64rem; font-weight: 760; text-transform: uppercase; letter-spacing: .05em; }
    .dth-notify-recipient-chips { display: flex; flex-wrap: wrap; gap: .45rem; }
    .dth-notify-recipient-chip {
        max-width: 230px; display: inline-grid; grid-template-columns: 24px minmax(0,1fr); gap: .4rem; align-items: center;
        padding: .38rem .48rem; border: 1px solid #e4e9f0; border-radius: 10px; background: #fbfcfe;
    }
    .dth-notify-recipient-chip > svg { width: 15px; height: 15px; color: #7b8799; }
    .dth-notify-recipient-chip > span { min-width: 0; display: grid; }
    .dth-notify-recipient-chip strong { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #475467; font-size: .64rem; }
    .dth-notify-recipient-chip small { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #98a2b3; font-size: .55rem; }
    .dth-notify-recipient-chip.is-more { display: inline-flex; justify-content: center; min-width: 44px; color: #667085; font-size: .65rem; font-weight: 760; }
    .dth-notify-history-lock {
        display: inline-flex; align-items: center; gap: .35rem; color: #98a2b3; font-size: .64rem; font-weight: 650;
    }
    .dth-notify-history-lock svg { width: .9rem; height: .9rem; }

    @media (max-width: 760px) {
        .dth-notify-mailboxes { grid-template-columns: 1fr; }
        .dth-notify-message-route { grid-template-columns: 1fr; }
        .dth-notify-route strong { max-width: 150px; }
    }
</style>

<style>
    /* Notification Center professional workspace redesign. */
    .dth-notify-page--workspace { gap: 16px; }

    .dth-notify-summary-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 10px;
    }
    .dth-notify-summary-card {
        --tone: 37, 99, 235;
        appearance: none;
        width: 100%;
        min-width: 0;
        display: grid;
        grid-template-columns: 42px minmax(0, 1fr);
        gap: .7rem;
        align-items: center;
        padding: .78rem .82rem;
        border: 1px solid #e3e9f1;
        border-radius: 14px;
        background: linear-gradient(180deg, #fff 0%, #fbfcff 100%);
        color: #344054;
        text-align: left;
        box-shadow: 0 7px 22px rgba(15, 23, 42, .035);
        transition: transform .15s ease, border-color .15s ease, box-shadow .15s ease;
    }
    button.dth-notify-summary-card { cursor: pointer; }
    button.dth-notify-summary-card:hover {
        transform: translateY(-1px);
        border-color: rgba(var(--tone), .28);
        box-shadow: 0 10px 24px rgba(15, 23, 42, .055);
    }
    .dth-notify-summary-card.is-active {
        border-color: rgba(var(--tone), .36);
        box-shadow: inset 0 0 0 1px rgba(var(--tone), .05), 0 10px 24px rgba(var(--tone), .07);
    }
    .dth-notify-summary-card.is-violet { --tone: 124, 58, 237; }
    .dth-notify-summary-card.is-amber { --tone: 217, 119, 6; }
    .dth-notify-summary-card.is-green { --tone: 22, 163, 74; }
    .dth-notify-summary-card.is-red { --tone: 220, 38, 38; }
    .dth-notify-summary-card.has-alert { background: linear-gradient(180deg, #fff 0%, #fff8f8 100%); }
    .dth-notify-summary-card__icon {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: rgba(var(--tone), .075);
        color: rgb(var(--tone));
    }
    .dth-notify-summary-card__icon svg { width: 19px; height: 19px; }
    .dth-notify-summary-card__copy { min-width: 0; display: grid; grid-template-columns: minmax(0,1fr) auto; gap: .05rem .5rem; align-items: center; }
    .dth-notify-summary-card__copy small { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #667085; font-size: .65rem; font-weight: 720; }
    .dth-notify-summary-card__copy strong { grid-row: 1 / span 2; grid-column: 2; color: #12263f; font-size: 1.42rem; line-height: 1; font-weight: 820; letter-spacing: -.04em; }
    .dth-notify-summary-card__copy em { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #98a2b3; font-size: .58rem; font-style: normal; }

    .dth-notify-workspace-controls {
        overflow: hidden;
        border: 1px solid #e1e7ef;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 10px 30px rgba(15,23,42,.038);
    }
    .dth-notify-mailbox-switch {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        padding: 10px;
        border-bottom: 1px solid #edf1f6;
        background: #fbfcff;
    }
    .dth-notify-mailbox-tab {
        position: relative;
        min-width: 0;
        min-height: 56px;
        display: grid;
        grid-template-columns: 34px minmax(0,1fr) auto;
        gap: .65rem;
        align-items: center;
        padding: .55rem .68rem;
        border: 1px solid transparent;
        border-radius: 12px;
        background: transparent;
        color: #667085;
        text-align: left;
        cursor: pointer;
        transition: all .15s ease;
    }
    .dth-notify-mailbox-tab:hover { background: #f7f9fc; border-color: #e7ecf3; }
    .dth-notify-mailbox-tab.is-active { background: #fff; border-color: #cbdafb; color: #1d4ed8; box-shadow: 0 6px 18px rgba(37,99,235,.07); }
    .dth-notify-mailbox-tab > svg { width: 18px; height: 18px; }
    .dth-notify-mailbox-tab > span { min-width: 0; display: grid; gap: .06rem; }
    .dth-notify-mailbox-tab strong { color: #21364d; font-size: .76rem; font-weight: 790; }
    .dth-notify-mailbox-tab small { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #98a2b3; font-size: .59rem; }
    .dth-notify-mailbox-tab > b { min-width: 1.55rem; height: 1.55rem; display: inline-flex; align-items: center; justify-content: center; padding: 0 .32rem; border-radius: 999px; background: #eef2f7; color: #667085; font-size: .62rem; }
    .dth-notify-mailbox-tab.is-active > b { background: #dbeafe; color: #1d4ed8; }
    .dth-notify-mailbox-tab > i { position: absolute; top: .25rem; right: .25rem; min-width: 1.1rem; height: 1.1rem; display: inline-flex; align-items: center; justify-content: center; padding: 0 .22rem; border: 2px solid #fff; border-radius: 999px; background: #ef4444; color: #fff; font-size: .52rem; font-style: normal; font-weight: 800; }

    .dth-notify-category-tabs {
        display: flex;
        align-items: center;
        gap: .25rem;
        padding: .52rem .75rem .35rem;
        overflow-x: auto;
        border-bottom: 1px solid #edf1f6;
    }
    .dth-notify-category-tab {
        flex: 0 0 auto;
        min-height: 34px;
        display: inline-flex;
        align-items: center;
        gap: .38rem;
        padding: 0 .72rem;
        border: 0;
        border-radius: 9px;
        background: transparent;
        color: #667085;
        font-size: .7rem;
        font-weight: 720;
        cursor: pointer;
    }
    .dth-notify-category-tab:hover { background: #f6f8fb; color: #344054; }
    .dth-notify-category-tab.is-active { background: #eff6ff; color: #1d4ed8; }
    .dth-notify-category-tab b { min-width: 1.25rem; height: 1.25rem; display: inline-flex; align-items: center; justify-content: center; padding: 0 .28rem; border-radius: 999px; background: #f0f2f5; color: #7b8799; font-size: .56rem; }
    .dth-notify-category-tab.is-active b { background: #dbeafe; color: #1d4ed8; }

    .dth-notify-filter-toolbar {
        display: grid;
        grid-template-columns: minmax(300px, 1.7fr) repeat(3, minmax(140px, .72fr)) auto;
        gap: .6rem;
        align-items: center;
        padding: .7rem .75rem .78rem;
    }
    .dth-notify-filter-actions { display: flex; align-items: center; gap: .45rem; justify-content: flex-end; }
    .dth-notify-toolbar-button.is-quiet { color: #667085; background: #f8fafc; }

    .dth-notify-master-detail { grid-template-columns: minmax(0, 1.52fr) minmax(360px, .92fr); gap: 14px; }
    .dth-notify-list-card, .dth-notify-detail-card { border-color: #e1e7ef; box-shadow: 0 10px 30px rgba(15,23,42,.038); }
    .dth-notify-list-card { min-height: 620px; }
    .dth-notify-list-head { min-height: 58px; padding: .72rem .9rem; background: linear-gradient(180deg,#fcfdff 0%,#fafcff 100%); }
    .dth-notify-list-head__title { display: grid; gap: .1rem; }
    .dth-notify-list-head__title span { color: #2563eb; }
    .dth-notify-list-head__title strong { color: #8a96a8; font-size: .65rem; }
    .dth-notify-clear-button { display: inline-flex; align-items: center; gap: .35rem; padding: .36rem .52rem; border-radius: 8px; }
    .dth-notify-clear-button svg { width: .86rem; height: .86rem; }
    .dth-notify-clear-button:hover { background: #fff1f2; }
    .dth-notify-list { max-height: 760px; }

    .dth-notify-row {
        grid-template-columns: 40px minmax(0,1fr) 22px;
        gap: .72rem;
        padding: .86rem .92rem;
        transition: background .12s ease, box-shadow .12s ease;
    }
    .dth-notify-row.is-selected { background: linear-gradient(90deg,#f2f7ff 0%,#fbfdff 78%); box-shadow: inset 3px 0 0 #2563eb; }
    .dth-notify-row.is-unread:not(.is-selected) { background: linear-gradient(90deg,#f8fbff 0%,#fff 72%); }
    .dth-notify-row__main { gap: .3rem; }
    .dth-notify-row__headline { min-width: 0; display: inline-flex; align-items: center; gap: .38rem; }
    .dth-notify-row__headline strong { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dth-notify-row__headline i { flex: 0 0 auto; padding: .13rem .34rem; border-radius: 999px; background: #dbeafe; color: #1d4ed8; font-size: .52rem; line-height: 1.2; font-style: normal; font-weight: 800; text-transform: uppercase; }
    .dth-notify-row__body { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dth-notify-row__route-line { display: flex; align-items: center; gap: .35rem; min-width: 0; color: #8a96a8; }
    .dth-notify-row__route-line > span { min-width: 0; display: inline-flex; align-items: center; gap: .25rem; }
    .dth-notify-row__route-line small { color: #98a2b3; font-size: .55rem; font-weight: 720; text-transform: uppercase; letter-spacing: .04em; }
    .dth-notify-row__route-line b { max-width: 210px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #596579; font-size: .63rem; font-weight: 740; }
    .dth-notify-row__route-line svg { flex: 0 0 auto; width: .85rem; height: .85rem; color: #c4ccd7; }
    .dth-notify-row__meta { gap: .3rem; margin-top: .03rem; }
    .dth-notify-module-chip { display: inline-flex; align-items: center; padding: .15rem .4rem; border-radius: 999px; background: #f7f9fc; color: #667085; font-size: .56rem; font-weight: 700; }
    .dth-notify-mini-stat { display: inline-flex; align-items: center; gap: .2rem; color: #8491a3; font-size: .56rem; }
    .dth-notify-mini-stat b { color: #475467; font-size: .58rem; }
    .dth-notify-row__end { align-self: stretch; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: .45rem; color: #c0c8d4; }
    .dth-notify-row__end svg { width: .86rem; height: .86rem; }
    .dth-notify-row__end.is-sent > svg:first-child { color: #93a4b8; }

    .dth-notify-detail-card {
        top: 1rem;
        max-height: calc(100vh - 7rem);
        overflow: auto;
        padding: 1rem;
    }
    .dth-notify-detail-head { gap: .72rem; padding-bottom: .82rem; }
    .dth-notify-detail-head__copy { min-width: 0; flex: 1; }
    .dth-notify-detail-head__badges { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; }
    .dth-notify-detail-head h2 { margin: .45rem 0 .15rem; font-size: 1rem; }
    .dth-notify-detail-head p { margin: 0; color: #98a2b3; font-size: .61rem; }
    .dth-notify-priority, .dth-notify-status-badge { display: inline-flex; align-items: center; padding: .2rem .45rem; border-radius: 999px; font-size: .56rem; font-weight: 760; }
    .dth-notify-priority { background: #f8fafc; color: #64748b; }
    .dth-notify-priority--high { background: #fff7ed; color: #b45309; }
    .dth-notify-priority--critical { background: #fef2f2; color: #b42318; }
    .dth-notify-priority--low { background: #f0fdf4; color: #15803d; }
    .dth-notify-status-badge.is-unread { background: #eff6ff; color: #1d4ed8; }
    .dth-notify-status-badge.is-read { background: #f2f4f7; color: #667085; }

    .dth-notify-message-route { margin-top: .72rem; padding: .72rem; border-radius: 12px; background: linear-gradient(180deg,#fbfcff 0%,#f8fafc 100%); }
    .dth-notify-message-party { grid-template-columns: 32px minmax(0,1fr); gap: .52rem; padding: .6rem .62rem; }
    .dth-notify-message-party__avatar { width: 32px; height: 32px; border-radius: 10px; }
    .dth-notify-message-party__avatar svg { width: 15px; height: 15px; }
    .dth-notify-message-route__arrow { width: 30px; height: 30px; }

    .dth-notify-detail-facts {
        display: grid;
        grid-template-columns: repeat(3, minmax(0,1fr));
        gap: .5rem;
        padding: .72rem 0;
        border-bottom: 1px solid #edf1f6;
    }
    .dth-notify-detail-facts > div { min-width: 0; display: grid; grid-template-columns: 24px minmax(0,1fr); gap: .08rem .4rem; align-items: center; padding: .58rem .6rem; border: 1px solid #e8edf3; border-radius: 10px; background: #fbfcfe; }
    .dth-notify-detail-facts > div > span { grid-row: 1 / span 2; width: 24px; height: 24px; display: grid; place-items: center; border-radius: 8px; background: #f1f5f9; color: #64748b; }
    .dth-notify-detail-facts svg { width: 13px; height: 13px; }
    .dth-notify-detail-facts small { color: #98a2b3; font-size: .52rem; line-height: 1.3; }
    .dth-notify-detail-facts strong { color: #475467; font-size: .63rem; font-weight: 730; line-height: 1.4; white-space: normal; overflow-wrap: anywhere; word-break: break-word; }

    .dth-notify-delivery-strip { gap: .4rem; padding: .68rem 0; }
    .dth-notify-delivery-strip > span { flex: 1 1 110px; min-width: 0; padding: .5rem .56rem; }
    .dth-notify-recipient-preview { padding: .72rem 0; }
    .dth-notify-section-label { display: flex; align-items: center; justify-content: space-between; gap: .6rem; margin-bottom: .5rem; }
    .dth-notify-section-label > span { color: #586578; font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .075em; }
    .dth-notify-section-label > small { color: #a0a9b8; font-size: .56rem; }

    .dth-notify-content-card { margin-top: .75rem; padding: .8rem .85rem; border: 1px solid #e5eaf1; border-radius: 12px; background: #fff; color: #475467; font-size: .74rem; line-height: 1.62; }
    .dth-notify-content-card p { margin: 0 0 .65rem; color: #344054; font-weight: 680; }
    .dth-notify-content-card p:last-child { margin-bottom: 0; }
    .dth-notify-content-card > div:last-child { color: #5f6c80; }

    .dth-notify-attachments { padding: .78rem 0 .9rem; }
    .dth-notify-attachment { grid-template-columns: 30px minmax(0,1fr) 18px; min-height: 46px; padding: .45rem .58rem; }
    .dth-notify-attachment__icon { width: 30px; height: 30px; display: grid; place-items: center; border-radius: 9px; background: #eff6ff; color: #2563eb; }
    .dth-notify-attachment__icon svg { width: 14px; height: 14px; }
    .dth-notify-detail-actions { position: sticky; bottom: -1rem; margin: .85rem -1rem -1rem; padding: .78rem 1rem; background: rgba(255,255,255,.97); backdrop-filter: blur(8px); }
    .dth-notify-history-lock { margin-left: auto; }

    .dth-notify-empty-state__icon { width: 52px; height: 52px; display: grid; place-items: center; border-radius: 16px; background: #f4f7fb; color: #8a96a8; }
    .dth-notify-empty-state__icon svg { width: 22px; height: 22px; }

    /* Header actions: primary send first, administration links secondary. */
    html body .fi-header-actions { gap: .55rem !important; }
    html body .fi-btn.dth-notify-entry-action--send { order: 10; }
    html body .fi-btn.dth-notify-entry-action--templates { order: 1; }
    html body .fi-btn.dth-notify-entry-action--log { order: 2; }
    html body .fi-btn.dth-notify-entry-action--settings { order: 3; }

    /* Composer modal: sections are visually grouped but stay compact. */
    html body .fi-modal-window:has(.dth-notify-modal-action) .fi-modal-content { padding-top: .85rem !important; }
    html body .fi-modal-window:has(.dth-notify-modal-action) .fi-section {
        box-shadow: none !important;
        border-color: #e5eaf1 !important;
        border-radius: 13px !important;
        background: #fbfcff !important;
    }
    html body .fi-modal-window:has(.dth-notify-modal-action) .fi-section-header { padding: .75rem .85rem .65rem !important; background: #fff !important; }
    html body .fi-modal-window:has(.dth-notify-modal-action) .fi-section-content { padding: .8rem .85rem .9rem !important; }
    html body .fi-modal-window:has(.dth-notify-modal-action) .fi-section-header-heading { font-size: .83rem !important; }
    html body .fi-modal-window:has(.dth-notify-modal-action) .fi-section-header-description { font-size: .66rem !important; line-height: 1.4 !important; }
    html body .fi-modal-window:has(.dth-notify-modal-action) .fi-section-header-icon { color: #2563eb !important; }
    html body .fi-modal-window:has(.dth-notify-modal-action) textarea { min-height: 92px !important; }

    @media (max-width: 1350px) {
        .dth-notify-summary-grid { grid-template-columns: repeat(3,minmax(0,1fr)); }
        .dth-notify-filter-toolbar { grid-template-columns: minmax(260px,1.4fr) repeat(2,minmax(140px,.7fr)); }
        .dth-notify-filter-toolbar > .dth-notify-select:nth-of-type(3) { grid-column: auto; }
        .dth-notify-filter-actions { grid-column: 1 / -1; justify-content: flex-start; }
    }
    @media (max-width: 1180px) {
        .dth-notify-master-detail { grid-template-columns: 1fr; }
        .dth-notify-detail-card { position: static; max-height: none; min-height: auto; }
        .dth-notify-detail-actions { position: static; margin: .85rem 0 0; padding: .75rem 0 0; backdrop-filter: none; }
    }
    @media (max-width: 820px) {
        .dth-notify-summary-grid { grid-template-columns: repeat(2,minmax(0,1fr)); }
        .dth-notify-filter-toolbar { grid-template-columns: 1fr 1fr; }
        .dth-notify-search { grid-column: 1 / -1; }
        .dth-notify-filter-actions { grid-column: 1 / -1; }
        .dth-notify-detail-facts { grid-template-columns: 1fr; }
        .dth-notify-message-route { grid-template-columns: 1fr; }
    }
    @media (max-width: 620px) {
        .dth-notify-summary-grid { grid-template-columns: 1fr; }
        .dth-notify-mailbox-switch { grid-template-columns: 1fr; }
        .dth-notify-filter-toolbar { grid-template-columns: 1fr; }
        .dth-notify-search, .dth-notify-filter-actions { grid-column: auto; }
        .dth-notify-filter-actions { flex-wrap: wrap; }
        .dth-notify-row { grid-template-columns: 36px minmax(0,1fr) 18px; padding: .8rem .75rem; }
        .dth-notify-row__route-line { flex-wrap: wrap; }
        .dth-notify-row__route-line b { max-width: 140px; }
        .dth-notify-detail-card { padding: .85rem; }
        .dth-notify-detail-actions { flex-direction: column; align-items: stretch; }
        .dth-notify-primary-link, .dth-notify-secondary-button, .dth-notify-danger-button { width: 100%; }
        .dth-notify-history-lock { margin-left: 0; }
    }
</style>
