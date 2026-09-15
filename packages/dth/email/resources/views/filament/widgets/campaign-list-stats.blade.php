<x-filament-widgets::widget class="dth-email-campaign-stats-widget">
    <div class="dth-email-campaign-stats">
        @foreach ($stats as $stat)
            <div class="dth-email-campaign-stat" data-tone="{{ $stat['tone'] }}">
                <div class="dth-email-campaign-stat__icon">
                    <x-filament::icon :icon="$stat['icon']" />
                </div>

                <div class="dth-email-campaign-stat__body">
                    <div class="dth-email-campaign-stat__label">{{ $stat['label'] }}</div>
                    <div class="dth-email-campaign-stat__value">{{ $stat['formattedValue'] }}</div>
                    <div class="dth-email-campaign-stat__delta" data-direction="{{ $stat['delta'] < 0 ? 'down' : 'up' }}">
                        <x-filament::icon :icon="$stat['delta'] < 0 ? 'heroicon-m-arrow-down' : 'heroicon-m-arrow-up'" />
                        <span>{{ $stat['deltaLabel'] }}</span>
                    </div>
                    <div class="dth-email-campaign-stat__hint">
                        {{ \Dth\Email\Support\UiText::get('campaign.list.stats.compared_previous', 'so với kỳ trước') }}
                    </div>
                </div>

                <svg class="dth-email-campaign-stat__spark" viewBox="0 0 88 28" preserveAspectRatio="none" aria-hidden="true">
                    <polygon points="0,28 {{ $stat['sparkline'] }} 88,28" />
                    <polyline points="{{ $stat['sparkline'] }}" fill="none" vector-effect="non-scaling-stroke" />
                </svg>
            </div>
        @endforeach
    </div>

    <style>
        body:has(.dth-email-campaign-stats) .fi-main {
            background:
                radial-gradient(circle at 82% 4%, rgba(59, 130, 246, .035), transparent 25rem),
                linear-gradient(180deg, #f8fbff 0%, #f5f8fc 100%);
        }

        body:has(.dth-email-campaign-stats) .fi-page {
            gap: 1rem;
        }

        body:has(.dth-email-campaign-stats) .fi-header {
            align-items: center;
            margin-bottom: .15rem;
        }

        .dth-campaign-page-title {
            display: inline-flex;
            align-items: center;
            gap: .85rem;
        }

        .dth-campaign-page-title__icon {
            width: 3.35rem;
            height: 3.35rem;
            flex: 0 0 3.35rem;
            display: grid;
            place-items: center;
            color: #f59e0b;
            border: 1px solid #f7e8c4;
            border-radius: .9rem;
            background: #fff9eb;
            box-shadow: 0 8px 22px rgba(245, 158, 11, .08);
        }

        .dth-campaign-page-title__icon svg {
            width: 1.55rem;
            height: 1.55rem;
        }

        body:has(.dth-email-campaign-stats) .fi-header-heading {
            font-size: 1.8rem;
            line-height: 1.12;
            font-weight: 800;
            letter-spacing: -.035em;
            color: #111827;
        }

        body:has(.dth-email-campaign-stats) .fi-header-subheading {
            margin-top: .32rem;
            color: #7b879c;
            font-size: .9rem;
        }

        body:has(.dth-email-campaign-stats) .fi-header-actions .fi-btn {
            min-height: 2.65rem;
            border-radius: .72rem;
            padding-inline: 1.15rem;
            font-weight: 700;
            box-shadow: 0 8px 20px rgba(245, 158, 11, .16);
        }

        body:has(.dth-email-campaign-stats) .fi-wi.dth-email-campaign-stats-widget {
            margin: 0;
        }

        body:has(.dth-email-campaign-stats) .fi-wi.dth-email-campaign-stats-widget > div {
            padding: 0;
            border: 0;
            background: transparent;
            box-shadow: none;
        }

        .dth-email-campaign-stats {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: .75rem;
            width: 100%;
        }

        .dth-email-campaign-stat {
            --tone: 59, 130, 246;
            position: relative;
            min-height: 7rem;
            overflow: hidden;
            display: grid;
            grid-template-columns: 2.75rem minmax(0, 1fr);
            align-items: start;
            gap: .82rem;
            padding: .95rem .9rem .85rem;
            border: 1px solid #e8edf4;
            border-radius: .78rem;
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 7px 22px rgba(15, 23, 42, .045);
        }

        .dth-email-campaign-stat[data-tone="amber"] { --tone: 245, 158, 11; }
        .dth-email-campaign-stat[data-tone="blue"] { --tone: 59, 130, 246; }
        .dth-email-campaign-stat[data-tone="violet"] { --tone: 124, 58, 237; }
        .dth-email-campaign-stat[data-tone="green"] { --tone: 34, 197, 94; }

        .dth-email-campaign-stat__icon {
            width: 2.75rem;
            height: 2.75rem;
            display: grid;
            place-items: center;
            color: rgb(var(--tone));
            border-radius: .82rem;
            background: rgba(var(--tone), .105);
        }

        .dth-email-campaign-stat__icon svg {
            width: 1.48rem;
            height: 1.48rem;
        }

        .dth-email-campaign-stat__body {
            min-width: 0;
        }

        .dth-email-campaign-stat__label {
            color: #6d7a91;
            font-size: .78rem;
            font-weight: 600;
            line-height: 1.25;
        }

        .dth-email-campaign-stat__value {
            margin-top: .18rem;
            color: #111827;
            font-size: 1.45rem;
            line-height: 1.15;
            font-weight: 800;
            letter-spacing: -.03em;
        }

        .dth-email-campaign-stat__delta {
            display: inline-flex;
            align-items: center;
            gap: .1rem;
            margin-top: .28rem;
            color: #16a34a;
            font-size: .72rem;
            font-weight: 800;
        }

        .dth-email-campaign-stat__delta[data-direction="down"] {
            color: #ef4444;
        }

        .dth-email-campaign-stat__delta svg {
            width: .7rem;
            height: .7rem;
        }

        .dth-email-campaign-stat__hint {
            color: #9aa5b7;
            font-size: .66rem;
            margin-top: .02rem;
        }

        .dth-email-campaign-stat__spark {
            position: absolute;
            right: .65rem;
            bottom: .78rem;
            width: 4rem;
            height: 1.45rem;
            color: rgb(var(--tone));
            overflow: visible;
        }

        .dth-email-campaign-stat__spark polyline {
            stroke: currentColor;
            stroke-width: 1.55;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .dth-email-campaign-stat__spark polygon {
            fill: rgba(var(--tone), .08);
        }

        /* Status tabs */
        body:has(.dth-email-campaign-stats) [role="tablist"] {
            gap: .5rem;
            padding: 0;
            border: 0;
            background: transparent;
            box-shadow: none;
        }

        body:has(.dth-email-campaign-stats) [role="tab"] {
            min-height: 2.05rem;
            padding: .42rem .78rem;
            border: 1px solid transparent;
            border-radius: .72rem;
            color: #667085;
            background: #f4f6fa;
            font-size: .78rem;
            font-weight: 700;
        }

        body:has(.dth-email-campaign-stats) [role="tab"][aria-selected="true"] {
            color: #ea7800;
            border-color: #f59e0b;
            background: #fffaf0;
            box-shadow: none;
        }

        body:has(.dth-email-campaign-stats) [role="tab"] .fi-badge {
            min-width: auto;
            padding: 0;
            background: transparent;
            color: inherit;
            box-shadow: none;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content .fi-fo-toggle-buttons {
            gap: .48rem;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content .fi-fo-toggle-buttons button {
            min-height: 2rem;
            padding: .42rem .8rem;
            border: 1px solid transparent;
            border-radius: .72rem;
            background: #f4f6fa;
            color: #667085;
            font-size: .72rem;
            font-weight: 750;
            box-shadow: none;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content .fi-fo-toggle-buttons button:nth-child(2) {
            background: #f3f4f6; color: #667085;
        }
        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content .fi-fo-toggle-buttons button:nth-child(3) {
            background: #f3efff; color: #6941c6;
        }
        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content .fi-fo-toggle-buttons button:nth-child(4) {
            background: #ecfdf3; color: #039855;
        }
        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content .fi-fo-toggle-buttons button:nth-child(5) {
            background: #eafaf1; color: #039855;
        }
        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content .fi-fo-toggle-buttons button:nth-child(6) {
            background: #fff0f1; color: #d92d20;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content .fi-fo-toggle-buttons button[aria-pressed="true"],
        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content .fi-fo-toggle-buttons button[data-state="on"] {
            color: #ea7800;
            border-color: #f59e0b;
            background: #fffaf0;
        }

        /* Table shell */
        body:has(.dth-email-campaign-stats) .fi-ta {
            overflow: hidden;
            border: 1px solid #e7edf5;
            border-radius: .85rem;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .045);
        }

        body:has(.dth-email-campaign-stats) .fi-ta-header,
        body:has(.dth-email-campaign-stats) .fi-ta-header-toolbar {
            background: #fff;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-header-toolbar {
            padding: .8rem .9rem .7rem;
            gap: .7rem;
            align-items: center;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-search-field {
            width: min(34rem, 100%);
            flex: 0 1 34rem;
        }

        /* Keep Filament behavior, but make every toolbar control read like the mockup. */
        body:has(.dth-email-campaign-stats) .fi-ta-search-field .fi-input-wrp,
        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content .fi-input-wrp,
        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content .fi-select-input,
        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content select {
            min-height: 2.48rem !important;
            border: 1px solid #d8e1ec !important;
            border-radius: .62rem !important;
            background: #fff !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .025) !important;
            outline: none !important;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-search-field .fi-input-wrp:hover,
        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content .fi-input-wrp:hover {
            border-color: #c5d0df !important;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-search-field .fi-input-wrp:focus-within,
        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content .fi-input-wrp:focus-within {
            border-color: #f3b43f !important;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, .10) !important;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-search-field input,
        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content input,
        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content select {
            min-height: 2.42rem;
            font-size: .78rem;
            color: #344054;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-search-field svg,
        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content .fi-input-wrp svg {
            color: #8090a6;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content {
            padding: .68rem .9rem .78rem;
            border-top: 1px solid #edf1f6;
            border-bottom: 1px solid #edf1f6;
            background: #fff;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content .fi-sc-grid {
            gap: .68rem !important;
            align-items: end;
        }

        /* The design uses compact controls without labels above the fields. */
        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content .fi-fo-field-wrp-label,
        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content .fi-fo-field-label,
        body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content .fi-fo-field-label-content {
            display: none !important;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-filter-trigger .fi-btn,
        body:has(.dth-email-campaign-stats) .fi-ta-filter-trigger button {
            min-height: 2.48rem;
            border: 1px solid #d8e1ec !important;
            border-radius: .62rem !important;
            background: #fff !important;
            box-shadow: none !important;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-table thead {
            background: #fbfcfe;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-header-cell {
            padding-block: .7rem;
            border-bottom: 1px solid #e9eef5;
            color: #334155;
            font-size: .72rem;
            font-weight: 800;
            white-space: nowrap;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-row {
            transition: background .15s ease;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-row:hover {
            background: #fbfdff;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-cell {
            padding-block: .6rem;
            border-bottom-color: #edf1f5;
            vertical-align: middle;
            color: #334155;
            font-size: .77rem;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-text-item-label {
            line-height: 1.3;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-text-item-description {
            margin-top: .12rem;
            color: #98a2b3;
            font-size: .68rem;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-record-action-group-btn {
            border-radius: .55rem;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-footer {
            padding: .72rem .9rem;
            background: #fff;
        }

        /* Pagination: boxed page selector and boxed page numbers like the reference UI. */
        body:has(.dth-email-campaign-stats) .fi-ta-footer .fi-pagination {
            gap: .55rem;
            align-items: center;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-footer .fi-pagination select,
        body:has(.dth-email-campaign-stats) .fi-ta-footer .fi-pagination .fi-select-input,
        body:has(.dth-email-campaign-stats) .fi-ta-footer .fi-pagination .fi-input-wrp {
            min-height: 2.35rem !important;
            border: 1px solid #d8e1ec !important;
            border-radius: .58rem !important;
            background: #fff !important;
            box-shadow: none !important;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-footer .fi-pagination :is(button, a) {
            min-width: 2.35rem;
            min-height: 2.35rem;
            padding: 0 .62rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #d8e1ec !important;
            border-radius: .58rem !important;
            background: #fff !important;
            color: #667085 !important;
            box-shadow: none !important;
            font-size: .76rem;
            font-weight: 700;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-footer .fi-pagination :is(button, a):hover:not([disabled]) {
            border-color: #f3b43f !important;
            color: #e77b00 !important;
            background: #fffaf0 !important;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-footer .fi-pagination [aria-current="page"],
        body:has(.dth-email-campaign-stats) .fi-ta-footer .fi-pagination .fi-active {
            border-color: #f59e0b !important;
            background: #f59e0b !important;
            color: #fff !important;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-footer .fi-pagination :is(button, a)[disabled] {
            opacity: .45;
            cursor: not-allowed;
        }

        body:has(.dth-email-campaign-stats) .fi-ta-footer .fi-pagination svg {
            width: .9rem;
            height: .9rem;
        }

        @media (max-width: 1180px) {
            .dth-email-campaign-stats {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 780px) {
            .dth-email-campaign-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 520px) {
            .dth-email-campaign-stats {
                grid-template-columns: 1fr;
            }
        }

        .dark .dth-email-campaign-stat,
        .dark body:has(.dth-email-campaign-stats) .fi-ta,
        .dark body:has(.dth-email-campaign-stats) .fi-ta-header,
        .dark body:has(.dth-email-campaign-stats) .fi-ta-header-toolbar,
        .dark body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content,
        .dark body:has(.dth-email-campaign-stats) .fi-ta-footer {
            border-color: #273244;
            background: #111827;
        }

        .dark .dth-email-campaign-stat__value,
        .dark body:has(.dth-email-campaign-stats) .fi-header-heading {
            color: #f8fafc;
        }


        .dark body:has(.dth-email-campaign-stats) .fi-ta-search-field .fi-input-wrp,
        .dark body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content .fi-input-wrp,
        .dark body:has(.dth-email-campaign-stats) .fi-ta-filters-above-content .fi-select-input,
        .dark body:has(.dth-email-campaign-stats) .fi-ta-footer .fi-pagination .fi-input-wrp,
        .dark body:has(.dth-email-campaign-stats) .fi-ta-footer .fi-pagination :is(button, a) {
            border-color: #344054 !important;
            background: #111827 !important;
            color: #d0d5dd !important;
        }
    </style>
</x-filament-widgets::widget>
