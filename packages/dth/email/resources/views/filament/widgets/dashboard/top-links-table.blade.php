<x-filament-widgets::widget>
    <section class="dth-email-panel dth-email-table-widget">
        <header class="dth-email-panel-header">
            <div>
                <h3>{{ $heading }}</h3>
                <p>{{ $description }}</p>
            </div>
        </header>

        @if (count($rows))
            <div class="dth-email-mini-table-wrap">
                <table class="dth-email-mini-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ \Dth\Email\Support\UiText::get('reports.columns.url', 'URL') }}</th>
                            <th>{{ \Dth\Email\Support\UiText::get('dashboard.metrics.total_clicks', 'Clicks') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="dth-email-url-cell" title="{{ $row->url }}">{{ \Illuminate\Support\Str::limit($row->url, 42) }}</td>
                                <td class="dth-email-accent-value">{{ number_format($row->totalClicks, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="dth-email-empty">{{ \Dth\Email\Support\UiText::get('dashboard.empty.heading', 'No data for this period') }}</div>
        @endif

        <footer class="dth-email-panel-footer dth-email-panel-footer-muted">
            <span>{{ \Dth\Email\Support\UiText::get('dashboard.charts.top_links', 'Top links') }}</span>
        </footer>
    </section>
</x-filament-widgets::widget>
