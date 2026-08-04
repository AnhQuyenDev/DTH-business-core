<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 20px 0; border-bottom: 2px solid #2563eb; }
        .header h1 { font-size: 20px; margin: 0; color: #2563eb; }
        .content { padding: 20px 0; }
        .otp-box { background: #f8fafc; border: 1px dashed #2563eb; border-radius: 6px; padding: 20px; text-align: center; margin: 20px 0; }
        .otp-box .code { font-size: 28px; font-weight: bold; color: #2563eb; letter-spacing: 6px; }
        .footer { text-align: center; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ __('sales.email.otp_title') }}</h1>
        </div>
        <div class="content">
            <p>{{ __('sales.email.otp_intro', ['code' => $quotationCode]) }}</p>

            <div class="otp-box">
                <p style="margin:0 0 8px; font-size:14px;">{{ __('sales.email.otp_label') }}</p>
                <div class="code">{{ $otp }}</div>
            </div>

            <p>{{ __('sales.email.otp_expiry', ['minutes' => $expiresInMinutes]) }}</p>
            <p>{{ __('sales.email.otp_not_you') }}</p>
        </div>
        <div class="footer">
            <p>{{ __('sales.email.system_footer', ['company' => company_name()]) }}</p>
        </div>
    </div>
</body>
</html>