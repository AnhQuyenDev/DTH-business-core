<div class="dth-notify-delivery-modal">
    <div class="dth-notify-delivery-summary">
        <span><strong>{{ $record->recipients->count() }}</strong> {{ app()->getLocale() === 'en' ? 'recipients' : 'người nhận' }}</span>
        <span><strong>{{ $record->recipients->where('email_status', 'sent')->count() }}</strong> Email sent</span>
        <span><strong>{{ $record->recipients->where('email_status', 'failed')->count() }}</strong> Email failed</span>
    </div>
    <div class="dth-notify-delivery-table-wrap">
        <table class="dth-notify-delivery-table">
            <thead><tr>
                <th>{{ app()->getLocale() === 'en' ? 'Recipient' : 'Người nhận' }}</th>
                <th>In-app</th>
                <th>{{ app()->getLocale() === 'en' ? 'Read' : 'Đã đọc' }}</th>
                <th>Email</th>
            </tr></thead>
            <tbody>
            @foreach($record->recipients as $recipient)
                <tr>
                    <td><strong>{{ $recipient->user?->name ?: '—' }}</strong><small>{{ $recipient->user?->email }}</small></td>
                    <td>{{ $recipient->in_app_delivered_at ? $recipient->in_app_delivered_at->format('d/m/Y H:i') : '—' }}</td>
                    <td>{{ $recipient->read_at ? $recipient->read_at->format('d/m/Y H:i') : '—' }}</td>
                    <td><span class="dth-notify-email-status dth-notify-email-status--{{ $recipient->email_status }}">{{ str($recipient->email_status)->headline() }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
