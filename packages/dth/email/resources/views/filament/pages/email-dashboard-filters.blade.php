@php
    use Illuminate\Support\Carbon;

    $rangeLabel = match ($activePreset) {
        '7d' => \Dth\Email\Support\UiText::get('dashboard.filters.presets.7d', 'Last 7 days'),
        '30d' => \Dth\Email\Support\UiText::get('dashboard.filters.presets.30d', 'Last 30 days'),
        '90d' => \Dth\Email\Support\UiText::get('dashboard.filters.presets.90d', 'Last 90 days'),
        'month' => \Dth\Email\Support\UiText::get('dashboard.filters.presets.month', 'This month'),
        default => Carbon::parse($state['start_date'])->format('d/m/Y').' - '.Carbon::parse($state['end_date'])->format('d/m/Y'),
    };

    $scoreState = $insightReport->score >= 85
        ? \Dth\Email\Support\UiText::status('healthy')
        : ($insightReport->score >= 70
            ? \Dth\Email\Support\UiText::get('insights.severity.warning', 'Warning')
            : \Dth\Email\Support\UiText::get('insights.severity.critical', 'Critical'));
    $scoreTone = $insightReport->score >= 85 ? 'success' : ($insightReport->score >= 70 ? 'warning' : 'danger');
    $severityMeta = [
        'critical' => ['label' => \Dth\Email\Support\UiText::get('insights.severity.critical', 'Critical'), 'tone' => 'danger', 'icon' => 'heroicon-o-exclamation-triangle'],
        'warning' => ['label' => \Dth\Email\Support\UiText::get('insights.severity.warning', 'Needs attention'), 'tone' => 'warning', 'icon' => 'heroicon-o-exclamation-circle'],
        'positive' => ['label' => \Dth\Email\Support\UiText::get('insights.severity.positive', 'Positive'), 'tone' => 'success', 'icon' => 'heroicon-o-arrow-trending-up'],
        'neutral' => ['label' => \Dth\Email\Support\UiText::get('insights.severity.neutral', 'Information'), 'tone' => 'info', 'icon' => 'heroicon-o-information-circle'],
    ];
@endphp

<style>
    [x-cloak] { display: none !important; }

    :root {
        --dth-email-bg: #f4f7fb;
        --dth-email-card: #ffffff;
        --dth-email-border: #e4eaf2;
        --dth-email-text: #111827;
        --dth-email-muted: #718096;
        --dth-email-orange: #f59e0b;
        --dth-email-orange-strong: #ffae00;
        --dth-email-blue: #2f80ed;
        --dth-email-green: #12b76a;
        --dth-email-red: #ef4444;
        --dth-email-radius: 13px;
        --dth-email-shadow: 0 8px 24px rgba(16, 24, 40, .045);
    }

    body:has(.dth-email-dashboard-root) .fi-main {
        background: var(--dth-email-bg) !important;
    }

    body:has(.dth-email-dashboard-root) .fi-main .fi-header {
        display: none !important;
    }

    body:has(.dth-email-dashboard-root) .fi-page {
        gap: 14px !important;
    }

    body:has(.dth-email-dashboard-root) .fi-section {
        border-color: var(--dth-email-border) !important;
        border-radius: var(--dth-email-radius) !important;
        box-shadow: var(--dth-email-shadow) !important;
    }

    .dth-email-dashboard-root {
        display: grid;
        gap: 14px;
    }

    .dth-email-widgets-grid {
        gap: 14px !important;
    }

    .dth-email-dashboard-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        min-height: 66px;
    }

    .dth-email-dashboard-heading {
        display: flex;
        min-width: 0;
        align-items: center;
        gap: 16px;
    }

    .dth-email-dashboard-heading-icon {
        display: grid;
        width: 54px;
        height: 54px;
        flex: 0 0 54px;
        place-items: center;
        border: 1px solid #fbe7ba;
        border-radius: 15px;
        color: #f59e0b;
        background: linear-gradient(145deg, #fff5dc 0%, #fff 100%);
        box-shadow: 0 8px 22px rgba(245, 158, 11, .10);
    }

    .dth-email-dashboard-heading-icon svg {
        width: 25px;
        height: 25px;
    }

    .dth-email-dashboard-title {
        margin: 0;
        color: #111827;
        font-size: 1.72rem;
        font-weight: 800;
        letter-spacing: -.035em;
        line-height: 1.1;
    }

    .dth-email-dashboard-subtitle {
        margin-top: 6px;
        color: #74819a;
        font-size: .88rem;
        font-weight: 450;
        line-height: 1.4;
    }

    .dth-email-dashboard-actions {
        display: flex;
        flex: 0 0 auto;
        align-items: center;
        gap: 9px;
    }

    .dth-email-header-btn {
        display: inline-flex;
        min-height: 38px;
        align-items: center;
        justify-content: center;
        gap: 7px;
        padding: 0 13px;
        border: 1px solid #e2e8f0;
        border-radius: 9px;
        color: #344054;
        background: #fff;
        box-shadow: 0 2px 5px rgba(16, 24, 40, .025);
        font-size: .76rem;
        font-weight: 650;
        text-decoration: none;
        transition: border-color .15s ease, background .15s ease, transform .15s ease;
    }

    .dth-email-header-btn:hover {
        transform: translateY(-1px);
        border-color: #d4dce8;
        background: #fbfcfe;
    }

    .dth-email-header-btn svg { width: 17px; height: 17px; }
    .dth-email-header-btn--pdf { color: #c87500; }
    .dth-email-header-btn--excel { color: #15803d; }
    .dth-email-header-btn--analysis { color: #344054; }

    .dth-email-filter-card,
    .dth-email-panel {
        border: 1px solid var(--dth-email-border);
        border-radius: var(--dth-email-radius);
        background: #fff;
        box-shadow: var(--dth-email-shadow);
    }

    .dth-email-filter-card { overflow: hidden; }

    .dth-email-filter-header {
        display: flex;
        min-height: 42px;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 9px 15px;
        border-bottom: 1px solid #edf1f6;
    }

    .dth-email-filter-title,
    .dth-email-reset {
        display: inline-flex;
        align-items: center;
        gap: 7px;
    }

    .dth-email-filter-title {
        color: #253047;
        font-size: .88rem;
        font-weight: 750;
    }

    .dth-email-filter-title svg,
    .dth-email-reset svg { width: 17px; height: 17px; }

    .dth-email-reset {
        color: #667085;
        font-size: .72rem;
        font-weight: 620;
        text-decoration: none;
    }

    .dth-email-filter-form {
        display: grid;
        grid-template-columns: minmax(135px, .92fr) minmax(145px, 1fr) minmax(145px, 1fr) minmax(170px, 1.2fr) minmax(170px, 1.2fr) 142px;
        gap: 11px;
        align-items: end;
        padding: 10px 15px 13px;
    }

    .dth-email-filter-field { min-width: 0; }

    .dth-email-filter-field > label {
        display: block;
        margin-bottom: 5px;
        color: #536078;
        font-size: .68rem;
        font-weight: 650;
    }

    .dth-email-filter-control,
    .dth-email-date-shell {
        width: 100%;
        height: 37px;
        border: 1px solid #dde4ed;
        border-radius: 8px;
        color: #344054;
        background: #fff;
        box-shadow: 0 1px 2px rgba(16, 24, 40, .02);
        font-size: .75rem;
    }

    select.dth-email-filter-control {
        padding: 0 31px 0 10px;
        outline: none;
    }

    .dth-email-date-shell {
        position: relative;
        display: flex;
        align-items: center;
        overflow: hidden;
    }

    .dth-email-date-shell input[type="text"] {
        width: 100%;
        height: 100%;
        border: 0;
        outline: 0;
        padding: 0 36px 0 10px;
        color: #344054;
        background: transparent;
        font-size: .75rem;
    }

    .dth-email-date-native {
        position: absolute;
        right: 6px;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    .dth-email-date-button {
        position: absolute;
        right: 0;
        top: 0;
        display: grid;
        width: 36px;
        height: 100%;
        place-items: center;
        border: 0;
        color: #475467;
        background: transparent;
        cursor: pointer;
    }

    .dth-email-date-button svg { width: 16px; height: 16px; }

    .dth-email-filter-submit {
        display: inline-flex;
        width: 100%;
        height: 37px;
        align-items: center;
        justify-content: center;
        border: 0;
        border-radius: 8px;
        color: #654100;
        background: linear-gradient(180deg, #ffbf18 0%, #ffad00 100%);
        box-shadow: 0 6px 13px rgba(245, 158, 11, .14);
        font-size: .75rem;
        font-weight: 750;
        cursor: pointer;
    }

    .dth-email-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
    }

    .dth-email-kpi-card {
        --tone: #2563eb;
        --tone-soft: #eaf2ff;
        position: relative;
        display: grid;
        grid-template-columns: 48px minmax(0, 1fr);
        min-height: 101px;
        gap: 11px;
        overflow: hidden;
        padding: 13px 13px 12px;
        border: 1px solid var(--dth-email-border);
        border-radius: 12px;
        color: inherit;
        background: #fff;
        box-shadow: 0 7px 20px rgba(16, 24, 40, .04);
        text-decoration: none;
    }

    .dth-email-kpi-card[data-tone="amber"] { --tone: #f59e0b; --tone-soft: #fff2d8; }
    .dth-email-kpi-card[data-tone="blue"] { --tone: #2563eb; --tone-soft: #eaf2ff; }
    .dth-email-kpi-card[data-tone="green"] { --tone: #0fb36b; --tone-soft: #e8f9ef; }
    .dth-email-kpi-card[data-tone="red"] { --tone: #ef4444; --tone-soft: #ffeded; }

    .dth-email-kpi-icon {
        display: grid;
        width: 44px;
        height: 44px;
        place-items: center;
        border-radius: 12px;
        color: var(--tone);
        background: var(--tone-soft);
    }

    .dth-email-kpi-icon svg { width: 22px; height: 22px; }

    .dth-email-kpi-label {
        overflow: hidden;
        color: #667085;
        font-size: .71rem;
        font-weight: 560;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dth-email-kpi-value {
        margin-top: 2px;
        color: #101828;
        font-size: 1.25rem;
        font-weight: 800;
        letter-spacing: -.03em;
        line-height: 1.08;
    }

    .dth-email-kpi-delta {
        display: flex;
        align-items: center;
        gap: 3px;
        margin-top: 5px;
        color: #98a2b3;
        font-size: .62rem;
        line-height: 1.18;
    }

    .dth-email-kpi-delta svg { width: 12px; height: 12px; flex: 0 0 auto; }
    .dth-email-kpi-delta[data-color="success"] { color: #0aa85f; }
    .dth-email-kpi-delta[data-color="danger"] { color: #e5484d; }
    .dth-email-kpi-delta[data-color="warning"] { color: #d97706; }

    .dth-email-kpi-sparkline {
        position: absolute;
        right: 9px;
        bottom: 8px;
        width: 76px;
        height: 23px;
        opacity: .95;
    }

    .dth-email-kpi-sparkline polyline {
        stroke: var(--tone);
        stroke-width: 1.55px;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .dth-email-kpi-sparkline-area {
        fill: color-mix(in srgb, var(--tone) 10%, transparent);
    }

    .dth-email-panel {
        height: 100%;
        overflow: hidden;
    }

    .dth-email-panel-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding: 13px 14px 8px;
    }

    .dth-email-panel-header h3 {
        margin: 0;
        color: #17213a;
        font-size: .91rem;
        font-weight: 780;
        letter-spacing: -.015em;
    }

    .dth-email-panel-header p {
        margin-top: 3px;
        color: #8995aa;
        font-size: .67rem;
        line-height: 1.35;
    }

    .dth-email-panel-footer {
        margin-top: auto;
        padding: 9px 14px 11px;
        border-top: 1px solid #edf1f5;
    }

    .dth-email-panel-footer a {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #26344f;
        font-size: .68rem;
        font-weight: 700;
        text-decoration: none;
    }

    .dth-email-panel-footer a svg { width: 13px; height: 13px; }
    .dth-email-panel-footer-muted { color: #98a2b3; font-size: .65rem; }

    .dth-email-table-widget {
        display: flex;
        min-height: 226px;
        flex-direction: column;
    }

    .dth-email-mini-table-wrap {
        flex: 1 1 auto;
        overflow-x: auto;
        padding: 0 11px 3px;
    }

    .dth-email-mini-table {
        width: 100%;
        border-collapse: collapse;
        color: #475467;
        font-size: .65rem;
    }

    .dth-email-mini-table th {
        padding: 7px 6px;
        border-bottom: 1px solid #e8edf3;
        color: #667085;
        font-size: .58rem;
        font-weight: 680;
        text-align: left;
        white-space: nowrap;
    }

    .dth-email-mini-table td {
        padding: 7px 6px;
        border-bottom: 1px solid #f0f2f6;
        vertical-align: middle;
    }

    .dth-email-mini-table tr:last-child td { border-bottom: 0; }

    .dth-email-mini-table td strong {
        display: block;
        max-width: 155px;
        overflow: hidden;
        color: #344054;
        font-weight: 680;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dth-email-mini-table td span {
        display: block;
        max-width: 155px;
        margin-top: 1px;
        overflow: hidden;
        color: #98a2b3;
        font-size: .58rem;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .dth-email-accent-value { color: #c96c00 !important; font-weight: 750; }
    .dth-email-url-cell { max-width: 235px; overflow: hidden; color: #344054; text-overflow: ellipsis; white-space: nowrap; }

    .dth-email-empty {
        display: grid;
        min-height: 112px;
        flex: 1 1 auto;
        place-items: center;
        padding: 25px 14px;
        color: #98a2b3;
        font-size: .72rem;
        text-align: center;
    }

    .dth-email-funnel-panel { min-height: 286px; }

    .dth-email-funnel {
        display: grid;
        grid-template-columns: minmax(0, 1.5fr) minmax(92px, .72fr);
        gap: 13px;
        align-items: center;
        padding: 3px 15px 15px;
    }

    .dth-email-funnel-shapes,
    .dth-email-funnel-labels {
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 4px;
    }

    .dth-email-funnel-shape {
        --funnel-width: calc(100% - (var(--funnel-level) * 13%));
        display: grid;
        width: var(--funnel-width);
        min-height: 39px;
        margin-inline: auto;
        place-items: center;
        clip-path: polygon(4% 0, 96% 0, 86% 100%, 14% 100%);
        color: #fff;
        background: var(--funnel-color);
        font-size: .78rem;
        font-weight: 800;
    }

    .dth-email-funnel-shape[data-tone="navy"] { --funnel-color: #315d8c; }
    .dth-email-funnel-shape[data-tone="blue"] { --funnel-color: #2f73ea; }
    .dth-email-funnel-shape[data-tone="green"] { --funnel-color: #13b77a; }
    .dth-email-funnel-shape[data-tone="mint"] { --funnel-color: #8cdcb8; color: #194d39; }

    .dth-email-funnel-label {
        display: flex;
        min-height: 39px;
        flex-direction: column;
        justify-content: center;
        border-bottom: 1px solid #edf1f5;
        color: #667085;
        font-size: .64rem;
    }

    .dth-email-funnel-label:last-child { border-bottom: 0; }
    .dth-email-funnel-label strong { margin-top: 1px; color: #2563eb; font-size: .69rem; }

    body:has(.dth-email-dashboard-root) .fi-wi-chart .fi-section {
        min-height: 286px;
        overflow: hidden;
    }

    body:has(.dth-email-dashboard-root) .fi-wi-chart .fi-section-header {
        padding: 13px 14px 7px !important;
    }

    body:has(.dth-email-dashboard-root) .fi-wi-chart .fi-section-header-heading {
        color: #17213a !important;
        font-size: .91rem !important;
        font-weight: 780 !important;
    }

    body:has(.dth-email-dashboard-root) .fi-wi-chart .fi-section-header-description {
        color: #8995aa !important;
        font-size: .67rem !important;
    }

    body:has(.dth-email-dashboard-root) .fi-wi-chart .fi-section-content {
        padding: 0 13px 12px !important;
    }

    .dth-email-insights-backdrop {
        position: fixed;
        inset: 0;
        z-index: 9998;
        background: rgba(15, 23, 42, .46);
        backdrop-filter: blur(2px);
    }

    .dth-email-insights-modal {
        position: fixed;
        left: 50%;
        top: 50%;
        z-index: 9999;
        width: min(760px, calc(100vw - 32px));
        max-height: min(82vh, 760px);
        overflow: auto;
        transform: translate(-50%, -50%);
        border: 1px solid #e3e9f1;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 30px 80px rgba(15, 23, 42, .25);
    }

    .dth-email-insights-modal-header {
        position: sticky;
        top: 0;
        z-index: 2;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 17px 19px;
        border-bottom: 1px solid #edf1f6;
        background: rgba(255, 255, 255, .96);
        backdrop-filter: blur(10px);
    }

    .dth-email-insights-modal-header h3 { margin: 0; color: #111827; font-size: 1.08rem; font-weight: 800; }
    .dth-email-insights-modal-header p { margin-top: 3px; color: #7b879b; font-size: .72rem; }

    .dth-email-insights-close {
        display: grid;
        width: 34px;
        height: 34px;
        place-items: center;
        border: 1px solid #e4e9f0;
        border-radius: 9px;
        color: #667085;
        background: #fff;
        cursor: pointer;
    }
    .dth-email-insights-close svg { width: 17px; height: 17px; }

    .dth-email-insights-content { padding: 18px; }

    .dth-email-insights-summary {
        display: grid;
        grid-template-columns: 122px 1fr;
        gap: 16px;
        align-items: center;
        padding: 15px;
        border: 1px solid #e6ebf2;
        border-radius: 14px;
        background: #f8fafc;
    }

    .dth-email-score {
        display: grid;
        width: 96px;
        height: 96px;
        place-items: center;
        border-radius: 999px;
        color: #111827;
        background: conic-gradient(#f59e0b calc(var(--score) * 1%), #e8edf4 0);
    }

    .dth-email-score::before {
        grid-area: 1 / 1;
        width: 74px;
        height: 74px;
        border-radius: inherit;
        background: #fff;
        content: "";
    }

    .dth-email-score span { z-index: 1; font-size: 1.35rem; font-weight: 850; }
    .dth-email-score small { display: block; color: #98a2b3; font-size: .58rem; font-weight: 600; text-align: center; }

    .dth-email-insights-summary h4 { margin: 0; color: #1f2937; font-size: .9rem; font-weight: 780; }
    .dth-email-insights-summary p { margin-top: 6px; color: #667085; font-size: .75rem; line-height: 1.6; }

    .dth-email-insights-list { display: grid; gap: 10px; margin-top: 14px; }

    .dth-email-insight-item {
        display: grid;
        grid-template-columns: 36px minmax(0, 1fr) auto;
        gap: 11px;
        align-items: start;
        padding: 12px;
        border: 1px solid #e7ecf3;
        border-radius: 12px;
        background: #fff;
    }

    .dth-email-insight-icon {
        display: grid;
        width: 36px;
        height: 36px;
        place-items: center;
        border-radius: 10px;
        background: #f4f7fb;
    }
    .dth-email-insight-icon svg { width: 18px; height: 18px; }
    .dth-email-insight-item h5 { margin: 0; color: #253047; font-size: .78rem; font-weight: 750; }
    .dth-email-insight-item p { margin-top: 4px; color: #667085; font-size: .7rem; line-height: 1.5; }

    .dth-email-insight-badge {
        padding: 4px 8px;
        border-radius: 999px;
        font-size: .6rem;
        font-weight: 700;
        white-space: nowrap;
    }
    .dth-email-insight-badge[data-tone="danger"] { color: #b42318; background: #feeceb; }
    .dth-email-insight-badge[data-tone="warning"] { color: #b54708; background: #fff4e5; }
    .dth-email-insight-badge[data-tone="success"] { color: #067647; background: #e9f8ef; }
    .dth-email-insight-badge[data-tone="info"] { color: #175cd3; background: #eef4ff; }

    .dark {
        --dth-email-bg: #101522;
        --dth-email-card: #171d2a;
        --dth-email-border: rgba(255,255,255,.08);
        --dth-email-text: #f5f7fa;
    }

    .dark .dth-email-filter-card,
    .dark .dth-email-panel,
    .dark .dth-email-kpi-card,
    .dark .dth-email-header-btn,
    .dark .dth-email-insights-modal,
    .dark .dth-email-insight-item { background: #171d2a; }

    .dark .dth-email-dashboard-title,
    .dark .dth-email-kpi-value,
    .dark .dth-email-panel-header h3,
    .dark .dth-email-insights-modal-header h3,
    .dark .dth-email-insight-item h5 { color: #f2f4f7; }

    .dark .dth-email-filter-control,
    .dark .dth-email-date-shell { border-color: rgba(255,255,255,.09); background: #141b28; }

    .dark .dth-email-date-shell input[type="text"],
    .dark select.dth-email-filter-control { color: #e5e7eb; background: #141b28; }

    .dark .dth-email-insights-modal-header { background: rgba(23,29,42,.96); }
    .dark .dth-email-insights-summary { border-color: rgba(255,255,255,.08); background: #121925; }
    .dark .dth-email-score::before { background: #171d2a; }

    @media (max-width: 1199px) {
        .dth-email-filter-form { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .dth-email-dashboard-header { align-items: flex-start; flex-direction: column; }
    }

    @media (max-width: 900px) {
        .dth-email-kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .dth-email-dashboard-actions { flex-wrap: wrap; }
    }

    @media (max-width: 640px) {
        .dth-email-filter-form { grid-template-columns: 1fr; }
        .dth-email-kpi-grid { grid-template-columns: 1fr; }
        .dth-email-funnel { grid-template-columns: 1fr; }
        .dth-email-insights-summary { grid-template-columns: 1fr; }
        .dth-email-dashboard-heading-icon { display: none; }
        .dth-email-dashboard-title { font-size: 1.45rem; }
        .dth-email-header-btn span { display: none; }
    }
</style>

<div class="dth-email-dashboard-root" x-data="{ insightsOpen: false }" @keydown.escape.window="insightsOpen = false">
    <header class="dth-email-dashboard-header">
        <div class="dth-email-dashboard-heading">
            <div class="dth-email-dashboard-heading-icon">
                <x-filament::icon icon="heroicon-o-envelope" />
            </div>
            <div>
                <h1 class="dth-email-dashboard-title">{{ \Dth\Email\Support\UiText::get('dashboard.title', 'Email Overview') }}</h1>
                <p class="dth-email-dashboard-subtitle">
                    {{ \Dth\Email\Support\UiText::get('dashboard.subheading', 'Track email marketing performance from :start to :end.', [
                        'start' => Carbon::parse($state['start_date'])->format('d/m/Y'),
                        'end' => Carbon::parse($state['end_date'])->format('d/m/Y'),
                    ]) }}
                </p>
            </div>
        </div>

        <div class="dth-email-dashboard-actions">
            <button type="button" class="dth-email-header-btn dth-email-header-btn--analysis" @click="insightsOpen = true">
                <x-filament::icon icon="heroicon-o-light-bulb" />
                <span>{{ \Dth\Email\Support\UiText::get('insights.heading', 'Statistical insights') }}</span>
            </button>

            @if ($pdfEnabled)
                <a class="dth-email-header-btn dth-email-header-btn--pdf" href="{{ $exportUrls['pdf'] }}">
                    <x-filament::icon icon="heroicon-o-document-arrow-down" />
                    <span>{{ \Dth\Email\Support\UiText::get('reports.export_pdf', 'Export PDF') }}</span>
                </a>
            @endif

            <a class="dth-email-header-btn dth-email-header-btn--excel" href="{{ $exportUrls['xlsx'] }}">
                <x-filament::icon icon="heroicon-o-table-cells" />
                <span>{{ \Dth\Email\Support\UiText::get('reports.export_xlsx', 'Export Excel') }}</span>
            </a>

            <a class="dth-email-header-btn" href="{{ $exportUrls['csv'] }}">
                <x-filament::icon icon="heroicon-o-arrow-down-tray" />
                <span>{{ \Dth\Email\Support\UiText::get('reports.export_csv', 'Export CSV') }}</span>
            </a>
        </div>
    </header>

    <section class="dth-email-filter-card">
        <div class="dth-email-filter-header">
            <div class="dth-email-filter-title">
                <x-filament::icon icon="heroicon-o-adjustments-horizontal" />
                <span>{{ \Dth\Email\Support\UiText::get('dashboard.filters.heading', 'Filters') }}</span>
            </div>

            <a href="{{ $resetUrl }}" class="dth-email-reset">
                <x-filament::icon icon="heroicon-o-arrow-path" />
                {{ \Dth\Email\Support\UiText::get('dashboard.filters.reset', 'Reset') }}
            </a>
        </div>

        <form method="GET" action="{{ $actionUrl }}" class="dth-email-filter-form">
            <input type="hidden" name="compare_previous" value="{{ $state['compare_previous'] ? '1' : '0' }}" />

            <div class="dth-email-filter-field">
                <label for="dth-email-range">{{ \Dth\Email\Support\UiText::get('dashboard.filters.range_label', 'Date range') }}</label>
                <select id="dth-email-range" class="dth-email-filter-control" onchange="if (this.value) window.location.href = this.value">
                    <option value="">{{ $rangeLabel }}</option>
                    @foreach ($presets as $key => $preset)
                        <option value="{{ $preset['url'] }}" @selected($activePreset === $key)>{{ $preset['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="dth-email-filter-field">
                <label>{{ \Dth\Email\Support\UiText::get('dashboard.filters.start_date', 'Start date') }}</label>
                <div
                    class="dth-email-date-shell"
                    x-data="{
                        iso: @js($state['start_date']),
                        display: @js(Carbon::parse($state['start_date'])->format('d/m/Y')),
                        fromIso() { this.display = this.iso ? this.iso.split('-').reverse().join('/') : ''; },
                        toIso() {
                            const parts = this.display.split('/');
                            if (parts.length === 3 && parts[0].length <= 2 && parts[1].length <= 2 && parts[2].length === 4) {
                                this.iso = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
                            }
                        }
                    }"
                >
                    <input type="text" x-model="display" @change="toIso()" inputmode="numeric" aria-label="{{ \Dth\Email\Support\UiText::get('dashboard.filters.start_date', 'Start date') }}" />
                    <input x-ref="picker" class="dth-email-date-native" type="date" x-model="iso" @change="fromIso()" max="{{ now()->toDateString() }}" />
                    <input type="hidden" name="start_date" :value="iso" />
                    <button type="button" class="dth-email-date-button" @click="$refs.picker.showPicker ? $refs.picker.showPicker() : $refs.picker.click()" aria-label="{{ \Dth\Email\Support\UiText::get('dashboard.filters.start_date', 'Start date') }}">
                        <x-filament::icon icon="heroicon-o-calendar-days" />
                    </button>
                </div>
            </div>

            <div class="dth-email-filter-field">
                <label>{{ \Dth\Email\Support\UiText::get('dashboard.filters.end_date', 'End date') }}</label>
                <div
                    class="dth-email-date-shell"
                    x-data="{
                        iso: @js($state['end_date']),
                        display: @js(Carbon::parse($state['end_date'])->format('d/m/Y')),
                        fromIso() { this.display = this.iso ? this.iso.split('-').reverse().join('/') : ''; },
                        toIso() {
                            const parts = this.display.split('/');
                            if (parts.length === 3 && parts[0].length <= 2 && parts[1].length <= 2 && parts[2].length === 4) {
                                this.iso = `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`;
                            }
                        }
                    }"
                >
                    <input type="text" x-model="display" @change="toIso()" inputmode="numeric" aria-label="{{ \Dth\Email\Support\UiText::get('dashboard.filters.end_date', 'End date') }}" />
                    <input x-ref="picker" class="dth-email-date-native" type="date" x-model="iso" @change="fromIso()" max="{{ now()->toDateString() }}" />
                    <input type="hidden" name="end_date" :value="iso" />
                    <button type="button" class="dth-email-date-button" @click="$refs.picker.showPicker ? $refs.picker.showPicker() : $refs.picker.click()" aria-label="{{ \Dth\Email\Support\UiText::get('dashboard.filters.end_date', 'End date') }}">
                        <x-filament::icon icon="heroicon-o-calendar-days" />
                    </button>
                </div>
            </div>

            <div class="dth-email-filter-field">
                <label>{{ \Dth\Email\Support\UiText::get('dashboard.filters.sending_account', 'Sending account') }}</label>
                <select class="dth-email-filter-control" name="sending_account_id">
                    <option value="">{{ \Dth\Email\Support\UiText::get('dashboard.filters.all_accounts', 'All accounts') }}</option>
                    @foreach ($sendingAccounts as $id => $name)
                        <option value="{{ $id }}" @selected((string) $state['sending_account_id'] === (string) $id)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="dth-email-filter-field">
                <label>{{ \Dth\Email\Support\UiText::get('dashboard.filters.campaign_status', 'Campaign status') }}</label>
                <select class="dth-email-filter-control" name="campaign_status">
                    <option value="">{{ \Dth\Email\Support\UiText::get('dashboard.filters.all_statuses', 'All statuses') }}</option>
                    @foreach ($campaignStatuses as $value => $label)
                        <option value="{{ $value }}" @selected((string) $state['campaign_status'] === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="dth-email-filter-submit">{{ \Dth\Email\Support\UiText::get('dashboard.filters.apply', 'Apply') }}</button>
        </form>
    </section>

    <template x-teleport="body">
        <div x-cloak x-show="insightsOpen">
            <div class="dth-email-insights-backdrop" @click="insightsOpen = false" x-transition.opacity></div>
            <section class="dth-email-insights-modal" role="dialog" aria-modal="true" aria-label="{{ \Dth\Email\Support\UiText::get('insights.heading', 'Statistical analysis') }}" x-transition>
                <header class="dth-email-insights-modal-header">
                    <div>
                        <h3>{{ \Dth\Email\Support\UiText::get('insights.heading', 'Statistical analysis') }}</h3>
                        <p>{{ \Dth\Email\Support\UiText::get('insights.heading', 'Statistical insights') }}</p>
                    </div>
                    <button type="button" class="dth-email-insights-close" @click="insightsOpen = false" aria-label="{{ \Dth\Email\Support\UiText::get('common.actions.close', 'Close') }}">
                        <x-filament::icon icon="heroicon-o-x-mark" />
                    </button>
                </header>

                <div class="dth-email-insights-content">
                    <div class="dth-email-insights-summary">
                        <div>
                            <div class="dth-email-score" style="--score: {{ max(0, min(100, $insightReport->score)) }}">
                                <span>{{ $insightReport->score }}<small>/100</small></span>
                            </div>
                        </div>
                        <div>
                            <h4>{{ \Dth\Email\Support\UiText::get('insights.heading', 'Statistical insights') }} · {{ $scoreState }}</h4>
                            <p>{{ $insightReport->summary }}</p>
                        </div>
                    </div>

                    <div class="dth-email-insights-list">
                        @forelse ($insightReport->items as $item)
                            @php($meta = $severityMeta[$item->severity] ?? $severityMeta['neutral'])
                            <article class="dth-email-insight-item">
                                <div class="dth-email-insight-icon">
                                    <x-filament::icon :icon="$meta['icon']" />
                                </div>
                                <div>
                                    <h5>{{ $item->title }}</h5>
                                    <p>{{ $item->body }}</p>
                                </div>
                                <span class="dth-email-insight-badge" data-tone="{{ $meta['tone'] }}">{{ $meta['label'] }}</span>
                            </article>
                        @empty
                            <div class="dth-email-empty">{{ \Dth\Email\Support\UiText::get('insights.no_findings', 'No notable statistical findings for this period.') }}</div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </template>
</div>
