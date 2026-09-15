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
                            <th>{{ \Dth\Email\Support\UiText::get('dashboard.infrastructure.accounts', 'Sending account') }}</th>
                            <th>{{ \Dth\Email\Support\UiText::get('dashboard.metrics.sent', 'Sent') }}</th>
                            <th>{{ \Dth\Email\Support\UiText::get('dashboard.metrics.open_rate', 'Open rate') }}</th>
                            <th>{{ \Dth\Email\Support\UiText::get('dashboard.metrics.click_rate', 'Click rate') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                <td><strong>{{ $row->name }}</strong></td>
                                <td>{{ number_format($row->sent, 0, ',', '.') }}</td>
                                <td>{{ number_format($row->openRate, 1, ',', '.') }}%</td>
                                <td class="dth-email-accent-value">{{ number_format($row->clickRate, 1, ',', '.') }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="dth-email-empty">{{ \Dth\Email\Support\UiText::get('dashboard.empty.heading', 'No data for this period') }}</div>
        @endif

        <footer class="dth-email-panel-footer">
            <a href="{{ $indexUrl }}">
                {{ \Dth\Email\Support\UiText::get('models.sending_accounts', 'Sending Accounts') }}
                <x-filament::icon icon="heroicon-m-arrow-right" />
            </a>
        </footer>
    </section>
</x-filament-widgets::widget>
