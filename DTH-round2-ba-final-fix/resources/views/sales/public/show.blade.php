<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $quotation->title }} - {{ $quotation->quotation_code }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="max-w-4xl mx-auto px-4 py-6 no-print sticky top-0 z-40 bg-white/95 backdrop-blur shadow">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                @if($quotation->status->canConfirm())
                    <button onclick="openOtpModal('accept')" class="px-3 py-2 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition">
                        {{ __('sales.public.confirm_electronic') }}
                    </button>
                @endif
                <button onclick="window.print()" class="px-3 py-2 bg-gray-700 text-white text-sm rounded hover:bg-gray-800 transition">
                    {{ __('sales.public.print_quotation') }}
                </button>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button onclick="copyLink()" class="px-3 py-2 border border-gray-300 bg-white text-gray-700 text-sm rounded hover:bg-gray-50 transition">
                    {{ __('action.copy_link') }}
                </button>
                <button onclick="shareLink()" class="px-3 py-2 border border-gray-300 bg-white text-gray-700 text-sm rounded hover:bg-gray-50 transition">
                    {{ __('sales.public.share') }}
                </button>
                <a href="{{ route('sales.quotation.public.pdf', ['quotationCode' => $quotation->quotation_code, 'token' => $quotation->public_token]) }}"
                   target="_blank" class="px-3 py-2 border border-gray-300 bg-white text-gray-700 text-sm rounded hover:bg-gray-50 transition">
                    {{ __('sales.public.view_pdf') }}
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 pb-10">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 no-print">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 no-print">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        @if($quotation->status === \App\Enums\Sales\QuotationStatus::Superseded)
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4 no-print">
                {{ __('sales.public.superseded_notice') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
            <div class="border-b-2 border-blue-600 px-6 py-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        @if(company_logo_url())
                            <img src="{{ company_logo_url() }}" alt="{{ company_name() }}" class="h-14 mb-2">
                        @endif
                        <h2 class="text-lg font-bold text-gray-900">{{ company_name() }}</h2>
                        @if(company_address())<p class="text-sm text-gray-600">{{ __('field.address') }}: {{ company_address() }}</p>@endif
                        @if(company_phone())<p class="text-sm text-gray-600">{{ __('sales.public.hotline') }}: {{ company_phone() }}</p>@endif
                        @if(company_email())<p class="text-sm text-gray-600">{{ __('field.email') }}: {{ company_email() }}</p>@endif
                    </div>
                    <div class="text-right">
                        <h1 class="text-3xl font-extrabold text-blue-600 tracking-wide">{{ __('sales.pdf.quotation_title') }}</h1>
                        <p class="text-sm text-gray-500 mt-1">{{ __('field.code') }}: <strong class="text-gray-800">{{ $quotation->quotation_code }}-V{{ $quotation->version }}</strong></p>
                        <p class="text-sm text-gray-500">{{ __('field.date') }}: {{ $quotation->quotation_date?->format('d/m/Y') }}</p>
                        <p class="text-sm text-gray-500">{{ __('field.effective_until') }}: {{ $quotation->valid_until?->format('d/m/Y') }}</p>
                        <p class="mt-2">
                            @if(in_array($quotation->status->value, ['viewed', 'accepted', 'rejected', 'expired', 'cancelled', 'revision_requested', 'superseded']))
                                <span class="inline-block px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-semibold rounded">{{ __('sales.public.status_viewed') }}</span>
                            @endif
                            <span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-600 text-xs font-semibold rounded">{{ $quotation->status->label() }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="px-6 py-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
                    <div class="border rounded-lg p-4 bg-gray-50">
                        <h3 class="font-semibold text-blue-600 text-sm mb-2">{{ __('sales.public.customer_info') }}</h3>
                        <p class="font-medium text-gray-900">{{ $quotation->customer_snapshot['display_name'] ?? '' }}</p>
                        @if(!empty($quotation->company_snapshot['company_name']))
                            <p class="text-sm text-gray-600">{{ $quotation->company_snapshot['company_name'] }}</p>
                            <p class="text-sm text-gray-600">{{ __('field.tax_code') }}: {{ $quotation->company_snapshot['tax_code'] ?? '' }}</p>
                        @endif
                        <p class="text-sm text-gray-600">{{ __('sales.public.contact_person') }}: {{ $quotation->customer_snapshot['contact_name'] ?? $quotation->customer_snapshot['display_name'] ?? '' }}</p>
                        @if(!empty($quotation->customer_snapshot['phone']))
                            <p class="text-sm text-gray-600">{{ __('field.phone') }}: {{ $quotation->customer_snapshot['phone'] }}</p>
                        @endif
                        @if(!empty($quotation->company_snapshot['company_address']))
                            <p class="text-sm text-gray-600">{{ __('field.address') }}: {{ $quotation->company_snapshot['company_address'] }}</p>
                        @endif
                        @if(!empty($quotation->customer_snapshot['email']))
                            <p class="text-sm text-gray-600">{{ __('field.email') }}: {{ $quotation->customer_snapshot['email'] }}</p>
                        @endif
                    </div>
                    <div class="border rounded-lg p-4 bg-gray-50">
                        <h3 class="font-semibold text-blue-600 text-sm mb-2">{{ __('sales.public.quotation_information') }}</h3>
                        @if($quotation->priceBook)
                            <p class="text-sm text-gray-600">{{ __('sales.public.price_channel') }}: {{ $quotation->priceBook->name }}</p>
                        @endif
                        <p class="text-sm text-gray-600">{{ __('field.date') }}: {{ $quotation->quotation_date?->format('d/m/Y') }}</p>
                        <p class="text-sm text-gray-600">{{ __('field.effective_until') }}: {{ $quotation->valid_until?->format('d/m/Y') }}</p>
                        @if($quotation->assignedStaff?->user)
                            <p class="text-sm text-gray-600">{{ __('sales.public.staff_in_charge') }}: {{ $quotation->assignedStaff->full_name }}</p>
                        @endif
                    </div>
                </div>

                <p class="text-sm text-gray-700 mb-5">
                    {{ __('sales.public.intro', ['company' => company_name(), 'title' => $quotation->title]) }}
                </p>

                <h3 class="font-semibold text-gray-800 mb-3">{{ __('sales.public.items_title') }}</h3>
                <table class="w-full border-collapse mb-5">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-2 text-left text-sm w-10">{{ __('sales.public.no') }}</th>
                            <th class="border p-2 text-left text-sm">{{ __('sales.public.service') }}</th>
                            <th class="border p-2 text-center text-sm w-16">{{ __('sales.public.unit') }}</th>
                            <th class="border p-2 text-center text-sm w-16">{{ __('sales.public.quantity') }}</th>
                            <th class="border p-2 text-right text-sm w-28">{{ __('sales.public.unit_price') }}</th>
                            <th class="border p-2 text-right text-sm w-24">{{ __('sales.public.discount') }}</th>
                            <th class="border p-2 text-center text-sm w-16">{{ __('sales.public.vat') }}</th>
                            <th class="border p-2 text-right text-sm w-28">{{ __('sales.public.line_total') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quotation->items as $i => $item)
                        <tr>
                            <td class="border p-2 text-sm">{{ $i + 1 }}</td>
                            <td class="border p-2 text-sm">
                                <strong>{{ $item->service_name_snapshot }}</strong>
                                @if($item->package_name_snapshot)<br><span class="text-gray-500 text-xs">{{ $item->package_name_snapshot }}</span>@endif
                            </td>
                            <td class="border p-2 text-center text-sm">{{ $item->unit }}</td>
                            <td class="border p-2 text-center text-sm">{{ number_format($item->quantity, $item->quantity == intval($item->quantity) ? 0 : 2) }}</td>
                            <td class="border p-2 text-right text-sm">{{ format_money($item->unit_price) }}</td>
                            <td class="border p-2 text-right text-sm">{{ $item->discount_amount > 0 ? format_money($item->discount_amount) : '-' }}</td>
                            <td class="border p-2 text-center text-sm">{{ $item->vat_rate > 0 ? $item->vat_rate.'%' : '-' }}</td>
                            <td class="border p-2 text-right text-sm font-medium">{{ format_money($item->line_total) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="flex justify-end mb-6">
                    <table class="text-sm">
                        <tr><td class="py-1 pr-8 text-gray-600">{{ __('sales.public.subtotal') }}:</td><td class="py-1 text-right">{{ format_money($quotation->subtotal) }}</td></tr>
                        @if($quotation->discount_total > 0)
                        <tr><td class="py-1 pr-8 text-gray-600">{{ __('sales.public.discount') }}:</td><td class="py-1 text-right text-red-600">-{{ format_money($quotation->discount_total) }}</td></tr>
                        @endif
                        <tr><td class="py-1 pr-8 text-gray-600">{{ __('sales.public.vat_tax') }}:</td><td class="py-1 text-right">{{ format_money($quotation->tax_total) }}</td></tr>
                        <tr class="border-t-2 border-blue-600"><td class="py-2 pr-8 font-bold text-blue-600 text-base">{{ __('sales.public.grand_total') }}:</td><td class="py-2 text-right font-bold text-blue-600 text-lg">{{ format_money($quotation->grand_total) }} {{ $quotation->currency }}</td></tr>
                    </table>
                </div>

                @php
                    $hasScope = false;
                    foreach ($quotation->items as $item) {
                        if (!empty($item->scope_snapshot)) { $hasScope = true; break; }
                    }
                    if (!empty($quotation->terms_snapshot['scope'])) { $hasScope = true; }
                @endphp
                @if($hasScope)
                <h3 class="font-semibold text-gray-800 mb-3">{{ __('sales.public.scope_of_supply') }}</h3>
                <table class="w-full border-collapse mb-5">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-2 text-left text-sm w-48">{{ __('sales.public.scope_item') }}</th>
                            <th class="border p-2 text-left text-sm">{{ __('sales.public.scope_content') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quotation->items as $item)
                            @if(!empty($item->scope_snapshot))
                            <tr>
                                <td class="border p-2 text-sm font-medium">{{ $item->service_name_snapshot }}</td>
                                <td class="border p-2 text-sm">{!! nl2br(e($item->scope_snapshot)) !!}</td>
                            </tr>
                            @endif
                        @endforeach
                        @if(!empty($quotation->terms_snapshot['scope']))
                        <tr>
                            <td class="border p-2 text-sm font-medium">{{ $quotation->title }}</td>
                            <td class="border p-2 text-sm">{!! nl2br(e($quotation->terms_snapshot['scope'])) !!}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
                @endif

                @php
                    // Public/PDF payment data must be immutable after approval/send.
                    // Never merge live BankAccount master data into a historical quote.
                    $payment = $quotation->payment_snapshot ?? [];
                @endphp
                @if(!empty($payment['bank_code']) && !empty($payment['account_number']))
                <h3 class="font-semibold text-gray-800 mb-3">{{ __('sales.pdf.payment_information') }}</h3>
                <div class="border rounded-lg p-4 mb-6 bg-gray-50">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                        <div class="text-sm space-y-1">
                            <p>{{ __('field.bank') }}: <strong>{{ $payment['bank_name'] ?? '' }}</strong> @if(!empty($payment['branch_name'])) - {{ $payment['branch_name'] }} @endif</p>
                            <p class="flex flex-wrap items-center gap-2">{{ __('field.account_number') }}: <strong>{{ $payment['account_number'] }}</strong>
                                <button onclick="copyText('{{ $payment['account_number'] }}')" class="text-xs px-2 py-0.5 border border-blue-300 text-blue-600 rounded hover:bg-blue-50">{{ __('action.copy') }}</button>
                            </p>
                            <p>{{ __('field.account_name') }}: {{ $payment['account_name'] ?? '' }}</p>
                            @if(!empty($payment['swift_code']))
                            <p>{{ __('field.swift_code') }}: {{ $payment['swift_code'] }}</p>
                            @endif
                            <p>{{ __('sales.public.transfer_amount') }}: <strong>{{ format_money($quotation->grand_total) }} {{ $quotation->currency }}</strong></p>
                            <p class="flex flex-wrap items-center gap-2">{{ __('sales.public.transfer_content') }}: <strong class="text-blue-600">{{ $payment['transfer_content'] ?? $quotation->quotation_code }}</strong>
                                <button onclick="copyText('{{ $payment['transfer_content'] ?? $quotation->quotation_code }}')" class="text-xs px-2 py-0.5 border border-blue-300 text-blue-600 rounded hover:bg-blue-50">{{ __('action.copy') }}</button>
                            </p>
                        </div>
                        <div class="text-center">
                            {!! app(\App\Services\Sales\QrPaymentService::class)->generateHtml(
                                $payment['bank_code'],
                                $payment['account_number'],
                                $quotation->grand_total,
                                $payment['transfer_content'] ?? $quotation->quotation_code,
                                $payment['account_name'] ?? null,
                                180,
                                $payment['qr_template'] ?? null,
                            ) !!}
                            <p class="text-xs text-gray-500 mt-1">{{ __('sales.pdf.scan_qr_payment') }}</p>
                        </div>
                    </div>
                </div>
                @endif

                @if($quotation->terms_snapshot)
                <h3 class="font-semibold text-gray-800 mb-3">{{ __('sales.pdf.terms_conditions') }}</h3>
                <div class="border rounded-lg p-4 mb-6 bg-gray-50 text-sm">
                    <p>{{ __('sales.pdf.validity') }}: <strong>{{ $quotation->terms_snapshot['valid_until'] ?? $quotation->valid_until?->format('d/m/Y') ?? __('common.not_available') }}</strong></p>
                    @if(!empty($quotation->terms_snapshot['payment_terms']))
                        <p class="mt-2"><strong>{{ __('sales.pdf.payment_terms') }}:</strong><br>{{ $quotation->terms_snapshot['payment_terms'] }}</p>
                    @endif
                    @if(!empty($quotation->terms_snapshot['vat_note']))
                        <p class="mt-2"><strong>{{ __('sales.pdf.vat_note') }}:</strong><br>{{ $quotation->terms_snapshot['vat_note'] }}</p>
                    @endif
                    @if(!empty($quotation->terms_snapshot['notes']))
                        <p class="mt-2"><strong>{{ __('field.notes') }}:</strong><br>{{ $quotation->terms_snapshot['notes'] }}</p>
                    @endif
                </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div class="text-center border rounded-lg p-6">
                        <h3 class="font-semibold text-gray-800 mb-2">{{ __('sales.pdf.company_representative') }}</h3>
                        <p class="text-xs text-gray-500 mb-10">{{ __('sales.public.prepared_by') }}</p>
                        <p class="font-medium text-gray-900 mt-8">{{ company_name() }}</p>
                    </div>
                    <div class="text-center border rounded-lg p-6">
                        <h3 class="font-semibold text-gray-800 mb-2">{{ __('sales.public.customer_confirmation') }}</h3>
                        <p class="text-xs text-gray-500 mb-10">{{ __('sales.public.sign_and_stamp') }}</p>
                        @if($quotation->status === \App\Enums\Sales\QuotationStatus::Accepted && $quotation->confirmations->isNotEmpty())
                            @php $confirm = $quotation->confirmations->first(); @endphp
                            <p class="font-medium text-gray-900 mt-8">{{ $confirm->signer_name }}</p>
                            <p class="text-xs text-gray-500">{{ $confirm->signer_position ?? '' }}</p>
                            <p class="text-xs text-gray-500 mt-2">{{ __('sales.public.confirmation_code') }}: {{ $confirm->confirmation_code }}</p>
                            @if($confirm->otp_verified_at)
                                <p class="text-xs text-green-600">{{ __('sales.public.otp_verified') }} {{ $confirm->otp_verified_at->format('d/m/Y H:i') }}</p>
                            @endif
                        @elseif(!empty($quotation->company_snapshot['company_name']))
                            <p class="font-medium text-gray-900 mt-8">{{ $quotation->company_snapshot['company_name'] }}</p>
                        @else
                            <p class="font-medium text-gray-900 mt-8">{{ $quotation->customer_snapshot['display_name'] ?? '' }}</p>
                        @endif
                    </div>
                </div>

                @if($quotation->status->canConfirm())
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 no-print">
                    <h3 class="font-semibold text-blue-800 mb-3">{{ __('sales.public.confirm_quotation') }}</h3>
                    <p class="text-sm text-blue-700 mb-3">
                        Xác nhận điện tử được bảo vệ bằng OTP gửi tới
                        <strong>{{ $quotation->authorized_signer_email }}</strong>.
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <button onclick="openOtpModal('accept')" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">{{ __('sales.public.confirm_electronic') }}</button>
                        <button onclick="openOtpModal('reject')" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">{{ __('action.reject') }}</button>
                        <button onclick="openOtpModal('revision')" class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600">{{ __('sales.public.request_revision') }}</button>
                    </div>
                </div>
                @endif

                @if($quotation->status === \App\Enums\Sales\QuotationStatus::Accepted)
                    @php
                        $paymentStatus = $quotation->payment_status?->value ?? (string) $quotation->payment_status;
                        $pendingNotice = $quotation->paymentNotices
                            ->first(fn ($notice) => ($notice->status?->value ?? (string) $notice->status) === 'pending');
                    @endphp
                    <div class="border rounded-lg p-4 mb-6 no-print {{ $paymentStatus === 'paid' ? 'bg-green-50 border-green-200' : 'bg-amber-50 border-amber-200' }}">
                        <h3 class="font-semibold mb-2 {{ $paymentStatus === 'paid' ? 'text-green-800' : 'text-amber-800' }}">Trạng thái thanh toán</h3>
                        @if($paymentStatus === 'paid')
                            <p class="text-sm text-green-700">Thanh toán đã được bộ phận Tài chính xác minh.</p>
                        @elseif($pendingNotice)
                            <p class="text-sm text-amber-700">Khách hàng đã thông báo chuyển khoản. Bộ phận Tài chính đang đối soát.</p>
                            <p class="text-xs text-amber-700 mt-1">Số tiền khai báo: <strong>{{ format_money($pendingNotice->declared_amount) }} {{ $quotation->currency }}</strong></p>
                        @else
                            <p class="text-sm text-amber-700 mb-3">Sau khi chuyển khoản theo QR/thông tin phía trên, hãy gửi thông báo để Tài chính đối soát. Thao tác này không tự động xác nhận đã thanh toán.</p>
                            <form method="POST" action="{{ route('sales.quotation.public.notify-payment', ['quotationCode' => $quotation->quotation_code, 'token' => $quotation->public_token]) }}" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @csrf
                                <div>
                                    <label class="block text-sm font-medium">Người chuyển khoản</label>
                                    <input name="payer_name" value="{{ old('payer_name', $quotation->authorized_signer_name) }}" required class="w-full border rounded px-3 py-2 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Email</label>
                                    <input name="payer_email" type="email" value="{{ $quotation->authorized_signer_email }}" readonly required class="w-full border rounded px-3 py-2 text-sm bg-gray-100">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Số tiền đã chuyển</label>
                                    <input name="declared_amount" type="number" value="{{ (float) $quotation->grand_total }}" readonly required class="w-full border rounded px-3 py-2 text-sm bg-gray-100">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium">Mã giao dịch / tham chiếu (nếu có)</label>
                                    <input name="transfer_reference" value="{{ old('transfer_reference') }}" class="w-full border rounded px-3 py-2 text-sm">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium">Ghi chú</label>
                                    <textarea name="note" rows="2" class="w-full border rounded px-3 py-2 text-sm">{{ old('note') }}</textarea>
                                </div>
                                <div class="md:col-span-2">
                                    <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">Thông báo đã chuyển khoản</button>
                                </div>
                            </form>
                        @endif
                    </div>
                @endif

                <div class="text-xs text-gray-400 border-t pt-4">
                    @if(company_email())<p>{{ __('field.email') }}: {{ company_email() }}</p>@endif
                    <p class="mt-1">{{ __('sales.public.generated_note', ['company' => company_name()]) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div id="otpModal" class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center hidden no-print z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-1" id="otpModalTitle">{{ __('sales.public.confirm_electronic') }}</h3>
            <p class="text-sm text-gray-500 mb-4">Mã OTP sẽ được gửi tới email đã được Sales chỉ định trước khi gửi báo giá.</p>
            <form id="otpForm" onsubmit="return false">
                @csrf
                <div class="space-y-3">
                    <div id="step1" class="space-y-3">
                        <div><label class="block text-sm font-medium">{{ __('field.full_name') }}</label><input id="otpSignerName" type="text" value="{{ $quotation->authorized_signer_name }}" required class="w-full border rounded px-3 py-2 text-sm"></div>
                        <div><label class="block text-sm font-medium">{{ __('field.email') }}</label><input id="otpEmail" type="email" value="{{ $quotation->authorized_signer_email }}" readonly required class="w-full border rounded px-3 py-2 text-sm bg-gray-100"></div>
                        <div id="otpExtraFields">
                            <div id="acceptFields" class="space-y-3">
                                <div><label class="block text-sm font-medium">{{ __('field.position') }}</label><input id="otpPosition" class="w-full border rounded px-3 py-2 text-sm"></div>
                                <div><label class="block text-sm font-medium">{{ __('field.phone') }}</label><input id="otpPhone" class="w-full border rounded px-3 py-2 text-sm"></div>
                            </div>
                            <div id="otpReasonField" class="hidden mt-3">
                                <label class="block text-sm font-medium">{{ __('field.reason') }}</label>
                                <textarea id="otpReason" rows="3" class="w-full border rounded px-3 py-2 text-sm"></textarea>
                            </div>
                        </div>
                        <button onclick="sendOtp()" id="sendOtpBtn" class="w-full px-4 py-2 bg-blue-600 text-white rounded text-sm">{{ __('sales.public.otp_send') }}</button>
                    </div>
                    <div id="step2" class="hidden space-y-3">
                        <p class="text-sm text-gray-600">{{ __('sales.public.otp_enter', ['email' => '']) }} <strong id="otpEmailShown"></strong></p>
                        <div><label class="block text-sm font-medium">{{ __('sales.public.otp_label') }}</label><input id="otpCode" type="text" maxlength="6" inputmode="numeric" class="w-full border rounded px-3 py-2 text-sm tracking-widest text-center text-lg" required></div>
                        <button onclick="verifyOtp()" id="verifyOtpBtn" class="w-full px-4 py-2 bg-green-600 text-white rounded text-sm">{{ __('sales.public.otp_verify') }}</button>
                        <button type="button" onclick="resetOtpForm()" class="w-full px-4 py-2 border rounded text-sm">{{ __('action.back') }}</button>
                    </div>
                </div>
            </form>
            <div id="otpError" class="hidden bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded text-sm mt-3"></div>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="hideOtpModal()" class="px-4 py-2 border rounded text-sm">{{ __('action.cancel') }}</button>
            </div>
        </div>
    </div>

    <div id="toast" class="fixed bottom-4 right-4 bg-gray-900 text-white text-sm px-4 py-3 rounded shadow-lg hidden z-50"></div>

    <script>
        var publicUrl = '{{ route("sales.quotation.public.show", ["quotationCode" => $quotation->quotation_code, "token" => $quotation->public_token]) }}';
        var otpAction = 'accept';
        var otpVerifiedEmail = null;

        function showToast(message) {
            var el = document.getElementById('toast');
            el.textContent = message;
            el.classList.remove('hidden');
            clearTimeout(showToast._t);
            showToast._t = setTimeout(function () { el.classList.add('hidden'); }, 3000);
        }

        function getCsrf() {
            var meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        }

        function copyText(text) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function () { showToast('{{ __("sales.public.copied") }}'); });
            } else {
                var ta = document.createElement('textarea');
                ta.value = text;
                document.body.appendChild(ta);
                ta.select();
                document.execCommand('copy');
                document.body.removeChild(ta);
                showToast('{{ __("sales.public.copied") }}');
            }
        }

        function copyLink() { copyText(publicUrl); }

        function shareLink() {
            if (navigator.share) {
                navigator.share({ title: document.title, url: publicUrl }).catch(function () {});
            } else { copyText(publicUrl); }
        }

        function openOtpModal(action) {
            otpAction = action || 'accept';
            resetOtpForm();
            var title = document.getElementById('otpModalTitle');
            title.textContent = otpAction === 'accept'
                ? '{{ __("sales.public.confirm_electronic") }}'
                : (otpAction === 'reject' ? '{{ __("sales.public.reject_quotation") }}' : '{{ __("sales.public.request_revision") }}');
            document.getElementById('acceptFields').classList.toggle('hidden', otpAction !== 'accept');
            document.getElementById('otpReasonField').classList.toggle('hidden', otpAction === 'accept');
            document.getElementById('otpReason').required = otpAction !== 'accept';
            document.getElementById('otpModal').classList.add('flex');
            document.getElementById('otpModal').classList.remove('hidden');
        }

        function hideOtpModal() {
            document.getElementById('otpModal').classList.remove('flex');
            document.getElementById('otpModal').classList.add('hidden');
        }

        function resetOtpForm() {
            otpVerifiedEmail = null;
            document.getElementById('step1').classList.remove('hidden');
            document.getElementById('step2').classList.add('hidden');
            document.getElementById('otpError').classList.add('hidden');
            document.getElementById('otpCode').value = '';
            document.getElementById('sendOtpBtn').disabled = false;
            document.getElementById('verifyOtpBtn').disabled = false;
        }

        function showOtpError(message) {
            var el = document.getElementById('otpError');
            el.textContent = message;
            el.classList.remove('hidden');
        }

        function sendOtp() {
            var email = document.getElementById('otpEmail').value.trim();
            var name = document.getElementById('otpSignerName').value.trim();
            var reason = document.getElementById('otpReason').value.trim();
            if (!email || !name || (otpAction !== 'accept' && !reason)) {
                showOtpError('{{ __("sales.public.otp_fill_form") }}');
                return;
            }

            var btn = document.getElementById('sendOtpBtn');
            btn.disabled = true;
            fetch('{{ route("sales.quotation.public.send-otp", ["quotationCode" => $quotation->quotation_code, "token" => $quotation->public_token]) }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': getCsrf(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email }),
            }).then(function (res) {
                return res.json().then(function (data) { return { ok: res.ok, data: data }; });
            }).then(function (result) {
                if (result.ok) {
                    document.getElementById('otpEmailShown').textContent = email;
                    document.getElementById('step1').classList.add('hidden');
                    document.getElementById('step2').classList.remove('hidden');
                } else {
                    btn.disabled = false;
                    var errors = result.data.errors || {};
                    showOtpError(result.data.message || (errors.otp ? errors.otp[0] : '{{ __("sales.public.error_occurred") }}'));
                }
            }).catch(function () {
                btn.disabled = false;
                showOtpError('{{ __("sales.public.error_occurred") }}');
            });
        }

        function verifyOtp() {
            var email = document.getElementById('otpEmail').value.trim();
            var otp = document.getElementById('otpCode').value.trim();
            if (!/^\d{6}$/.test(otp)) { showOtpError('{{ __("sales.public.otp_invalid_format") }}'); return; }

            var btn = document.getElementById('verifyOtpBtn');
            btn.disabled = true;
            fetch('{{ route("sales.quotation.public.verify-otp", ["quotationCode" => $quotation->quotation_code, "token" => $quotation->public_token]) }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': getCsrf(), 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email, otp: otp }),
            }).then(function (res) {
                return res.json().then(function (data) { return { ok: res.ok, data: data }; });
            }).then(function (result) {
                if (result.ok) { otpVerifiedEmail = email; submitElectronicAction(); }
                else {
                    btn.disabled = false;
                    var errors = result.data.errors || {};
                    showOtpError(result.data.message || (errors.otp ? errors.otp[0] : '{{ __("sales.public.otp_invalid") }}'));
                }
            }).catch(function () { btn.disabled = false; showOtpError('{{ __("sales.public.error_occurred") }}'); });
        }

        function submitElectronicAction() {
            var routeMap = {
                accept: '{{ route("sales.quotation.public.accept", ["quotationCode" => $quotation->quotation_code, "token" => $quotation->public_token]) }}',
                reject: '{{ route("sales.quotation.public.reject", ["quotationCode" => $quotation->quotation_code, "token" => $quotation->public_token]) }}',
                revision: '{{ route("sales.quotation.public.request-revision", ["quotationCode" => $quotation->quotation_code, "token" => $quotation->public_token]) }}'
            };
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = routeMap[otpAction];
            form.style.display = 'none';
            var fields = {
                signer_name: document.getElementById('otpSignerName').value.trim(),
                signer_email: document.getElementById('otpEmail').value.trim(),
                otp_email: otpVerifiedEmail,
                _token: getCsrf()
            };
            if (otpAction === 'accept') {
                fields.signer_position = document.getElementById('otpPosition').value.trim();
                fields.signer_phone = document.getElementById('otpPhone').value.trim();
            } else {
                fields.reason = document.getElementById('otpReason').value.trim();
            }
            Object.keys(fields).forEach(function (name) {
                var input = document.createElement('input');
                input.type = 'hidden'; input.name = name; input.value = fields[name] || ''; form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
