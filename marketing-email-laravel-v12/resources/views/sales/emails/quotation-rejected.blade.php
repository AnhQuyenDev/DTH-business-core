<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 20px 0; border-bottom: 2px solid #dc2626; }
        .header h1 { font-size: 20px; margin: 0; color: #dc2626; }
        .content { padding: 20px 0; }
        .details { background: #fef2f2; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .details p { margin: 5px 0; }
        .reason-box { background: #fff; border: 1px solid #fca5a5; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .footer { text-align: center; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ __('sales.email.rejected_title') }}</h1>
        </div>
        <div class="content">
            <p>{{ __('sales.email.rejected_intro', ['code' => $quotation->quotation_code . '-V' . $quotation->version]) }}</p>

            <div class="details">
                <p><strong>{{ __('field.customer') }}:</strong> {{ $customer->company_name ?? $customer->display_name ?? __('common.not_available') }}</p>
                <p><strong>{{ __('sales.email.quotation_code') }}:</strong> {{ $quotation->quotation_code }}-V{{ $quotation->version }}</p>
                <p><strong>{{ __('field.title') }}:</strong> {{ $quotation->title }}</p>
                <p><strong>{{ __('sales.email.assigned_staff') }}:</strong> {{ $quotation->createdBy?->name ?? __('common.not_available') }}</p>
                <p><strong>{{ __('field.time') }}:</strong> {{ now()->format('d/m/Y H:i') }}</p>
            </div>

            @if($reason)
            <h3>{{ __('sales.email.rejection_reason') }}</h3>
            <div class="reason-box">
                <p>{{ $reason }}</p>
            </div>
            @endif

            <p>{{ __('sales.email.rejected_next_step') }}</p>
        </div>
        <div class="footer">
            <p>{{ __('sales.email.system_auto_footer', ['company' => company_name()]) }}</p>
        </div>
    </div>
</body>
</html>
