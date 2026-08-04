<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 20px 0; border-bottom: 2px solid #7c3aed; }
        .header h1 { font-size: 20px; margin: 0; color: #7c3aed; }
        .content { padding: 20px 0; }
        .details { background: #f5f3ff; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .details p { margin: 5px 0; }
        .button { display: inline-block; padding: 12px 24px; background: #7c3aed; color: #fff; text-decoration: none; border-radius: 4px; margin: 15px 0; }
        .footer { text-align: center; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ __('sales.email.updated_quotation_title') }}</h1>
        </div>
        <div class="content">
            <p>{{ __('sales.email.dear_customer') }}</p>
            <p>{{ __('sales.email.quotation_resent_intro', ['company' => company_name()]) }}</p>

            <div class="details">
                <p><strong>{{ __('sales.email.quotation_number') }}:</strong> {{ $quotation->quotation_code }}-V{{ $quotation->version }}</p>
                <p><strong>{{ __('field.title') }}:</strong> {{ $quotation->title }}</p>
                <p><strong>{{ __('sales.email.grand_total') }}:</strong> {{ number_format($quotation->grand_total, 0) }} {{ $quotation->currency }}</p>
                <p><strong>{{ __('field.effective_until') }}:</strong> {{ $quotation->valid_until?->format('d/m/Y') }}</p>
            </div>

            <p>{{ __('sales.email.view_quotation_instruction') }}</p>
            <p style="text-align:center">
                <a href="{{ $publicUrl }}" class="button">{{ __('sales.email.view_quotation') }}</a>
            </p>

            <p>{{ __('sales.email.contact_staff_note') }}</p>
            <p>{{ __('sales.email.best_regards') }}</p>
            <p><strong>{{ company_name() }}</strong></p>
        </div>
        <div class="footer">
            <p>{{ __('sales.email.system_footer', ['company' => company_name()]) }}</p>
        </div>
    </div>
</body>
</html>
