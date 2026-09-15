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
                            <th>{{ \Dth\Email\Support\UiText::get('dashboard.infrastructure.accounts', 'Tài khoản gửi') }}</th>
                            <th>{{ \Dth\Email\Support\UiText::get('dashboard.metrics.sent', 'Đã gửi') }}</th>
                            <th>{{ \Dth\Email\Support\UiText::get('dashboard.metrics.open_rate', 'Tỷ lệ mở') }}</th>
                            <th>{{ \Dth\Email\Support\UiText::get('dashboard.metrics.click_rate', 'Tỷ lệ nhấp') }}</th>
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
            <div class="dth-email-empty">{{ \Dth\Email\Support\UiText::get('dashboard.empty.heading', 'Không có dữ liệu trong giai đoạn này') }}</div>
        @endif

        <footer class="dth-email-panel-footer">
            <a href="{{ $indexUrl }}">
                {{ \Dth\Email\Support\UiText::get('dashboard.tables.view_all_accounts', 'Xem tất cả tài khoản') }}
                <x-filament::icon icon="heroicon-m-arrow-right" />
            </a>
        </footer>
    </section>
</x-filament-widgets::widget>
