@php
    $interactive = (bool) ($interactive ?? false);

    $formatDate = static function ($value): string {
        if (blank($value)) {
            return __('common.not_available');
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    };

    $payment = $quotation->payment_snapshot ?? [];

    $hasScope = false;
    foreach ($quotation->items as $item) {
        if (! empty($item->scope_snapshot)) {
            $hasScope = true;
            break;
        }
    }
    if (! empty($quotation->terms_snapshot['scope'])) {
        $hasScope = true;
    }

    $emailLogs = $quotation->relationLoaded('emailLogs')
        ? $quotation->emailLogs
        : collect();

    $sentEmailLog = $emailLogs
        ->sortByDesc('id')
        ->first(function ($log): bool {
            $status = $log->status?->value ?? (string) $log->status;
            return $status === 'sent';
        });

    $senderLog = $sentEmailLog ?? $emailLogs->sortByDesc('id')->first();
    $senderEmail = trim((string) ($senderLog?->sender_email ?? ''));
    $senderName = trim((string) ($senderLog?->sender_name ?? ''));

    $statusStyle = match ($quotation->status->value) {
        'accepted' => 'background:#dcfce7;color:#166534;',
        'rejected', 'expired', 'cancelled', 'superseded' => 'background:#fee2e2;color:#991b1b;',
        'revision_requested' => 'background:#ffedd5;color:#9a3412;',
        'viewed' => 'background:#dbeafe;color:#1d4ed8;',
        'sent' => 'background:#e0f2fe;color:#0369a1;',
        default => 'background:#f3f4f6;color:#4b5563;',
    };
@endphp

<style>
    .qdoc { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 13px; line-height: 1.55; color: #1f2937; background: #fff; }
    .qdoc * { box-sizing: border-box; }
    .qdoc table { width: 100%; border-collapse: collapse; }
    .qdoc-page-header { border-bottom: 2px solid #2563eb; padding-bottom: 12px; margin-bottom: 18px; }
    .qdoc-company { font-size: 17px; font-weight: 700; color: #1f2937; margin-top: 6px; }
    .qdoc-muted { font-size: 12px; color: #6b7280; }
    .qdoc-title { font-size: 28px; font-weight: 800; color: #2563eb; text-align: right; letter-spacing: .02em; }
    .qdoc-meta { font-size: 12px; text-align: right; color: #4b5563; }
    .qdoc-status { display: inline-block; margin-top: 8px; padding: 3px 9px; border-radius: 5px; font-size: 11px; font-weight: 700; }
    .qdoc-section-title { font-size: 15px; font-weight: 700; color: #1f2937; margin: 20px 0 10px; }
    .qdoc-info-table td { width: 50%; padding: 4px 8px; vertical-align: top; }
    .qdoc-info-box { border: 1px solid #d1d5db; border-radius: 8px; padding: 12px 14px; background: #f9fafb; min-height: 150px; }
    .qdoc-info-label { font-size: 13px; font-weight: 700; color: #2563eb; margin-bottom: 7px; }
    .qdoc-info-line { margin: 2px 0; }
    .qdoc-intro { margin: 18px 0; }
    .qdoc-items th, .qdoc-scope th { background: #f3f4f6; border: 1px solid #d1d5db; padding: 8px; font-size: 11px; }
    .qdoc-items td, .qdoc-scope td { border: 1px solid #d1d5db; padding: 8px; font-size: 11px; vertical-align: top; }
    .qdoc-totals { width: auto !important; margin-left: auto; margin-top: 10px; }
    .qdoc-totals td { padding: 4px 12px; }
    .qdoc-grand-total td { border-top: 2px solid #2563eb; color: #2563eb; font-size: 15px; font-weight: 700; padding-top: 8px; }
    .qdoc-box { border: 1px solid #d1d5db; border-radius: 8px; padding: 13px 15px; margin: 10px 0 18px; background: #f9fafb; }
    .qdoc-payment td { vertical-align: top; }
    .qdoc-payment-left { width: 65%; padding-right: 14px; }
    .qdoc-payment-right { width: 35%; text-align: center; }
    .qdoc-payment-row { margin: 4px 0; }
    .qdoc-copy-button { display: inline-block; margin-left: 7px; padding: 2px 8px; border: 1px solid #93c5fd; border-radius: 5px; background: #fff; color: #2563eb; font-size: 11px; cursor: pointer; }
    .qdoc-copy-button:hover { background: #eff6ff; }
    .qdoc-sign-table td { width: 50%; vertical-align: top; padding: 0 6px; }
    .qdoc-sign-box { border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; text-align: center; min-height: 155px; }
    .qdoc-sign-title { font-size: 13px; font-weight: 700; color: #1f2937; }
    .qdoc-sign-hint { font-size: 10px; color: #6b7280; margin-top: 4px; margin-bottom: 56px; }
    .qdoc-sign-name { font-size: 12px; font-weight: 700; }
    .qdoc-footer { text-align: center; margin-top: 24px; font-size: 10px; color: #6b7280; border-top: 1px solid #d1d5db; padding-top: 10px; }
    .qdoc-contact-email { color: #2563eb; font-weight: 600; text-decoration: none; }
    .qdoc-interactive { margin-top: 18px; }

    @if($interactive)
        <style>
            @media screen and (max-width: 760px) {
                .qdoc-title,
                .qdoc-meta {
                    text-align: left;
                }

                .qdoc-page-header td,
                .qdoc-info-table td,
                .qdoc-sign-table td,
                .qdoc-payment td {
                    display: block;
                    width: 100% !important;
                }

                .qdoc-page-header td + td,
                .qdoc-info-table td + td,
                .qdoc-sign-table td + td {
                    margin-top: 12px;
                }

                .qdoc-payment-left {
                    padding-right: 0;
                }

                .qdoc-payment-right {
                    margin-top: 14px;
                }

                .qdoc-items {
                    display: block;
                    overflow-x: auto;
                    white-space: nowrap;
                }
            }
            @media print {
                .qdoc-copy-button, .qdoc-interactive { display: none !important; }
            }
        </style>
    @endif
</style>

<div class="qdoc">
    <table class="qdoc-page-header">
        <tr>
            <td style="width:60%; vertical-align:top;">
                @if(company_logo_data_uri())
                    <img src="{{ company_logo_data_uri() }}" alt="{{ company_name() }}" style="max-height:54px;max-width:190px;width:auto;height:auto;">
                @endif
                <div class="qdoc-company">{{ company_name() }}</div>
                @if(company_address())<div class="qdoc-muted">{{ __('field.address') }}: {{ company_address() }}</div>@endif
                @if(company_phone())<div class="qdoc-muted">{{ __('sales.public.hotline') }}: {{ company_phone() }}</div>@endif
                @if(company_email())<div class="qdoc-muted">{{ __('field.email') }}: {{ company_email() }}</div>@endif
            </td>
            <td style="width:40%; vertical-align:top;">
                <div class="qdoc-title">{{ __('sales.pdf.quotation_title') }}</div>
                <div class="qdoc-meta">
                    <strong>{{ __('field.code') }}:</strong> {{ $quotation->quotation_code }}-V{{ $quotation->version }}<br>
                    {{ __('field.date') }}: {{ $formatDate($quotation->quotation_date) }}<br>
                    {{ __('field.effective_until') }}: {{ $formatDate($quotation->valid_until) }}
                    @if($interactive)
                        <div><span class="qdoc-status" style="{{ $statusStyle }}">{{ $quotation->status->label() }}</span></div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="qdoc-info-table">
        <tr>
            <td>
                <div class="qdoc-info-box">
                    <div class="qdoc-info-label">{{ __('sales.public.customer_info') }}</div>
                    <div class="qdoc-info-line"><strong>{{ $quotation->customer_snapshot['display_name'] ?? '' }}</strong></div>
                    @if(!empty($quotation->company_snapshot['company_name']))
                        <div class="qdoc-info-line">{{ $quotation->company_snapshot['company_name'] }}</div>
                        <div class="qdoc-info-line">{{ __('field.tax_code') }}: {{ $quotation->company_snapshot['tax_code'] ?? '' }}</div>
                    @endif
                    <div class="qdoc-info-line">{{ __('sales.public.contact_person') }}: {{ $quotation->customer_snapshot['contact_name'] ?? $quotation->customer_snapshot['display_name'] ?? '' }}</div>
                    @if(!empty($quotation->customer_snapshot['phone']))
                        <div class="qdoc-info-line">{{ __('field.phone') }}: {{ $quotation->customer_snapshot['phone'] }}</div>
                    @endif
                    @if(!empty($quotation->company_snapshot['company_address']))
                        <div class="qdoc-info-line">{{ __('field.address') }}: {{ $quotation->company_snapshot['company_address'] }}</div>
                    @endif
                    @if(!empty($quotation->customer_snapshot['email']))
                        <div class="qdoc-info-line">{{ __('field.email') }}: {{ $quotation->customer_snapshot['email'] }}</div>
                    @endif
                </div>
            </td>
            <td>
                <div class="qdoc-info-box">
                    <div class="qdoc-info-label">{{ __('sales.public.quotation_information') }}</div>
                    @if($quotation->priceBook)
                        <div class="qdoc-info-line">{{ __('sales.public.price_channel') }}: {{ $quotation->priceBook->name }}</div>
                    @endif
                    <div class="qdoc-info-line">{{ __('field.date') }}: {{ $formatDate($quotation->quotation_date) }}</div>
                    <div class="qdoc-info-line">{{ __('field.effective_until') }}: {{ $formatDate($quotation->valid_until) }}</div>
                    @if($quotation->assignedStaff?->user)
                        <div class="qdoc-info-line">{{ __('sales.public.staff_in_charge') }}: {{ $quotation->assignedStaff->full_name }}</div>
                    @endif
                    @if($senderEmail !== '')
                        <div class="qdoc-info-line">
                            {{ __('sales.public.reply_email') }}:
                            <a class="qdoc-contact-email" href="mailto:{{ $senderEmail }}">{{ $senderEmail }}</a>
                            @if($senderName !== '')
                                <span class="qdoc-muted">({{ $senderName }})</span>
                            @endif
                        </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="qdoc-intro">{{ __('sales.public.intro', ['company' => company_name(), 'title' => $quotation->title]) }}</div>

    <div class="qdoc-section-title">{{ __('sales.public.items_title') }}</div>
    <table class="qdoc-items">
        <thead>
            <tr>
                <th style="width:38px;">{{ __('sales.public.no') }}</th>
                <th>{{ __('sales.public.service') }}</th>
                <th style="width:64px;">{{ __('sales.public.unit') }}</th>
                <th style="width:64px;">{{ __('sales.public.quantity') }}</th>
                <th style="width:100px;">{{ __('sales.public.unit_price') }}</th>
                <th style="width:88px;">{{ __('sales.public.discount') }}</th>
                <th style="width:65px;">{{ __('sales.public.vat') }}</th>
                <th style="width:105px;">{{ __('sales.public.line_total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quotation->items as $index => $item)
                <tr>
                    <td style="text-align:center;">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->service_name_snapshot }}</strong>
                        @if($item->package_name_snapshot)<br><span class="qdoc-muted">{{ $item->package_name_snapshot }}</span>@endif
                    </td>
                    <td style="text-align:center;">{{ $item->unit }}</td>
                    <td style="text-align:center;">{{ number_format($item->quantity, $item->quantity == intval($item->quantity) ? 0 : 2) }}</td>
                    <td style="text-align:right;">{{ format_money($item->unit_price) }}</td>
                    <td style="text-align:right;">{{ $item->discount_amount > 0 ? format_money($item->discount_amount) : '-' }}</td>
                    <td style="text-align:center;">{{ $item->vat_rate > 0 ? $item->vat_rate.'%' : '-' }}</td>
                    <td style="text-align:right;font-weight:600;">{{ format_money($item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="qdoc-totals">
        <tr><td>{{ __('sales.public.subtotal') }}:</td><td style="text-align:right;">{{ format_money($quotation->subtotal) }}</td></tr>
        @if($quotation->discount_total > 0)
            <tr><td>{{ __('sales.public.discount') }}:</td><td style="text-align:right;">-{{ format_money($quotation->discount_total) }}</td></tr>
        @endif
        <tr><td>{{ __('sales.public.vat_tax') }}:</td><td style="text-align:right;">{{ format_money($quotation->tax_total) }}</td></tr>
        <tr class="qdoc-grand-total"><td>{{ __('sales.public.grand_total') }}:</td><td style="text-align:right;">{{ format_money($quotation->grand_total) }} {{ $quotation->currency }}</td></tr>
    </table>

    @if($hasScope)
        <div class="qdoc-section-title">{{ __('sales.public.scope_of_supply') }}</div>
        <table class="qdoc-scope">
            <thead>
                <tr>
                    <th style="width:180px;text-align:left;">{{ __('sales.public.scope_item') }}</th>
                    <th style="text-align:left;">{{ __('sales.public.scope_content') }}</th>
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
        <div class="qdoc-section-title">{{ __('sales.pdf.payment_information') }}</div>
        <div class="qdoc-box">
            <table class="qdoc-payment">
                <tr>
                    <td class="qdoc-payment-left">
                        <div class="qdoc-payment-row">{{ __('field.bank') }}: <strong>{{ $payment['bank_name'] ?? '' }}</strong> @if(!empty($payment['branch_name'])) - {{ $payment['branch_name'] }} @endif</div>
                        <div class="qdoc-payment-row">
                            {{ __('field.account_number') }}: <strong>{{ $payment['account_number'] }}</strong>
                            @if($interactive)
                                <button type="button" data-copy="{{ $payment['account_number'] }}" onclick="copyText(this.dataset.copy)" class="qdoc-copy-button">{{ __('action.copy') }}</button>
                            @endif
                        </div>
                        <div class="qdoc-payment-row">{{ __('field.account_name') }}: {{ $payment['account_name'] ?? '' }}</div>
                        @if(!empty($payment['swift_code']))
                            <div class="qdoc-payment-row">{{ __('field.swift_code') }}: {{ $payment['swift_code'] }}</div>
                        @endif
                        <div class="qdoc-payment-row">{{ __('sales.public.transfer_amount') }}: <strong>{{ format_money($quotation->grand_total) }} {{ $quotation->currency }}</strong></div>
                        <div class="qdoc-payment-row">
                            {{ __('sales.public.transfer_content') }}: <strong style="color:#2563eb;">{{ $payment['transfer_content'] ?? $quotation->quotation_code }}</strong>
                            @if($interactive)
                                <button type="button" data-copy="{{ $payment['transfer_content'] ?? $quotation->quotation_code }}" onclick="copyText(this.dataset.copy)" class="qdoc-copy-button">{{ __('action.copy') }}</button>
                            @endif
                        </div>
                    </td>
                    <td class="qdoc-payment-right">
                        {!! app(\App\Services\Sales\QrPaymentService::class)->generateHtml(
                            $payment['bank_code'],
                            $payment['account_number'],
                            $quotation->grand_total,
                            $payment['transfer_content'] ?? $quotation->quotation_code,
                            $payment['account_name'] ?? null,
                            $interactive ? 180 : 160,
                            $payment['qr_template'] ?? null,
                        ) !!}
                        <div class="qdoc-muted" style="margin-top:4px;">{{ __('sales.pdf.scan_qr_payment') }}</div>
                    </td>
                </tr>
            </table>
        </div>
    @endif

    @if($quotation->terms_snapshot)
        <div class="qdoc-section-title">{{ __('sales.pdf.terms_conditions') }}</div>
        <div class="qdoc-box">
            <div>{{ __('sales.pdf.validity') }}: <strong>{{ $formatDate($quotation->terms_snapshot['valid_until'] ?? $quotation->valid_until) }}</strong></div>
            @if(!empty($quotation->terms_snapshot['payment_terms']))
                <div style="margin-top:7px;"><strong>{{ __('sales.pdf.payment_terms') }}:</strong></div>
                <div>{{ $quotation->terms_snapshot['payment_terms'] }}</div>
            @endif
            @if(!empty($quotation->terms_snapshot['vat_note']))
                <div style="margin-top:7px;"><strong>{{ __('sales.pdf.vat_note') }}:</strong></div>
                <div>{{ $quotation->terms_snapshot['vat_note'] }}</div>
            @endif
            @if(!empty($quotation->terms_snapshot['notes']))
                <div style="margin-top:7px;"><strong>{{ __('field.notes') }}:</strong> {{ $quotation->terms_snapshot['notes'] }}</div>
            @endif
        </div>
    @endif

    <table class="qdoc-sign-table" style="margin-top:20px;">
        <tr>
            <td>
                <div class="qdoc-sign-box">
                    <div class="qdoc-sign-title">{{ __('sales.pdf.company_representative') }}</div>
                    <div class="qdoc-sign-hint">{{ __('sales.public.prepared_by') }}</div>
                    <div class="qdoc-sign-name">{{ company_name() }}</div>
                </div>
            </td>
            <td>
                <div class="qdoc-sign-box">
                    <div class="qdoc-sign-title">{{ __('sales.public.customer_confirmation') }}</div>
                    <div class="qdoc-sign-hint">{{ __('sales.public.sign_and_stamp') }}</div>
                    @if($quotation->status === \App\Enums\Sales\QuotationStatus::Accepted && $quotation->confirmations->isNotEmpty())
                        @php $confirm = $quotation->confirmations->first(); @endphp
                        <div class="qdoc-sign-name">{{ $confirm->signer_name }}</div>
                        @if($confirm->signer_position)<div class="qdoc-muted">{{ $confirm->signer_position }}</div>@endif
                        <div class="qdoc-muted">{{ __('sales.public.confirmation_code') }}: {{ $confirm->confirmation_code }}</div>
                    @elseif(!empty($quotation->company_snapshot['company_name']))
                        <div class="qdoc-sign-name">{{ $quotation->company_snapshot['company_name'] }}</div>
                    @else
                        <div class="qdoc-sign-name">{{ $quotation->customer_snapshot['display_name'] ?? '' }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    @if($interactive && $quotation->status->canConfirm())
        <div class="qdoc-interactive bg-blue-50 border border-blue-200 rounded-lg p-4 no-print">
            <h3 class="font-semibold text-blue-800 mb-3">{{ __('sales.public.confirm_quotation') }}</h3>
            <p class="text-sm text-blue-700 mb-3">
                {{ __('sales.public.otp_protected_notice') }}
                <strong>{{ $quotation->authorized_signer_email }}</strong>.
            </p>
            <div class="flex flex-wrap gap-2">
                <button onclick="openOtpModal('accept')" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">{{ __('sales.public.confirm_electronic') }}</button>
                <button onclick="openOtpModal('reject')" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">{{ __('action.reject') }}</button>
                <button onclick="openOtpModal('revision')" class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600">{{ __('sales.public.request_revision') }}</button>
            </div>
        </div>
    @endif

    @if($interactive && $quotation->status === \App\Enums\Sales\QuotationStatus::Accepted)
        @php
            $paymentStatus = $quotation->payment_status?->value ?? (string) $quotation->payment_status;
            $pendingNotice = $quotation->paymentNotices
                ->first(fn ($notice) => ($notice->status?->value ?? (string) $notice->status) === 'pending');
        @endphp
        <div class="qdoc-interactive border rounded-lg p-4 no-print {{ $paymentStatus === 'paid' ? 'bg-green-50 border-green-200' : 'bg-amber-50 border-amber-200' }}">
            <h3 class="font-semibold mb-2 {{ $paymentStatus === 'paid' ? 'text-green-800' : 'text-amber-800' }}">{{ __('sales.public.payment_status') }}</h3>
            @if($paymentStatus === 'paid')
                <p class="text-sm text-green-700">{{ __('sales.public.payment_verified') }}</p>
            @elseif($pendingNotice)
                <p class="text-sm text-amber-700">{{ __('sales.public.payment_pending_reconciliation') }}</p>
                <p class="text-xs text-amber-700 mt-1">{{ __('sales.public.declared_amount') }}: <strong>{{ format_money($pendingNotice->declared_amount) }} {{ $quotation->currency }}</strong></p>
            @else
                <p class="text-sm text-amber-700 mb-3">{{ __('sales.public.payment_notice_instruction') }}</p>
                <form method="POST" action="{{ route('sales.quotation.public.notify-payment', ['quotationCode' => $quotation->quotation_code, 'token' => $quotation->public_token]) }}" onsubmit="return submitWithFreshCsrf(event, this)" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium">{{ __('sales.public.payer_name') }}</label>
                        <input name="payer_name" value="{{ old('payer_name', $quotation->authorized_signer_name) }}" required class="w-full border rounded px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">{{ __('field.email') }}</label>
                        <input name="payer_email" type="email" value="{{ $quotation->authorized_signer_email }}" readonly required class="w-full border rounded px-3 py-2 text-sm bg-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">{{ __('sales.public.transferred_amount') }}</label>
                        <input name="declared_amount" type="number" value="{{ (float) $quotation->grand_total }}" readonly required class="w-full border rounded px-3 py-2 text-sm bg-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">{{ __('sales.public.transfer_reference') }}</label>
                        <input name="transfer_reference" value="{{ old('transfer_reference') }}" class="w-full border rounded px-3 py-2 text-sm">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium">{{ __('field.notes') }}</label>
                        <textarea name="note" rows="2" class="w-full border rounded px-3 py-2 text-sm">{{ old('note') }}</textarea>
                    </div>
                    <div class="md:col-span-2">
                        <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">{{ __('sales.public.notify_payment') }}</button>
                    </div>
                </form>
            @endif
        </div>
    @endif

    <div class="qdoc-footer">
        @if($senderEmail !== '')
            <div>{{ __('sales.public.reply_email') }}: <a class="qdoc-contact-email" href="mailto:{{ $senderEmail }}">{{ $senderEmail }}</a></div>
        @elseif(company_email())
            <div>{{ __('field.email') }}: {{ company_email() }}</div>
        @endif
        <div>{{ __('sales.public.generated_note', ['company' => company_name()]) }}</div>
        <div>{{ __('sales.pdf.created_at', ['time' => $quotation->created_at?->format('d/m/Y H:i') ?? __('common.not_available'), 'company' => company_name()]) }}</div>
    </div>
</div>
