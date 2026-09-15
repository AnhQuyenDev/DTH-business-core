<x-filament-widgets::widget class="dth-email-template-stats-widget">
    <div class="dth-email-template-stats">
        @foreach ($stats as $stat)
            <div class="dth-email-template-stat" data-tone="{{ $stat['tone'] }}">
                <div class="dth-email-template-stat__icon">
                    <x-filament::icon :icon="$stat['icon']" />
                </div>

                <div class="dth-email-template-stat__body">
                    <div class="dth-email-template-stat__label">{{ $stat['label'] }}</div>
                    <div class="dth-email-template-stat__value">{{ $stat['formattedValue'] }}</div>
                    <div class="dth-email-template-stat__delta" data-direction="{{ $stat['delta'] < 0 ? 'down' : 'up' }}">
                        <x-filament::icon :icon="$stat['delta'] < 0 ? 'heroicon-m-arrow-down' : 'heroicon-m-arrow-up'" />
                        <span>{{ $stat['deltaLabel'] }}</span>
                    </div>
                    <div class="dth-email-template-stat__hint">
                        {{ trim(\Dth\Email\Support\UiText::get('dashboard.delta.compared_previous', ':change compared with previous period', ['change' => ''])) }}
                    </div>
                </div>

                <svg class="dth-email-template-stat__spark" viewBox="0 0 88 28" preserveAspectRatio="none" aria-hidden="true">
                    <polygon points="0,28 {{ $stat['sparkline'] }} 88,28" />
                    <polyline points="{{ $stat['sparkline'] }}" fill="none" vector-effect="non-scaling-stroke" />
                </svg>
            </div>
        @endforeach
    </div>

    <style>
        body:has(.dth-email-template-stats) .fi-main {
            background:
                radial-gradient(circle at 82% 4%, rgba(96, 99, 255, .035), transparent 25rem),
                linear-gradient(180deg, #f8fbff 0%, #f5f8fc 100%);
        }

        body:has(.dth-email-template-stats) .fi-page {
            gap: 1rem;
        }

        body:has(.dth-email-template-stats) .fi-header {
            align-items: center;
            margin-bottom: .15rem;
        }

        .dth-template-page-title {
            display: inline-flex;
            align-items: center;
            gap: .85rem;
        }

        .dth-template-page-title__icon {
            width: 3.35rem;
            height: 3.35rem;
            display: grid;
            place-items: center;
            color: #5b54ff;
            border: 1px solid #dfe2ff;
            border-radius: .9rem;
            background: #f3f3ff;
            box-shadow: 0 8px 22px rgba(91, 84, 255, .08);
        }

        .dth-template-page-title__icon svg {
            width: 1.55rem;
            height: 1.55rem;
        }

        body:has(.dth-email-template-stats) .fi-header-heading {
            font-size: 1.8rem;
            line-height: 1.12;
            font-weight: 800;
            letter-spacing: -.035em;
            color: #111827;
        }

        body:has(.dth-email-template-stats) .fi-header-subheading {
            margin-top: .32rem;
            color: #7b879c;
            font-size: .9rem;
        }

        body:has(.dth-email-template-stats) .fi-header-actions .fi-btn {
            min-height: 2.65rem;
            border-radius: .72rem;
            padding-inline: 1.15rem;
            font-weight: 700;
            box-shadow: 0 8px 20px rgba(91, 84, 255, .12);
        }

        body:has(.dth-email-template-stats) .fi-wi.dth-email-template-stats-widget {
            margin: 0;
        }

        body:has(.dth-email-template-stats) .fi-wi.dth-email-template-stats-widget > div {
            padding: 0;
            border: 0;
            background: transparent;
            box-shadow: none;
        }

        .dth-email-template-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem;
            width: 100%;
        }

        .dth-email-template-stat {
            --tone: 91, 84, 255;
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

        .dth-email-template-stat[data-tone="amber"] { --tone: 245, 158, 11; }
        .dth-email-template-stat[data-tone="blue"] { --tone: 59, 130, 246; }
        .dth-email-template-stat[data-tone="green"] { --tone: 34, 197, 94; }
        .dth-email-template-stat[data-tone="violet"] { --tone: 124, 58, 237; }

        .dth-email-template-stat__icon {
            width: 2.75rem;
            height: 2.75rem;
            display: grid;
            place-items: center;
            color: rgb(var(--tone));
            border-radius: .82rem;
            background: rgba(var(--tone), .105);
        }

        .dth-email-template-stat__icon svg {
            width: 1.48rem;
            height: 1.48rem;
        }

        .dth-email-template-stat__body {
            min-width: 0;
        }

        .dth-email-template-stat__label {
            color: #6d7a91;
            font-size: .78rem;
            font-weight: 600;
            line-height: 1.25;
        }

        .dth-email-template-stat__value {
            margin-top: .18rem;
            color: #111827;
            font-size: 1.45rem;
            line-height: 1.15;
            font-weight: 800;
            letter-spacing: -.03em;
        }

        .dth-email-template-stat__delta {
            display: inline-flex;
            align-items: center;
            gap: .1rem;
            margin-top: .28rem;
            color: #16a34a;
            font-size: .72rem;
            font-weight: 800;
        }

        .dth-email-template-stat__delta[data-direction="down"] {
            color: #ef4444;
        }

        .dth-email-template-stat__delta svg {
            width: .7rem;
            height: .7rem;
        }

        .dth-email-template-stat__hint {
            color: #9aa5b7;
            font-size: .66rem;
            margin-top: .02rem;
        }

        .dth-email-template-stat__spark {
            position: absolute;
            right: .65rem;
            bottom: .78rem;
            width: 4rem;
            height: 1.45rem;
            color: rgb(var(--tone));
            overflow: visible;
        }

        .dth-email-template-stat__spark polyline {
            stroke: currentColor;
            stroke-width: 1.55;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .dth-email-template-stat__spark polygon {
            fill: rgba(var(--tone), .08);
        }

        body:has(.dth-email-template-stats) [role="tablist"] {
            gap: .5rem;
            padding: 0;
            border: 0;
            background: transparent;
            box-shadow: none;
        }

        body:has(.dth-email-template-stats) [role="tab"] {
            min-height: 2.05rem;
            padding: .42rem .78rem;
            border: 1px solid transparent;
            border-radius: .72rem;
            color: #667085;
            background: #f4f6fa;
            font-size: .78rem;
            font-weight: 700;
        }

        body:has(.dth-email-template-stats) [role="tab"][aria-selected="true"] {
            color: #4f46e5;
            border-color: #a5b4fc;
            background: #f4f3ff;
            box-shadow: none;
        }

        body:has(.dth-email-template-stats) .fi-ta {
            overflow: hidden;
            border: 1px solid #e7edf5;
            border-radius: .85rem;
            background: #fff;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .045);
        }

        body:has(.dth-email-template-stats) .fi-ta-header,
        body:has(.dth-email-template-stats) .fi-ta-header-toolbar {
            background: #fff;
        }

        body:has(.dth-email-template-stats) .fi-ta-header-toolbar {
            padding: .8rem .9rem .7rem;
            gap: .7rem;
            align-items: center;
        }

        body:has(.dth-email-template-stats) .fi-ta-search-field {
            width: min(34rem, 100%);
            flex: 0 1 34rem;
        }

        body:has(.dth-email-template-stats) .fi-ta-search-field .fi-input-wrp,
        body:has(.dth-email-template-stats) .fi-ta-filters-above-content .fi-input-wrp,
        body:has(.dth-email-template-stats) .fi-ta-filters-above-content .fi-select-input,
        body:has(.dth-email-template-stats) .fi-ta-filters-above-content select {
            min-height: 2.48rem !important;
            border: 1px solid #d8e1ec !important;
            border-radius: .62rem !important;
            background: #fff !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, .025) !important;
        }

        body:has(.dth-email-template-stats) .fi-ta-table thead {
            background: #fbfcfe;
        }

        body:has(.dth-email-template-stats) .fi-ta-header-cell {
            padding-block: .7rem;
            border-bottom: 1px solid #e9eef5;
            color: #334155;
            font-size: .72rem;
            font-weight: 800;
            white-space: nowrap;
        }

        body:has(.dth-email-template-stats) .fi-ta-row:hover {
            background: #fbfdff;
        }

        body:has(.dth-email-template-stats) .fi-ta-cell {
            padding-block: .6rem;
            border-bottom-color: #edf1f5;
            vertical-align: middle;
            color: #334155;
            font-size: .77rem;
        }

        body:has(.dth-email-template-stats) .fi-ta-footer {
            padding: .72rem .9rem;
            background: #fff;
        }

        body:has(.dth-email-template-stats) .fi-ta-footer .fi-pagination :is(button, a) {
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
            font-size: .76rem;
            font-weight: 700;
        }

        body:has(.dth-email-template-stats) .fi-ta-footer .fi-pagination [aria-current="page"] {
            border-color: #6366f1 !important;
            background: #6366f1 !important;
            color: #fff !important;
        }

        @media (max-width: 1180px) {
            .dth-email-template-stats {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 520px) {
            .dth-email-template-stats {
                grid-template-columns: 1fr;
            }
        }

        .dark .dth-email-template-stat,
        .dark body:has(.dth-email-template-stats) .fi-ta,
        .dark body:has(.dth-email-template-stats) .fi-ta-header,
        .dark body:has(.dth-email-template-stats) .fi-ta-header-toolbar,
        .dark body:has(.dth-email-template-stats) .fi-ta-footer {
            border-color: #273244;
            background: #111827;
        }

        .dark .dth-email-template-stat__value,
        .dark body:has(.dth-email-template-stats) .fi-header-heading {
            color: #f8fafc;
        }
    </style>
</x-filament-widgets::widget>