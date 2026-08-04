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
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #ddd; font-size: 13px; }
        th { background: #f8fafc; font-weight: 600; }
        .total-row td { font-weight: bold; border-top: 2px solid #333; }
        .footer { text-align: center; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #999; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ __('sales.email.accepted_title') }}</h1>
        </div>
        <div class="content">
            <p>{{ __('sales.email.accepted_intro', ['code' => $quotation->quotation_code . '-V' . $quotation->version]) }}</p>

            <div class="details">
                <p><strong>{{ __('field.customer') }}:</strong> {{ $customer->company_name ?? $customer->full_name ?? __('common.not_available') }}</p>
                <p><strong>{{ __('sales.email.quotation_code') }}:</strong> {{ $quotation->quotation_code }}-V{{ $quotation->version }}</p>
                <p><strong>{{ __('field.title') }}:</strong> {{ $quotation->title }}</p>
                <p><strong>{{ __('sales.email.grand_total') }}:</strong> {{ number_format($quotation->grand_total, 0) }} {{ $quotation->currency }}</p>
                <p><strong>{{ __('sales.email.accepted_at') }}:</strong> {{ now()->format('d/m/Y H:i') }}</p>
                <p><strong>{{ __('sales.email.assigned_staff') }}:</strong> {{ $quotation->createdBy?->name ?? __('common.not_available') }}</p>
            </div>

            @if($items && $items->count())
            <h3>{{ __('sales.email.quotation_details') }}</h3>
            <table>
                <thead>
                    <tr>
                        <th>{{ __('sales.email.description') }}</th>
                        <th style="text-align:right">{{ __('sales.public.quantity') }}</th>
                        <th style="text-align:right">{{ __('sales.public.unit_price') }}</th>
                        <th style="text-align:right">{{ __('sales.public.line_total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>{{ $item->description ?? $item->service?->name ?? __('common.not_available') }}</td>
                        <td style="text-align:right">{{ $item->quantity }}</td>
                        <td style="text-align:right">{{ number_format($item->unit_price, 0) }}</td>
                        <td style="text-align:right">{{ number_format($item->line_total, 0) }}</td>
                    </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="3" style="text-align:right">{{ __('sales.email.total') }}</td>
                        <td style="text-align:right">{{ number_format($quotation->grand_total, 0) }} {{ $quotation->currency }}</td>
                    </tr>
                </tbody>
            </table>
            @endif

            @if($quotation->payment_status === 'paid')
            <p>{{ __('sales.email.payment_status_label') }}: <strong style="color:#16a34a">{{ __('enum.sales.payment_status.paid') }}</strong></p>
            @elseif($quotation->payment_status === 'partial')
            <p>{{ __('sales.email.payment_status_label') }}: <strong style="color:#f59e0b">{{ __('sales.email.partially_paid') }}</strong></p>
            @else
            <p>{{ __('sales.email.payment_status_label') }}: <strong>{{ __('enum.sales.payment_status.unpaid') }}</strong></p>
            @endif

            <p>{{ __('sales.email.accepted_next_step') }}</p>
        </div>
        <div class="footer">
            <p>{{ __('sales.email.system_auto_footer', ['company' => company_name()]) }}</p>
        </div>
    </div>
</body>
</html>
