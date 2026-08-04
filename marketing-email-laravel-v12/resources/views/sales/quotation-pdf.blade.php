<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; line-height: 1.5; color: #333; }
        table { width: 100%; border-collapse: collapse; }
        .page-header { border-bottom: 2px solid #2563eb; padding-bottom: 10px; margin-bottom: 14px; }
        .page-header .company { font-size: 14px; font-weight: bold; color: #2563eb; }
        .page-header .muted { font-size: 10px; color: #666; }
        .quote-title { font-size: 22px; font-weight: bold; color: #2563eb; text-align: right; }
        .quote-meta { font-size: 11px; text-align: right; color: #333; }
        .section-title { font-size: 13px; font-weight: bold; color: #2563eb; margin: 16px 0 8px; }
        .info-table td { padding: 3px 8px; vertical-align: top; }
        .info-box { border: 1px solid #ddd; padding: 8px 10px; }
        .info-box .label { font-size: 12px; font-weight: bold; color: #2563eb; margin-bottom: 4px; }
        .items-table th { background: #f1f5f9; border: 1px solid #ddd; padding: 6px 8px; font-size: 10px; }
        .items-table td { border: 1px solid #ddd; padding: 6px 8px; font-size: 10px; }
        .totals-table { width: auto; margin-left: auto; }
        .totals-table td { padding: 3px 12px; }
        .grand-total { font-size: 14px; font-weight: bold; color: #2563eb; }
        .scope-table th { background: #f1f5f9; border: 1px solid #ddd; padding: 6px 8px; font-size: 10px; text-align: left; }
        .scope-table td { border: 1px solid #ddd; padding: 6px 8px; font-size: 10px; vertical-align: top; }
        .payment-box { border: 1px solid #ddd; padding: 10px 12px; margin: 12px 0; }
        .terms-box { border: 1px solid #ddd; padding: 10px 12px; margin: 12px 0; }
        .sign-box { border: 1px solid #ddd; padding: 14px; text-align: center; vertical-align: top; height: 130px; }
        .sign-box .title { font-size: 12px; font-weight: bold; color: #333; margin-bottom: 4px; }
        .sign-box .hint { font-size: 9px; color: #666; margin-bottom: 60px; }
        .sign-box .name { font-size: 11px; font-weight: bold; }
        .footer { text-align: center; margin-top: 24px; font-size: 9px; color: #777; border-top: 1px solid #ddd; padding-top: 8px; }
        .intro { margin: 10px 0; }
    </style>
</head>
<body>
    <table class="page-header">
        <tr>
            <td style="width:60%;">
                @if(company_logo_data_uri())
                    <img src="{{ company_logo_data_uri() }}" style="max-height:48px; max-width:180px;">
                @endif
                <div class="company">{{ company_name() }}</div>
                @if(company_address())<div class="muted">Địa chỉ: {{ company_address() }}</div>@endif
                @if(company_phone())<div class="muted">Hotline: {{ company_phone() }}</div>@endif
                @if(company_email())<div class="muted">Email: {{ company_email() }}</div>@endif
            </td>
            <td style="width:40%;">
                <div class="quote-title">{{ __('sales.pdf.quotation_title') }}</div>
                <div class="quote-meta">
                    <strong>{{ __('field.code') }}:</strong> {{ $quotation->quotation_code }}-V{{ $quotation->version }}<br>
                    {{ __('field.date') }}: {{ $quotation->quotation_date?->format('d/m/Y') }}<br>
                    {{ __('field.effective_until') }}: {{ $quotation->valid_until?->format('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>

    <table class="info-table">
        <tr>
            <td style="width:50%;">
                <table class="info-box" style="width:100%;">
                    <tr><td><div class="label">{{ __('sales.pdf.customer') }}</div></td></tr>
                    <tr><td><strong>{{ $quotation->customer_snapshot['display_name'] ?? '' }}</strong></td></tr>
                    @if(!empty($quotation->company_snapshot['company_name']))
                        <tr><td>{{ $quotation->company_snapshot['company_name'] }}</td></tr>
                        <tr><td>{{ __('field.tax_code') }}: {{ $quotation->company_snapshot['tax_code'] ?? '' }}</td></tr>
                    @endif
                    @if(!empty($quotation->customer_snapshot['phone']))
                        <tr><td>{{ __('field.phone') }}: {{ $quotation->customer_snapshot['phone'] }}</td></tr>
                    @endif
                    @if(!empty($quotation->company_snapshot['company_address']))
                        <tr><td>{{ __('field.address') }}: {{ $quotation->company_snapshot['company_address'] }}</td></tr>
                    @endif
                    @if(!empty($quotation->customer_snapshot['email']))
                        <tr><td>{{ __('field.email') }}: {{ $quotation->customer_snapshot['email'] }}</td></tr>
                    @endif
                </table>
            </td>
            <td style="width:50%;">
                <table class="info-box" style="width:100%;">
                    <tr><td><div class="label">{{ __('sales.public.quotation_information') }}</div></td></tr>
                    @if($quotation->priceBook)
                        <tr><td>{{ __('sales.public.price_channel') }}: {{ $quotation->priceBook->name }}</td></tr>
                    @endif
                    <tr><td>{{ __('field.date') }}: {{ $quotation->quotation_date?->format('d/m/Y') }}</td></tr>
                    <tr><td>{{ __('field.effective_until') }}: {{ $quotation->valid_until?->format('d/m/Y') }}</td></tr>
                    @if($quotation->assignedStaff?->user)
                        <tr><td>{{ __('sales.public.staff_in_charge') }}: {{ $quotation->assignedStaff->full_name }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <div class="intro">{{ __('sales.public.intro', ['company' => company_name(), 'title' => $quotation->title]) }}</div>

    <div class="section-title">{{ __('sales.public.items_title') }}</div>
    <table class="items-table">
        <thead>
            <tr>
                <th style="width:30px;">{{ __('sales.public.no') }}</th>
                <th>{{ __('sales.pdf.service') }}</th>
                <th style="width:50px;">{{ __('sales.public.unit') }}</th>
                <th style="width:50px;">{{ __('sales.public.quantity') }}</th>
                <th style="width:80px;">{{ __('sales.public.unit_price') }}</th>
                <th style="width:70px;">{{ __('sales.public.discount') }}</th>
                <th style="width:60px;">{{ __('sales.public.vat') }}</th>
                <th style="width:85px;">{{ __('sales.public.line_total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $index => $item)
            <tr>
                <td style="text-align:center;">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $item->service_name_snapshot }}</strong>
                    @if($item->package_name_snapshot)<br><span style="font-size:9px; color:#666;">{{ $item->package_name_snapshot }}</span>@endif
                </td>
                <td style="text-align:center;">{{ $item->unit }}</td>
                <td style="text-align:center;">{{ number_format($item->quantity, $item->quantity == intval($item->quantity) ? 0 : 2) }}</td>
                <td style="text-align:right;">{{ format_money($item->unit_price) }}</td>
                <td style="text-align:right;">{{ $item->discount_amount > 0 ? format_money($item->discount_amount) : '-' }}</td>
                <td style="text-align:center;">{{ $item->vat_rate > 0 ? $item->vat_rate.'%' : '-' }}</td>
                <td style="text-align:right;">{{ format_money($item->line_total) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table" style="margin-top:10px;">
        <tr><td>{{ __('sales.pdf.subtotal') }}:</td><td style="text-align:right;">{{ format_money($quotation->subtotal) }}</td></tr>
        @if($quotation->discount_total > 0)
        <tr><td>{{ __('sales.pdf.discount') }}:</td><td style="text-align:right;">-{{ format_money($quotation->discount_total) }}</td></tr>
        @endif
        <tr><td>{{ __('sales.public.vat_tax') }}:</td><td style="text-align:right;">{{ format_money($quotation->tax_total) }}</td></tr>
        <tr><td class="grand-total"><strong>{{ __('sales.pdf.grand_total') }}:</strong></td><td class="grand-total" style="text-align:right;"><strong>{{ format_money($quotation->grand_total) }} {{ $quotation->currency }}</strong></td></tr>
    </table>

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

        $hasScope = false;
        foreach ($quotation->items as $item) {
            if (!empty($item->scope_snapshot)) { $hasScope = true; break; }
        }
        if (!empty($quotation->terms_snapshot['scope'])) { $hasScope = true; }
    @endphp

    @if($hasScope)
        <div class="section-title">{{ __('sales.public.scope_of_supply') }}</div>
        <table class="scope-table">
            <thead>
                <tr>
                    <th style="width:180px;">{{ __('sales.public.scope_item') }}</th>
                    <th>{{ __('sales.public.scope_content') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotation->items as $item)
                    @if(!empty($item->scope_snapshot))
                    <tr>
                        <td><strong>{{ $item->service_name_snapshot }}</strong></td>
                        <td>{!! nl2br(e($item->scope_snapshot)) !!}</td>
                    </tr>
                    @endif
                @endforeach
                @if(!empty($quotation->terms_snapshot['scope']))
                <tr>
                    <td><strong>{{ $quotation->title }}</strong></td>
                    <td>{!! nl2br(e($quotation->terms_snapshot['scope'])) !!}</td>
                </tr>
                @endif
            </tbody>
        </table>
    @endif

    @if(!empty($payment['bank_code']) && !empty($payment['account_number']))
        <div class="section-title">{{ __('sales.pdf.payment_information') }}</div>
        <div class="payment-box">
            <table>
                <tr>
                    <td style="width:65%; vertical-align:top;">
                        <div>{{ __('field.bank') }}: <strong>{{ $payment['bank_name'] ?? '' }}</strong> @if(!empty($payment['branch_name'])) - {{ $payment['branch_name'] }} @endif</div>
                        <div>{{ __('field.account_number') }}: <strong>{{ $payment['account_number'] }}</strong></div>
                        <div>{{ __('field.account_name') }}: {{ $payment['account_name'] ?? '' }}</div>
                        @if(!empty($payment['swift_code']))
                        <div>{{ __('field.swift_code') }}: {{ $payment['swift_code'] }}</div>
                        @endif
                        <div>{{ __('sales.public.transfer_amount') }}: <strong>{{ format_money($quotation->grand_total) }} {{ $quotation->currency }}</strong></div>
                        <div>{{ __('sales.public.transfer_content') }}: <strong>{{ $payment['transfer_content'] ?? $quotation->quotation_code }}</strong></div>
                    </td>
                    <td style="width:35%; text-align:center; vertical-align:top;">
                        {!! app(\App\Services\Sales\QrPaymentService::class)->generateHtml(
                            $payment['bank_code'],
                            $payment['account_number'],
                            $quotation->grand_total,
                            $payment['transfer_content'] ?? $quotation->quotation_code,
                            $payment['account_name'] ?? null,
                            160,
                            $payment['qr_template'] ?? null,
                        ) !!}
                        <div style="font-size:9px; color:#666;">{{ __('sales.pdf.scan_qr_payment') }}</div>
                    </td>
                </tr>
            </table>
        </div>
    @endif

    @if($quotation->terms_snapshot)
        <div class="section-title">{{ __('sales.pdf.terms_conditions') }}</div>
        <div class="terms-box">
            <div>{{ __('sales.pdf.validity') }}: <strong>{{ $quotation->terms_snapshot['valid_until'] ?? $quotation->valid_until?->format('d/m/Y') ?? __('common.not_available') }}</strong></div>
            @if(!empty($quotation->terms_snapshot['payment_terms']))
                <div style="margin-top:6px;"><strong>{{ __('sales.pdf.payment_terms') }}:</strong></div>
                <div>{{ $quotation->terms_snapshot['payment_terms'] }}</div>
            @endif
            @if(!empty($quotation->terms_snapshot['vat_note']))
                <div style="margin-top:6px;"><strong>{{ __('sales.pdf.vat_note') }}:</strong></div>
                <div>{{ $quotation->terms_snapshot['vat_note'] }}</div>
            @endif
            @if(!empty($quotation->terms_snapshot['notes']))
                <div style="margin-top:6px;"><strong>{{ __('field.notes') }}:</strong> {{ $quotation->terms_snapshot['notes'] }}</div>
            @endif
        </div>
    @endif

    <div style="margin-top:20px;">
        <table>
            <tr>
                <td style="width:50%; padding-right:8px;">
                    <table class="sign-box" style="width:100%;">
                        <tr><td><div class="title">{{ __('sales.pdf.company_representative') }}</div></td></tr>
                        <tr><td><div class="hint">{{ __('sales.public.prepared_by') }}</div></td></tr>
                        <tr><td><div class="name">{{ company_name() }}</div></td></tr>
                    </table>
                </td>
                <td style="width:50%; padding-left:8px;">
                    <table class="sign-box" style="width:100%;">
                        <tr><td><div class="title">{{ __('sales.public.customer_confirmation') }}</div></td></tr>
                        <tr><td><div class="hint">{{ __('sales.public.sign_and_stamp') }}</div></td></tr>
                        <tr><td><div class="name">{{ $quotation->customer_snapshot['display_name'] ?? '' }}</div></td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        @if(company_email())<div>{{ __('field.email') }}: {{ company_email() }}</div>@endif
        <div>{{ __('sales.public.generated_note', ['company' => company_name()]) }}</div>
        <div>{{ __('sales.pdf.created_at', ['time' => now()->format('d/m/Y H:i'), 'company' => company_name()]) }}</div>
    </div>
</body>
</html>
