<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; line-height: 1.6; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #2563eb; padding-bottom: 10px; }
        .header h1 { font-size: 18px; margin: 0; color: #2563eb; }
        .header .code { font-size: 14px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f8fafc; font-weight: 600; }
        .totals { margin-top: 20px; }
        .totals table { width: auto; margin-left: auto; }
        .totals td { border: none; padding: 4px 15px; }
        .totals .grand-total { font-size: 16px; font-weight: bold; color: #2563eb; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 15px; }
        .info-box { border: 1px solid #ddd; padding: 10px; border-radius: 4px; }
        .info-box h3 { margin: 0 0 8px; font-size: 13px; color: #2563eb; }
        .info-box p { margin: 2px 0; font-size: 11px; }
        .payment-box { border: 1px solid #ddd; padding: 10px; border-radius: 4px; margin: 15px 0; }
        .payment-box h3 { color: #2563eb; margin: 0 0 8px; font-size: 13px; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
        .terms { margin-top: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .terms h3 { margin: 0 0 8px; font-size: 13px; color: #2563eb; }
        .stamp { margin-top: 30px; text-align: right; }
        .stamp .line { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('sales.pdf.quotation_title') }}</h1>
        <p class="code">{{ $quotation->quotation_code }}-V{{ $quotation->version }}</p>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <h3>{{ __('sales.pdf.provider') }}</h3>
            <p><strong>{{ company_name() }}</strong></p>
        </div>
        <div class="info-box">
            <h3>{{ __('sales.pdf.customer') }}</h3>
            <p><strong>{{ $quotation->customer_snapshot['display_name'] ?? '' }}</strong></p>
            @if(!empty($quotation->company_snapshot['company_name']))
                <p>{{ $quotation->company_snapshot['company_name'] }}</p>
                <p>{{ __('field.tax_code') }}: {{ $quotation->company_snapshot['tax_code'] ?? '' }}</p>
            @endif
            <p>{{ __('field.email') }}: {{ $quotation->customer_snapshot['email'] ?? '' }}</p>
            <p>{{ __('field.phone') }}: {{ $quotation->customer_snapshot['phone'] ?? '' }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:40px">{{ __('sales.public.no') }}</th>
                <th>{{ __('sales.pdf.service') }}</th>
                <th style="width:60px">{{ __('sales.public.unit') }}</th>
                <th style="width:60px">{{ __('sales.public.quantity') }}</th>
                <th style="width:90px">{{ __('sales.public.unit_price') }}</th>
                <th style="width:80px">{{ __('sales.public.discount') }}</th>
                <th style="width:80px">{{ __('sales.public.vat') }}</th>
                <th style="width:100px">{{ __('sales.public.line_total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $item->service_name_snapshot }}</strong>
                    @if($item->package_name_snapshot)
                        <br><small>{{ $item->package_name_snapshot }}</small>
                    @endif
                </td>
                <td>{{ $item->unit }}</td>
                <td>{{ $item->quantity }}</td>
                <td>{{ number_format($item->unit_price, 0) }}</td>
                <td>{{ $item->discount_amount > 0 ? number_format($item->discount_amount, 0) : '-' }}</td>
                <td>{{ $item->vat_rate > 0 ? $item->vat_rate.'%' : '-' }}</td>
                <td style="text-align:right">{{ number_format($item->line_total, 0) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr><td>{{ __('sales.pdf.subtotal') }}:</td><td style="text-align:right">{{ number_format($quotation->subtotal, 0) }}</td></tr>
            @if($quotation->discount_total > 0)
            <tr><td>{{ __('sales.pdf.discount') }}:</td><td style="text-align:right">-{{ number_format($quotation->discount_total, 0) }}</td></tr>
            @endif
            <tr><td>{{ __('sales.public.vat_tax') }} ({{ $quotation->tax_total > 0 ? '10%' : '0%' }}):</td><td style="text-align:right">{{ number_format($quotation->tax_total, 0) }}</td></tr>
            <tr class="grand-total"><td><strong>{{ __('sales.pdf.grand_total') }}:</strong></td><td style="text-align:right"><strong>{{ number_format($quotation->grand_total, 0) }} {{ $quotation->currency }}</strong></td></tr>
        </table>
    </div>

    @php
        $snapshot = $quotation->payment_snapshot ?? [];
        $bankAccount = $quotation->bankAccount;
        $payment = array_merge(
            $bankAccount ? [
                'bank_account_id' => $bankAccount->id,
                'bank_code' => $bankAccount->bank_code,
                'bank_name' => $bankAccount->bank_name,
                'account_number' => $bankAccount->account_number,
                'account_name' => $bankAccount->account_name,
                'branch_name' => $bankAccount->branch_name,
                'swift_code' => $bankAccount->swift_code,
                'qr_template' => $bankAccount->qr_template,
            ] : [],
            $snapshot,
        );
    @endphp

    @if(!empty($payment['bank_code']) && !empty($payment['account_number']))
    <div class="payment-box">
        <h3>{{ __('sales.pdf.payment_information') }}</h3>
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="vertical-align:top; padding-right:20px;">
                    <p>{{ __('field.bank') }}: {{ $payment['bank_name'] ?? '' }} {{ !empty($payment['branch_name']) ? '- ' . $payment['branch_name'] : '' }}</p>
                    <p>{{ __('field.account_number') }}: {{ $payment['account_number'] }}</p>
                    <p>{{ __('field.account_name') }}: {{ $payment['account_name'] ?? '' }}</p>
                    @if(!empty($payment['swift_code']))
                    <p>{{ __('field.swift_code') }}: {{ $payment['swift_code'] }}</p>
                    @endif
                    <p>{{ __('sales.public.transfer_content') }}: <strong>{{ $payment['transfer_content'] ?? $quotation->quotation_code }}</strong></p>
                </td>
                <td style="vertical-align:top; text-align:center; width:220px;">
                    {!! app(\App\Services\Sales\QrPaymentService::class)->generateHtml(
                        $payment['bank_code'],
                        $payment['account_number'],
                        $quotation->grand_total,
                        $payment['transfer_content'] ?? $quotation->quotation_code,
                        $payment['account_name'] ?? null,
                        180,
                        $payment['qr_template'] ?? null,
                    ) !!}
                    <p style="font-size:10px; color:#666;">{{ __('sales.pdf.scan_qr_payment') }}</p>
                </td>
            </tr>
        </table>
    </div>
    @endif

    @if($quotation->terms_snapshot)
    <div class="terms">
        <h3>{{ __('sales.pdf.terms_conditions') }}</h3>
        <p>{{ __('sales.pdf.validity') }}: {{ $quotation->terms_snapshot['valid_until'] ?? $quotation->valid_until?->format('d/m/Y') ?? __('common.not_available') }}</p>
        @if(!empty($quotation->terms_snapshot['scope']))
            <p><strong>{{ __('sales.pdf.scope_of_supply') }}:</strong></p>
            <p>{{ $quotation->terms_snapshot['scope'] }}</p>
        @endif
        @if(!empty($quotation->terms_snapshot['notes']))
            <p><strong>{{ __('field.notes') }}:</strong> {{ $quotation->terms_snapshot['notes'] }}</p>
        @endif
    </div>
    @endif

    <div class="stamp">
        <p class="line"><strong>{{ __('sales.pdf.company_representative') }}</strong></p>
        <p class="line" style="margin-top:50px;">{{ company_name() }}</p>
    </div>

    <div class="footer">
        <p>{{ __('sales.pdf.created_at', ['time' => now()->format('d/m/Y H:i'), 'company' => company_name()]) }}</p>
    </div>
</body>
</html>
