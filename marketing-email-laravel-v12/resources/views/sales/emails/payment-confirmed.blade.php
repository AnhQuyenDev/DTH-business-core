<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 20px 0; border-bottom: 2px solid #16a34a; }
        .header h1 { font-size: 20px; margin: 0; color: #16a34a; }
        .content { padding: 20px 0; }
        .details { background: #f0fdf4; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .details p { margin: 5px 0; }
        .footer { text-align: center; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ __('sales.email.payment_confirmed_title') }}</h1>
        </div>
        <div class="content">
            <p>{{ __('sales.email.dear_customer') }}</p>
            <p>{{ __('sales.email.payment_confirmed_intro', ['code' => $quotation->quotation_code . '-V' . $quotation->version]) }}</p>

            <div class="details">
                <p><strong>{{ __('sales.email.quotation_number') }}:</strong> {{ $quotation->quotation_code }}-V{{ $quotation->version }}</p>
                <p><strong>{{ __('field.title') }}:</strong> {{ $quotation->title }}</p>
                <p><strong>{{ __('sales.email.payment_amount') }}:</strong> {{ number_format($quotation->grand_total, 0) }} {{ $quotation->currency }}</p>
                <p><strong>{{ __('field.status') }}:</strong> {{ __('enum.sales.payment_status.paid') }}</p>
            </div>

            <p>{{ __('sales.email.payment_confirmed_thanks', ['company' => company_name()]) }}</p>
            <p>{{ __('sales.email.best_regards') }}</p>
            <p><strong>{{ company_name() }}</strong></p>
        </div>
        <div class="footer">
            <p>{{ __('sales.email.system_auto_footer', ['company' => company_name()]) }}</p>
        </div>
    </div>
</body>
</html>
