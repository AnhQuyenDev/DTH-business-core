<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $notification->title }}</title>
</head>
<body style="margin:0;background:#f4f7fb;font-family:Arial,Helvetica,sans-serif;color:#172033;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:28px 12px;background:#f4f7fb;">
    <tr><td align="center">
        <table role="presentation" width="640" cellspacing="0" cellpadding="0" style="max-width:640px;width:100%;background:#fff;border:1px solid #e3eaf3;border-radius:16px;overflow:hidden;">
            <tr>
                <td style="padding:22px 26px;background:linear-gradient(135deg,#eef5ff,#f9fbff);border-bottom:1px solid #e8eef6;">
                    <div style="font-size:12px;font-weight:700;letter-spacing:.12em;color:#2563eb;text-transform:uppercase;">DTH Business Core</div>
                    <h1 style="margin:8px 0 0;font-size:23px;line-height:1.25;color:#10243a;">{{ $notification->title }}</h1>
                </td>
            </tr>
            <tr>
                <td style="padding:26px;">
                    <p style="margin:0 0 18px;font-size:15px;line-height:1.65;color:#43536a;">{{ $notification->body }}</p>
                    @php($emailBody = data_get($notification->metadata ?? [], 'email_body') ?: $notification->detail_body)
                    @if($emailBody)
                        <div style="margin:0 0 22px;padding:16px 18px;border-radius:12px;background:#f8fafc;border:1px solid #e8edf3;font-size:14px;line-height:1.65;color:#344054;white-space:pre-line;">{{ $emailBody }}</div>
                    @endif
                    @if($notification->action_url)
                        <a href="{{ $notification->action_url }}" style="display:inline-block;padding:11px 17px;border-radius:10px;background:#2563eb;color:#fff;text-decoration:none;font-size:14px;font-weight:700;">{{ $notification->action_label ?: 'Mở trong DTH Business Core' }}</a>
                    @endif
                    <div style="margin-top:26px;padding-top:18px;border-top:1px solid #edf1f5;font-size:12px;line-height:1.6;color:#8a97a8;">
                        Người gửi: {{ $notification->sender_name ?: 'Hệ thống' }}<br>
                        Thời gian: {{ optional($notification->sent_at ?? $notification->created_at)->format('d/m/Y H:i') }}
                    </div>
                </td>
            </tr>
        </table>
    </td></tr>
</table>
</body>
</html>
