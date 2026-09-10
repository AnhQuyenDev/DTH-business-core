<x-filament-widgets::widget class="fi-wi-campaign-top-links">
<x-filament::section
    :heading="\Dth\Email\Support\UiText::get('reports.top_links', 'Link performance')"
    icon="heroicon-o-link"
    collapsible
>
    <style>
        .dth-links-table-wrap {
            overflow-x: auto;
        }

        .dth-links-table {
            width: 100%;
            min-width: 720px;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 0.875rem;
        }

        .dth-links-table th,
        .dth-links-table td {
            padding: 0.75rem;
            vertical-align: top;
            border-bottom: 1px solid rgb(229 231 235 / 0.8);
        }

        .dth-links-table th {
            color: rgb(107 114 128);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            line-height: 1.1rem;
            text-align: left;
            text-transform: uppercase;
        }

        .dth-links-table th:nth-child(1),
        .dth-links-table td:nth-child(1) {
            width: 42%;
        }

        .dth-links-table th:nth-child(n + 2),
        .dth-links-table td:nth-child(n + 2) {
            width: 14.5%;
            text-align: right;
        }

        .dth-links-table td {
            color: rgb(75 85 99);
            line-height: 1.35rem;
        }

        .dth-links-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .dth-links-table .dth-link-url {
            overflow-wrap: anywhere;
            color: var(--primary-600, rgb(217 119 6));
            font-weight: 500;
            text-align: left;
        }

        .dark .dth-links-table th {
            color: rgb(156 163 175);
        }

        .dark .dth-links-table td {
            color: rgb(209 213 219);
            border-bottom-color: rgb(255 255 255 / 0.1);
        }

        @media (max-width: 767px) {
            .dth-links-table {
                min-width: 680px;
            }
        }
    </style>

    @if ($links === [])
        <div class="rounded-xl border border-dashed border-gray-300 px-6 py-10 text-center dark:border-white/10">
            <x-filament::icon
                icon="heroicon-o-cursor-arrow-rays"
                class="mx-auto h-8 w-8 text-gray-400 dark:text-gray-500"
            />
            <div class="mt-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ \Dth\Email\Support\UiText::get('reports.no_link_data', 'No tracked link activity') }}
            </div>
        </div>
    @else
        <div class="dth-links-table-wrap">
            <table class="dth-links-table">
                <thead>
                    <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-3">{{ \Dth\Email\Support\UiText::get('reports.columns.url', 'URL') }}</th>
                        <th class="px-3 py-3 text-right">{{ \Dth\Email\Support\UiText::get('reports.columns.unique_clickers', 'Unique clickers') }}</th>
                        <th class="px-3 py-3 text-right">{{ \Dth\Email\Support\UiText::get('reports.columns.total_clicks', 'Total clicks') }}</th>
                        <th class="px-3 py-3 text-right">{{ \Dth\Email\Support\UiText::get('reports.columns.click_share', 'Click share') }}</th>
                        <th class="px-3 py-3 text-right">{{ \Dth\Email\Support\UiText::get('reports.columns.last_click', 'Last click') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                    @foreach ($links as $link)
                        <tr>
                            <td>
                                @if (\Illuminate\Support\Str::startsWith($link->url, ['https://', 'http://']))
                                    <a
                                        href="{{ $link->url }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="dth-link-url hover:underline"
                                    >
                                        {{ $link->url }}
                                    </a>
                                @else
                                    <span class="dth-link-url">
                                        {{ $link->url }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                {{ number_format($link->uniqueClickers) }}
                            </td>
                            <td>
                                {{ number_format($link->totalClicks) }}
                            </td>
                            <td>
                                {{ number_format($link->clickShare, 1) }}%
                            </td>
                            <td>
                                {{ $link->lastClickedAt?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament::section>

</x-filament-widgets::widget>
