{{--
    Filament core components intentionally use the framework's stock styles.
    The rules below are limited to custom DTH dashboard/report markup and do
    not override Filament forms, tables, modals, dropdowns, notifications, or sidebar.
--}}
<style>
    :root {--dth-radius-sm: .625rem;
        --dth-radius-md: .875rem;
        --dth-radius-lg: 1rem;
        --dth-border: rgb(229 231 235);
        --dth-muted: rgb(107 114 128);
        --dth-surface-soft: rgb(249 250 251);}

    .dark {--dth-border: rgba(255, 255, 255, .10);
        --dth-muted: rgb(156 163 175);
        --dth-surface-soft: rgba(255, 255, 255, .035);}

    .dth-report-grid {display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 1rem;}

    @media (min-width: 768px) {
        .dth-report-grid {grid-template-columns: repeat(2, minmax(0, 1fr));}
    }

    @media (min-width: 1280px) {
        .dth-report-grid.dth-report-grid-4 {grid-template-columns: repeat(4, minmax(0, 1fr));}
    }

    .dth-metric-card {border: 1px solid var(--dth-border);
        border-radius: var(--dth-radius-md);
        padding: 1.1rem;
        background: transparent;}

    .dth-metric-label {color: var(--dth-muted);
        font-size: .78rem;
        font-weight: 600;}

    .dth-metric-value {margin-top: .35rem;
        font-size: 1.55rem;
        line-height: 1.2;
        font-weight: 750;
        letter-spacing: -.025em;}

    .dth-report-table {width: 100%;
        font-size: .82rem;}

    .dth-report-table th {padding: .7rem .65rem;
        border-bottom: 1px solid var(--dth-border);
        color: var(--dth-muted);
        font-size: .71rem;
        font-weight: 700;
        letter-spacing: .025em;
        text-transform: uppercase;}

    .dth-report-table td {padding: .75rem .65rem;
        border-bottom: 1px solid var(--dth-border);}

    .dth-report-table tbody tr:last-child td {border-bottom: 0;}

    .dth-report-table tbody tr:hover {background: var(--dth-surface-soft);}

    .dth-filter-label {display: block;
        margin-bottom: .35rem;
        color: var(--dth-muted);
        font-size: .75rem;
        font-weight: 650;}

    .dth-note {border: 1px solid var(--dth-border);
        border-radius: var(--dth-radius-sm);
        padding: .9rem 1rem;
        background: var(--dth-surface-soft);
        color: var(--dth-muted);
        font-size: .8rem;
        line-height: 1.55;}

    .dth-dashboard-hero {display: flex;
        flex-direction: column;
        gap: 1rem;
        justify-content: space-between;
        border: 1px solid var(--dth-border);
        border-radius: var(--dth-radius-lg);
        padding: 1.35rem 1.4rem;
        background:
            radial-gradient(circle at 100% 0%, rgba(var(--primary-500), .10), transparent 38%),
            var(--dth-surface-soft);}

    @media (min-width: 768px) {
        .dth-dashboard-hero {flex-direction: row; align-items: center;}
    }

    .dth-dashboard-hero h2 {margin-top: .18rem;
        color: rgb(17 24 39);
        font-size: 1.45rem;
        line-height: 1.25;
        font-weight: 760;
        letter-spacing: -.025em;}

    .dark .dth-dashboard-hero h2 {color: white;}

    .dth-dashboard-hero p:not(.dth-eyebrow) {margin-top: .3rem;
        max-width: 52rem;
        color: var(--dth-muted);
        font-size: .86rem;
        line-height: 1.55;}

    .dth-eyebrow {color: var(--dth-muted);
        font-size: .68rem;
        font-weight: 760;
        letter-spacing: .08em;
        text-transform: uppercase;}

    .dth-tone-admin {box-shadow: inset 4px 0 0 rgb(245 158 11);}

    .dth-tone-marketing {box-shadow: inset 4px 0 0 rgb(168 85 247);}

    .dth-tone-customer-service {box-shadow: inset 4px 0 0 rgb(14 165 233);}

    .dth-tone-sales {box-shadow: inset 4px 0 0 rgb(245 158 11);}

    .dth-tone-finance {box-shadow: inset 4px 0 0 rgb(34 197 94);}

    .dth-metric-card[data-tone="success"] {box-shadow: inset 0 3px 0 rgb(34 197 94);}

    .dth-metric-card[data-tone="warning"] {box-shadow: inset 0 3px 0 rgb(245 158 11);}

    .dth-metric-card[data-tone="danger"] {box-shadow: inset 0 3px 0 rgb(239 68 68);}

    .dth-metric-card[data-tone="info"] {box-shadow: inset 0 3px 0 rgb(14 165 233);}

    .dth-metric-card[data-tone="primary"] {box-shadow: inset 0 3px 0 rgb(var(--primary-500));}

    .dth-metric-card[data-tone="gray"] {box-shadow: inset 0 3px 0 rgb(156 163 175);}

    .dth-metric-card[data-tone] svg {color: var(--dth-muted);}

    .dth-bar-row {display: grid; gap: .45rem;}

    .dth-bar-meta {display: flex; align-items: baseline; justify-content: space-between; gap: 1rem; font-size: .8rem;}

    .dth-bar-meta span {color: rgb(55 65 81); font-weight: 650;}

    .dark .dth-bar-meta span {color: rgb(229 231 235);}

    .dth-bar-meta strong {color: var(--dth-muted); font-size: .75rem; font-weight: 700; text-align: right;}

    .dth-bar-track {height: .52rem; overflow: hidden; border-radius: 999px; background: var(--dth-surface-soft); box-shadow: inset 0 0 0 1px var(--dth-border);}

    .dth-bar-fill {display: block; height: 100%; border-radius: inherit; min-width: .25rem;}

    .dth-bar-admin {background: rgb(245 158 11);}

    .dth-bar-marketing {background: rgb(168 85 247);}

    .dth-bar-info {background: rgb(14 165 233);}

    .dth-bar-sales {background: rgb(245 158 11);}

    .dth-bar-finance {background: rgb(34 197 94);}

    .dth-column-chart {display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: .75rem;
        min-height: 13rem;
        align-items: end;}

    .dth-column-item {display: grid; grid-template-rows: auto 8rem auto; gap: .45rem; min-width: 0; text-align: center;}

    .dth-column-value {min-height: 1rem; color: var(--dth-muted); font-size: .65rem; font-weight: 650;}

    .dth-column-track {display: flex; align-items: end; justify-content: center; overflow: hidden; border-radius: .55rem; background: var(--dth-surface-soft); border: 1px solid var(--dth-border);}

    .dth-column-fill {display: block; width: 58%; min-height: 2px; border-radius: .45rem .45rem 0 0;}

    .dth-column-label {color: var(--dth-muted); font-size: .68rem; white-space: nowrap;}

    .dth-list-row {display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: .8rem .9rem;
        border: 1px solid var(--dth-border);
        border-radius: var(--dth-radius-sm);
        background: transparent;}

    .dth-list-row:hover {background: var(--dth-surface-soft);}

    .dth-empty-state {padding: 2rem 1rem;
        text-align: center;
        color: var(--dth-muted);
        font-size: .82rem;
        border: 1px dashed var(--dth-border);
        border-radius: var(--dth-radius-sm);}

    .dth-report-link-card {display: flex;
        align-items: center;
        gap: .7rem;
        min-height: 3.25rem;
        padding: .8rem .9rem;
        border: 1px solid var(--dth-border);
        border-radius: var(--dth-radius-sm);
        color: rgb(55 65 81);
        font-size: .8rem;
        font-weight: 650;
        transition: background-color .12s ease, border-color .12s ease;}

    .dark .dth-report-link-card {color: rgb(229 231 235);}

    .dth-report-link-card span {flex: 1;}

    .dth-report-link-card:hover {background: var(--dth-surface-soft); border-color: rgba(var(--primary-500), .35);}

    .dth-analytics-shell {display: grid; gap: 1.25rem;}

    .dth-analytics-toolbar {display: flex; flex-direction: column; gap: .9rem; align-items: stretch; justify-content: space-between;
        padding: 1rem 1.1rem; border: 1px solid var(--dth-border); border-radius: var(--dth-radius-md); background: var(--dth-surface-soft);}

    @media (min-width: 768px) {
        .dth-analytics-toolbar {flex-direction: row; align-items: center;}
    }

    .dth-analytics-toolbar__title {font-weight: 750; letter-spacing: -.015em; color: rgb(17 24 39);}

    .dark .dth-analytics-toolbar__title {color: white;}

    .dth-analytics-toolbar__subtitle {margin-top: .15rem; color: var(--dth-muted); font-size: .78rem;}

    .dth-period-filter {display: flex; align-items: center; gap: .55rem; min-width: 14rem;}

    .dth-period-filter > span {white-space: nowrap; color: var(--dth-muted); font-size: .72rem; font-weight: 700;}

    .dth-analytics-kpi-grid {display: grid; grid-template-columns: repeat(1,minmax(0,1fr)); gap: .85rem;}

    @media (min-width: 640px) {
        .dth-analytics-kpi-grid {grid-template-columns: repeat(2,minmax(0,1fr));}
    }

    @media (min-width: 1280px) {
        .dth-analytics-kpi-grid {grid-template-columns: repeat(4,minmax(0,1fr));}
    }

    .dth-analytics-kpi {position: relative; overflow: hidden; min-height: 8.1rem; padding: 1rem 1.05rem;
        border: 1px solid var(--dth-border); border-radius: var(--dth-radius-md); background: rgba(255,255,255,.38);}

    .dark .dth-analytics-kpi {background: rgba(255,255,255,.018);}

    .dth-analytics-kpi::after {content:""; position:absolute; inset:auto -.5rem -.8rem auto; width:4.5rem; height:4.5rem; border-radius:999px; opacity:.08; background: currentColor;}

    .dth-analytics-kpi[data-tone="success"] {color: rgb(34 197 94);}

    .dth-analytics-kpi[data-tone="warning"] {color: rgb(245 158 11);}

    .dth-analytics-kpi[data-tone="danger"] {color: rgb(239 68 68);}

    .dth-analytics-kpi[data-tone="info"] {color: rgb(59 130 246);}

    .dth-analytics-kpi[data-tone="marketing"] {color: rgb(168 85 247);}

    .dth-analytics-kpi[data-tone="primary"] {color: rgb(var(--primary-500));}

    .dth-analytics-kpi__top {display:flex; align-items:center; justify-content:space-between; gap:.75rem;}

    .dth-analytics-kpi__label {color:var(--dth-muted); font-size:.74rem; font-weight:700;}

    .dth-analytics-kpi__icon {display:grid; place-items:center; width:2rem; height:2rem; border-radius:.65rem; color:white;}

    .dth-analytics-kpi[data-tone="success"] .dth-analytics-kpi__icon {background:rgb(34 197 94);}

    .dth-analytics-kpi[data-tone="warning"] .dth-analytics-kpi__icon {background:rgb(245 158 11);}

    .dth-analytics-kpi[data-tone="danger"] .dth-analytics-kpi__icon {background:rgb(239 68 68);}

    .dth-analytics-kpi[data-tone="info"] .dth-analytics-kpi__icon {background:rgb(59 130 246);}

    .dth-analytics-kpi[data-tone="marketing"] .dth-analytics-kpi__icon {background:rgb(168 85 247);}

    .dth-analytics-kpi[data-tone="primary"] .dth-analytics-kpi__icon {background:rgb(var(--primary-500));}

    .dth-analytics-kpi__icon svg {color:white;}

    .dth-analytics-kpi__value {margin-top:.65rem; color:rgb(17 24 39); font-size:1.55rem; font-weight:800; letter-spacing:-.035em; line-height:1.1;}

    .dark .dth-analytics-kpi__value {color:white;}

    .dth-analytics-kpi__footer {display:flex; align-items:center; gap:.35rem; margin-top:.7rem; color:var(--dth-muted); font-size:.68rem;}

    .dth-analytics-delta {font-weight:800;}

    .dth-analytics-delta[data-direction="up"] {color:rgb(34 197 94);}

    .dth-analytics-delta[data-direction="down"] {color:rgb(239 68 68);}

    .dth-analytics-delta[data-direction="flat"] {color:var(--dth-muted);}

    .dth-analytics-layout-2 {display:grid; grid-template-columns:1fr; gap:1rem;}

    @media (min-width: 1280px) {
        .dth-analytics-layout-2 {grid-template-columns:minmax(0,1.45fr) minmax(20rem,.85fr);}
    }

    .dth-analytics-layout-equal {display:grid; grid-template-columns:1fr; gap:1rem;}

    @media (min-width: 1100px) {
        .dth-analytics-layout-equal {grid-template-columns:repeat(2,minmax(0,1fr));}
    }

    .dth-line-chart {min-height: 15rem;}

    .dth-line-chart__svg {width:100%; height:auto; min-height:14rem; overflow:visible;}

    .dth-chart-grid-line {stroke: var(--dth-border); stroke-width:1; stroke-dasharray:4 5;}

    .dth-chart-axis-label,.dth-chart-axis-value {fill:var(--dth-muted); font-size:10px;}

    .dth-chart-axis-value {font-size:9px;}

    .dth-chart-line {fill:none; stroke-width:3; stroke-linecap:round; stroke-linejoin:round;}

    .dth-chart-line--current {stroke:rgb(245 158 11);}

    .dth-chart-line--previous {stroke:rgb(100 116 139); stroke-width:2; stroke-dasharray:7 6; opacity:.72;}

    .dth-chart-area {fill:rgba(245,158,11,.10); stroke:none;}

    .dth-chart-legend {display:flex; justify-content:flex-end; flex-wrap:wrap; gap:.85rem; margin-bottom:.35rem; color:var(--dth-muted); font-size:.68rem; font-weight:650;}

    .dth-chart-legend span {display:flex; align-items:center; gap:.35rem;}

    .dth-chart-dot {width:.55rem; height:.55rem; border-radius:999px; display:inline-block;}

    .dth-chart-dot--current {background:rgb(245 158 11);}

    .dth-chart-dot--previous {background:rgb(100 116 139);}

    .dth-analytics-bars {display:grid; gap:.9rem;}

    .dth-analytics-bar-row {display:grid; gap:.35rem;}

    .dth-analytics-bar-meta {display:flex; align-items:baseline; justify-content:space-between; gap:1rem; font-size:.76rem;}

    .dth-analytics-bar-meta span {min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:rgb(55 65 81); font-weight:650;}

    .dark .dth-analytics-bar-meta span {color:rgb(229 231 235);}

    .dth-analytics-bar-meta strong {color:var(--dth-muted); font-size:.7rem; white-space:nowrap;}

    .dth-analytics-bar-track {height:.52rem; overflow:hidden; border-radius:999px; background:var(--dth-surface-soft); box-shadow:inset 0 0 0 1px var(--dth-border);}

    .dth-analytics-bar-track span {display:block; height:100%; min-width:3px; border-radius:inherit; background:rgb(var(--primary-500));}

    .dth-analytics-bars[data-tone="marketing"] .dth-analytics-bar-track span {background:rgb(168 85 247);}

    .dth-analytics-bars[data-tone="finance"] .dth-analytics-bar-track span {background:rgb(34 197 94);}

    .dth-analytics-bars[data-tone="sales"] .dth-analytics-bar-track span {background:rgb(245 158 11);}

    .dth-analytics-bars[data-tone="info"] .dth-analytics-bar-track span {background:rgb(59 130 246);}

    .dth-analytics-bar-subtitle {color:var(--dth-muted); font-size:.65rem;}

    .dth-donut-layout {display:grid; grid-template-columns:1fr; gap:1.2rem; align-items:center;}

    @media (min-width: 640px) {
        .dth-donut-layout {grid-template-columns:11rem 1fr;}
    }

    .dth-donut {width:10.5rem; aspect-ratio:1; margin:auto; border-radius:999px; display:grid; place-items:center; box-shadow:inset 0 0 0 1px rgba(255,255,255,.08);}

    .dth-donut__hole {width:62%; aspect-ratio:1; display:flex; flex-direction:column; align-items:center; justify-content:center; border-radius:999px; background:white; box-shadow:0 0 0 1px var(--dth-border);}

    .dark .dth-donut__hole {background:rgb(24 24 27);}

    .dth-donut__hole strong {color:rgb(17 24 39); font-size:1rem; font-weight:800;}

    .dark .dth-donut__hole strong {color:white;}

    .dth-donut__hole span {margin-top:.1rem; color:var(--dth-muted); font-size:.62rem;}

    .dth-donut-legend {display:grid; gap:.5rem;}

    .dth-donut-legend__row {display:grid; grid-template-columns:auto minmax(0,1fr) auto; align-items:center; gap:.55rem; font-size:.72rem;}

    .dth-donut-legend__row i {width:.55rem; height:.55rem; border-radius:999px;}

    .dth-donut-legend__label {overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:rgb(55 65 81); font-weight:650;}

    .dark .dth-donut-legend__label {color:rgb(229 231 235);}

    .dth-donut-legend__row strong {color:var(--dth-muted); font-size:.68rem;}

    .dth-funnel {display:flex; flex-direction:column; align-items:center; gap:.42rem; padding:.3rem 0;}

    .dth-funnel__step {display:flex; justify-content:space-between; gap:.75rem; min-width:10rem; padding:.62rem .8rem; border:1px solid rgba(245,158,11,.24); border-radius:.55rem; background:rgba(245,158,11,.08); transition:width .2s ease;}

    .dth-funnel__step span {overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:rgb(55 65 81); font-size:.72rem; font-weight:650;}

    .dark .dth-funnel__step span {color:rgb(229 231 235);}

    .dth-funnel__step strong {font-size:.72rem; color:rgb(245 158 11);}

    .dth-insight-grid {display:grid; grid-template-columns:1fr; gap:.75rem;}

    @media (min-width: 768px) {
        .dth-insight-grid {grid-template-columns:repeat(2,minmax(0,1fr));}
    }

    .dth-insight-card {display:flex; gap:.8rem; padding:.9rem 1rem; border:1px solid var(--dth-border); border-radius:var(--dth-radius-sm); background:var(--dth-surface-soft);}

    .dth-insight-card__icon {flex:0 0 auto; display:grid; place-items:center; width:2rem; height:2rem; border-radius:.6rem; background:rgba(245,158,11,.12); color:rgb(245 158 11);}

    .dth-insight-card strong {display:block; color:rgb(17 24 39); font-size:.78rem;}

    .dark .dth-insight-card strong {color:white;}

    .dth-insight-card p {margin-top:.2rem; color:var(--dth-muted); font-size:.72rem; line-height:1.5;}

    .dth-insight-card[data-tone="success"] .dth-insight-card__icon {background:rgba(34,197,94,.12); color:rgb(34 197 94);}

    .dth-insight-card[data-tone="warning"] .dth-insight-card__icon {background:rgba(245,158,11,.12); color:rgb(245 158 11);}

    .dth-insight-card[data-tone="info"] .dth-insight-card__icon {background:rgba(59,130,246,.12); color:rgb(59 130 246);}

    .dth-insight-card[data-tone="primary"] .dth-insight-card__icon {background:rgba(var(--primary-500),.12); color:rgb(var(--primary-500));}

    .dth-insight-card[data-tone="marketing"] .dth-insight-card__icon {background:rgba(168,85,247,.12); color:rgb(168 85 247);}

    .dth-analytics-table {width:100%; border-collapse:separate; border-spacing:0; font-size:.76rem;}

    .dth-analytics-table th {padding:.68rem .7rem; color:var(--dth-muted); font-size:.66rem; font-weight:750; text-align:left; text-transform:uppercase; letter-spacing:.025em; border-bottom:1px solid var(--dth-border);}

    .dth-analytics-table td {padding:.75rem .7rem; border-bottom:1px solid var(--dth-border); vertical-align:middle;}

    .dth-analytics-table tbody tr:last-child td {border-bottom:0;}

    .dth-analytics-table tbody tr:hover {background:var(--dth-surface-soft);}

    .dth-analytics-table .numeric {text-align:right; font-variant-numeric:tabular-nums;}

    .dth-analytics-index {display:flex; align-items:center; gap:.5rem; min-width:8rem;}

    .dth-analytics-index__track {flex:1; height:.42rem; background:var(--dth-surface-soft); border-radius:999px; box-shadow:inset 0 0 0 1px var(--dth-border); overflow:hidden;}

    .dth-analytics-index__track span {display:block; height:100%; border-radius:inherit; background:linear-gradient(90deg,rgb(59 130 246),rgb(34 197 94));}

    .dth-analytics-index strong {width:2.5rem; text-align:right; font-size:.68rem;}

    .dth-campaign-card-grid {display:grid; grid-template-columns:1fr; gap:.8rem;}

    @media (min-width: 900px) {
        .dth-campaign-card-grid {grid-template-columns:repeat(2,minmax(0,1fr));}
    }

    .dth-campaign-card {padding:.9rem 1rem; border:1px solid var(--dth-border); border-radius:var(--dth-radius-sm); background:transparent;}

    .dth-campaign-card__head {display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem;}

    .dth-campaign-card__head strong {color:rgb(17 24 39); font-size:.78rem; line-height:1.4;}

    .dark .dth-campaign-card__head strong {color:white;}

    .dth-campaign-card__metrics {display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:.5rem; margin-top:.75rem;}

    .dth-campaign-card__metrics div {padding:.55rem .6rem; border-radius:.5rem; background:var(--dth-surface-soft);}

    .dth-campaign-card__metrics span {display:block; color:var(--dth-muted); font-size:.6rem;}

    .dth-campaign-card__metrics strong {display:block; margin-top:.15rem; color:rgb(17 24 39); font-size:.75rem;}

    .dark .dth-campaign-card__metrics strong {color:white;}

    .dth-config-shell {display:grid; gap:1rem;}

    .dth-config-toolbar {display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;
        padding:.15rem .1rem .35rem;}

    .dth-config-toolbar > p {margin:0; max-width:58rem; color:var(--dth-muted); font-size:.8rem; line-height:1.45;}

    .dth-config-toolbar__status {display:inline-flex; align-items:center; gap:.4rem; min-height:2rem; padding:.35rem .65rem;
        border:1px solid var(--dth-border); border-radius:999px; font-size:.7rem; font-weight:720; white-space:nowrap;}

    .dth-config-toolbar__status--success {color:rgb(22 163 74); background:rgba(34,197,94,.08); border-color:rgba(34,197,94,.24);}

    .dth-config-toolbar__status--warning {color:rgb(217 119 6); background:rgba(245,158,11,.08); border-color:rgba(245,158,11,.24);}

    .dth-config-launch-grid {display:grid; grid-template-columns:1fr; gap:.75rem;}

    @media (min-width: 900px) {
        .dth-config-launch-grid--2 {grid-template-columns:repeat(2,minmax(0,1fr));}

        .dth-config-launch-grid--3 {grid-template-columns:repeat(3,minmax(0,1fr));}
    }

    .dth-config-launch-card {display:grid; grid-template-columns:auto minmax(0,1fr) auto; align-items:center; gap:.8rem;
        min-height:5rem; padding:.9rem 1rem; border:1px solid var(--dth-border); border-radius:var(--dth-radius-md);
        background:rgba(255,255,255,.34); color:inherit; text-decoration:none;
        transition:border-color .12s ease, background-color .12s ease, box-shadow .12s ease, transform .12s ease;}

    .dark .dth-config-launch-card {background:rgba(255,255,255,.02);}

    .dth-config-launch-card:hover {border-color:rgba(var(--primary-500),.38); background:var(--dth-surface-soft);
        box-shadow:0 8px 22px rgba(15,23,42,.05); transform:translateY(-1px);}

    .dark .dth-config-launch-card:hover {box-shadow:0 10px 24px rgba(0,0,0,.16);}

    .dth-config-launch-card__icon {display:grid; place-items:center; width:2.35rem; height:2.35rem; border-radius:.7rem;
        background:rgba(var(--primary-500),.10); color:rgb(var(--primary-600));}

    .dark .dth-config-launch-card__icon {color:rgb(var(--primary-400));}

    .dth-config-launch-card__content {display:grid; gap:.18rem; min-width:0;}

    .dth-config-launch-card__content strong {color:rgb(17 24 39); font-size:.82rem; font-weight:760;}

    .dark .dth-config-launch-card__content strong {color:white;}

    .dth-config-launch-card__content span {overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:var(--dth-muted); font-size:.7rem;}

    .dth-config-launch-card__meta {display:flex; align-items:center; gap:.45rem; color:var(--dth-muted);}

    .dth-config-launch-card__meta b {display:grid; place-items:center; min-width:1.85rem; height:1.85rem; padding:0 .45rem;
        border:1px solid var(--dth-border); border-radius:999px; font-size:.68rem; font-variant-numeric:tabular-nums;}

    .dth-config-attention-panel {display:grid; grid-template-columns:1fr; gap:.45rem; padding:.75rem .85rem;
        border:1px solid rgba(245,158,11,.22); border-radius:var(--dth-radius-sm); background:rgba(245,158,11,.045);}

    @media (min-width: 900px) {
        .dth-config-attention-panel {grid-template-columns:repeat(2,minmax(0,1fr));}
    }

    .dth-config-attention-panel__item {display:flex; align-items:flex-start; gap:.45rem; color:var(--dth-muted); font-size:.72rem; line-height:1.4;}

    .dth-config-attention-panel__item svg {flex:0 0 auto; margin-top:.05rem; color:rgb(245 158 11);}

    .dth-config-palette-panel {padding:.85rem 1rem; border:1px solid var(--dth-border); border-radius:var(--dth-radius-md); background:rgba(255,255,255,.34);}

    .dark .dth-config-palette-panel {background:rgba(255,255,255,.02);}

    .dth-config-palette-panel__head {display:flex; align-items:center; justify-content:space-between; gap:1rem; margin-bottom:.7rem;}

    .dth-config-palette-panel__head strong {color:rgb(17 24 39); font-size:.78rem;}

    .dark .dth-config-palette-panel__head strong {color:white;}

    .dth-config-palette-panel__head span {display:grid; place-items:center; min-width:1.75rem; height:1.75rem; border:1px solid var(--dth-border); border-radius:999px;
        color:var(--dth-muted); font-size:.65rem; font-weight:750;}

    .dth-config-palette-strip {display:flex; flex-wrap:wrap; gap:.45rem;}

    .dth-config-palette-chip {display:inline-flex; align-items:center; gap:.38rem; min-height:1.95rem; padding:.32rem .55rem;
        border:1px solid var(--dth-border); border-radius:999px; color:var(--dth-muted); font-size:.66rem; background:var(--dth-surface-soft);}

    .dth-color-dot {width:.65rem; height:.65rem; border-radius:999px; flex:0 0 auto; box-shadow:0 0 0 1px rgba(15,23,42,.08);}

    .dth-color-dot--gray {background:#6b7280;}

    .dth-color-dot--primary, .dth-color-dot--amber {background:#f59e0b;}

    .dth-color-dot--info, .dth-color-dot--sky {background:#0ea5e9;}

    .dth-color-dot--success, .dth-color-dot--emerald {background:#10b981;}

    .dth-color-dot--warning {background:#f59e0b;}

    .dth-color-dot--danger {background:#ef4444;}

    .dth-color-dot--orange {background:#f97316;}

    .dth-color-dot--yellow {background:#eab308;}

    .dth-color-dot--lime {background:#84cc16;}

    .dth-color-dot--green {background:#22c55e;}

    .dth-color-dot--teal {background:#14b8a6;}

    .dth-color-dot--cyan {background:#06b6d4;}

    .dth-color-dot--blue {background:#3b82f6;}

    .dth-color-dot--indigo {background:#6366f1;}

    .dth-color-dot--violet {background:#8b5cf6;}

    .dth-color-dot--purple {background:#a855f7;}

    .dth-color-dot--fuchsia {background:#d946ef;}

    .dth-color-dot--pink {background:#ec4899;}

    .dth-color-dot--rose {background:#f43f5e;}

    .dth-config-matrix {width:100%; border-collapse:separate; border-spacing:0; font-size:.76rem;}

    .dth-config-matrix th {padding:.65rem .7rem; border-bottom:1px solid var(--dth-border); color:var(--dth-muted);
        font-size:.64rem; font-weight:760; text-align:left; text-transform:uppercase; letter-spacing:.025em;}

    .dth-config-matrix td {padding:.72rem .7rem; border-bottom:1px solid var(--dth-border); vertical-align:middle; color:rgb(55 65 81);}

    .dark .dth-config-matrix td {color:rgb(229 231 235);}

    .dth-config-matrix tbody tr:last-child td {border-bottom:0;}

    .dth-config-matrix tbody tr:hover {background:var(--dth-surface-soft);}

    .dth-config-color-orbit {display:flex;
        align-items:center;
        flex-wrap:wrap;
        gap:.65rem;}

    .dth-config-color-orbit__swatch {width:1.85rem;
        height:1.85rem;
        border-radius:999px;
        background:var(--swatch-color);
        border:2px solid rgba(148,163,184,.24);
        box-shadow:inset 0 0 0 2px rgba(255,255,255,.72), 0 1px 2px rgba(15,23,42,.10);
        transition:transform .12s ease, box-shadow .12s ease;
        cursor:help;}

    .dark .dth-config-color-orbit__swatch {box-shadow:inset 0 0 0 2px rgba(24,24,27,.84), 0 1px 2px rgba(0,0,0,.25);}

    .dth-config-color-orbit__swatch:hover {transform:translateY(-1px) scale(1.08);
        box-shadow:0 0 0 3px rgba(var(--primary-500),.16), inset 0 0 0 2px rgba(255,255,255,.82);}
</style>
