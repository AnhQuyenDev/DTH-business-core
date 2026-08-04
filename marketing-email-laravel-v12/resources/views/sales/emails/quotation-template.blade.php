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
        .details { background: #f8fafc; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .details p { margin: 5px 0; }
        .button { display: inline-block; padding: 12px 24px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 4px; margin: 15px 0; }
        .footer { text-align: center; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ __('sales.email.quotation_title') }}</h1>
        </div>
        <div class="content">
            {!! $body !!}
        </div>
        <div class="footer">
            <p>{{ __('sales.email.system_footer', ['company' => company_name()]) }}</p>
        </div>
    </div>
</body>
</html>
