<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $quotation->title }} - {{ $quotation->quotation_code }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body class="bg-gray-50">
    <div class="max-w-4xl mx-auto p-4 sm:p-6">
        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 no-print">{{ session('success') }}</div>
        @endif

        @if($quotation->status === 'superseded')
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4 no-print">
                {{ __('sales.public.superseded_notice') }}
            </div>
        @endif

        @if($quotation->status->canConfirm())
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 no-print">
                <h3 class="font-semibold text-blue-800 mb-3">{{ __('sales.public.confirm_quotation') }}</h3>
                <div class="flex flex-wrap gap-2">
                    <button onclick="showConfirmForm('accept')" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">{{ __('action.accept') }}</button>
                    <button onclick="showConfirmForm('reject')" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">{{ __('action.reject') }}</button>
                    <button onclick="showConfirmForm('revision')" class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600">{{ __('sales.public.request_revision') }}</button>
                </div>
            </div>
        @endif

        @if($quotation->status === 'accepted' && $quotation->confirmations->isNotEmpty())
            @php $confirm = $quotation->confirmations->first(); @endphp
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-green-800 mb-2">{{ __('sales.public.confirmed_check') }}</h3>
                <p class="text-sm">{{ __('sales.public.confirmed_by') }}: <strong>{{ $confirm->signer_name }}</strong></p>
                <p class="text-sm">{{ __('field.email') }}: {{ $confirm->signer_email }}</p>
                <p class="text-sm">{{ __('field.time') }}: {{ $confirm->confirmed_at?->format('d/m/Y H:i') }}</p>
                <p class="text-sm">{{ __('sales.public.confirmation_code') }}: {{ $confirm->confirmation_code }}</p>
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <div class="text-center border-b pb-4 mb-4">
                <h1 class="text-2xl font-bold text-blue-600">{{ config('app.name') }}</h1>
                <p class="text-lg font-semibold mt-2">{{ $quotation->title }}</p>
                <p class="text-gray-500">{{ __('field.code') }}: {{ $quotation->quotation_code }}-V{{ $quotation->version }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="border rounded p-3">
                    <h3 class="font-semibold text-blue-600 text-sm mb-1">{{ __('field.customer') }}</h3>
                    <p class="font-medium">{{ $quotation->customer_snapshot['display_name'] ?? '' }}</p>
                    @if(!empty($quotation->company_snapshot['company_name']))
                        <p class="text-sm">{{ $quotation->company_snapshot['company_name'] }}</p>
                        <p class="text-sm">{{ __('field.tax_code') }}: {{ $quotation->company_snapshot['tax_code'] ?? '' }}</p>
                    @endif
                    <p class="text-sm">{{ __('field.email') }}: {{ $quotation->customer_snapshot['email'] ?? '' }}</p>
                </div>
                <div class="border rounded p-3">
                    <h3 class="font-semibold text-blue-600 text-sm mb-1">{{ __('sales.public.quotation_information') }}</h3>
                    <p class="text-sm">{{ __('field.date') }}: {{ $quotation->quotation_date?->format('d/m/Y') }}</p>
                    <p class="text-sm">{{ __('field.effective_until') }}: {{ $quotation->valid_until?->format('d/m/Y') }}</p>
                    <p class="text-sm">{{ __('field.status') }}: {{ $quotation->status->label() }}</p>
                </div>
            </div>

            @if(!empty($quotation->company_snapshot['company_address']))
            <div class="border rounded p-3 mb-6 text-sm">
                <strong>{{ __('field.address') }}:</strong> {{ $quotation->company_snapshot['company_address'] }}
            </div>
            @endif

            <table class="w-full border-collapse mb-6">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border p-2 text-left text-sm">{{ __('sales.public.no') }}</th>
                        <th class="border p-2 text-left text-sm">{{ __('sales.public.service') }}</th>
                        <th class="border p-2 text-center text-sm">{{ __('sales.public.unit') }}</th>
                        <th class="border p-2 text-center text-sm">{{ __('sales.public.quantity') }}</th>
                        <th class="border p-2 text-right text-sm">{{ __('sales.public.unit_price') }}</th>
                        <th class="border p-2 text-right text-sm">{{ __('sales.public.discount') }}</th>
                        <th class="border p-2 text-right text-sm">{{ __('sales.public.vat') }}</th>
                        <th class="border p-2 text-right text-sm">{{ __('sales.public.line_total') }}</th>
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
                        <td class="border p-2 text-center text-sm">{{ $item->quantity }}</td>
                        <td class="border p-2 text-right text-sm">{{ number_format($item->unit_price, 0) }}</td>
                        <td class="border p-2 text-right text-sm">{{ $item->discount_amount > 0 ? number_format($item->discount_amount, 0) : '-' }}</td>
                        <td class="border p-2 text-right text-sm">{{ $item->vat_rate > 0 ? $item->vat_rate.'%' : '-' }}</td>
                        <td class="border p-2 text-right text-sm font-medium">{{ number_format($item->line_total, 0) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="flex justify-end mb-6">
                <table class="text-sm">
                    <tr><td class="py-1 pr-8">{{ __('sales.public.subtotal') }}:</td><td class="py-1 text-right">{{ number_format($quotation->subtotal, 0) }}</td></tr>
                    @if($quotation->discount_total > 0)
                    <tr><td class="py-1 pr-8">{{ __('sales.public.discount') }}:</td><td class="py-1 text-right text-red-600">-{{ number_format($quotation->discount_total, 0) }}</td></tr>
                    @endif
                    <tr><td class="py-1 pr-8">{{ __('sales.public.vat_tax') }}:</td><td class="py-1 text-right">{{ number_format($quotation->tax_total, 0) }}</td></tr>
                    <tr class="border-t-2 border-blue-600"><td class="py-2 pr-8 font-bold text-blue-600">{{ __('sales.public.grand_total') }}:</td><td class="py-2 text-right font-bold text-blue-600 text-lg">{{ number_format($quotation->grand_total, 0) }} {{ $quotation->currency }}</td></tr>
                </table>
            </div>

            @if($quotation->payment_snapshot)
            <div class="border rounded p-4 mb-6">
                <h3 class="font-semibold text-blue-600 mb-2">{{ __('sales.public.payment_information') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                    <p>{{ __('field.bank') }}: <strong>{{ $quotation->payment_snapshot['bank_name'] ?? '' }}</strong></p>
                    <p>{{ __('field.account_number') }}: <strong>{{ $quotation->payment_snapshot['account_number'] ?? '' }}</strong></p>
                    <p>{{ __('field.account_name') }}: {{ $quotation->payment_snapshot['account_name'] ?? '' }}</p>
                    <p>{{ __('field.branch_name') }}: {{ $quotation->payment_snapshot['branch_name'] ?? '' }}</p>
                    <p class="md:col-span-2">{{ __('sales.public.transfer_content') }}: <strong class="text-blue-600">{{ $quotation->payment_snapshot['transfer_content'] ?? $quotation->quotation_code }}</strong></p>
                </div>
            </div>
            @endif

            @if($quotation->terms_snapshot)
            <div class="border rounded p-4 mb-6 text-sm">
                <h3 class="font-semibold text-blue-600 mb-2">{{ __('field.terms') }}</h3>
                <p>{{ __('field.effective_until') }}: {{ $quotation->terms_snapshot['valid_until'] ?? $quotation->valid_until?->format('d/m/Y') }}</p>
                @if(!empty($quotation->terms_snapshot['scope']))<p class="mt-2"><strong>{{ __('field.scope') }}:</strong><br>{{ $quotation->terms_snapshot['scope'] }}</p>@endif
                @if(!empty($quotation->terms_snapshot['notes']))<p class="mt-2"><strong>{{ __('field.notes') }}:</strong><br>{{ $quotation->terms_snapshot['notes'] }}</p>@endif
            </div>
            @endif

            <div class="text-right text-sm text-gray-500 pt-4 border-t no-print">
                <a href="{{ route('sales.quotation.public.pdf', ['quotationCode' => $quotation->quotation_code, 'token' => $quotation->public_token]) }}" class="text-blue-600 hover:underline">{{ __('sales.public.download_pdf') }}</a>
                | <button onclick="window.print()" class="text-blue-600 hover:underline">{{ __('sales.public.print_quotation') }}</button>
            </div>
        </div>
    </div>

    <div id="confirmModal" class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center hidden no-print z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-4" id="modalTitle">{{ __('sales.public.confirm') }}</h3>
            <form id="confirmForm" method="POST">
                @csrf
                <input type="hidden" name="action" id="confirmAction">
                <div class="space-y-3">
                    <div><label class="block text-sm font-medium">{{ __('field.full_name') }}</label><input name="signer_name" required class="w-full border rounded px-3 py-2 text-sm"></div>
                    <div><label class="block text-sm font-medium">{{ __('field.email') }}</label><input name="signer_email" type="email" required class="w-full border rounded px-3 py-2 text-sm"></div>
                    <div id="positionField"><label class="block text-sm font-medium">{{ __('field.position') }}</label><input name="signer_position" class="w-full border rounded px-3 py-2 text-sm"></div>
                    <div id="phoneField"><label class="block text-sm font-medium">{{ __('field.phone') }}</label><input name="signer_phone" class="w-full border rounded px-3 py-2 text-sm"></div>
                    <div id="reasonField" class="hidden"><label class="block text-sm font-medium">{{ __('field.reason') }}</label><textarea name="reason" rows="3" class="w-full border rounded px-3 py-2 text-sm"></textarea></div>
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" onclick="hideConfirmForm()" class="px-4 py-2 border rounded text-sm">{{ __('action.cancel') }}</button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded text-sm">{{ __('action.send') }}</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showConfirmForm(action) {
            const modal = document.getElementById('confirmModal');
            const form = document.getElementById('confirmForm');
            const title = document.getElementById('modalTitle');
            const reasonField = document.getElementById('reasonField');
            const positionField = document.getElementById('positionField');
            const phoneField = document.getElementById('phoneField');

            form.action = action === 'accept' ? '{{ route("sales.quotation.public.accept", ["quotationCode" => $quotation->quotation_code, "token" => $quotation->public_token]) }}'
                : action === 'reject' ? '{{ route("sales.quotation.public.reject", ["quotationCode" => $quotation->quotation_code, "token" => $quotation->public_token]) }}'
                : '{{ route("sales.quotation.public.request-revision", ["quotationCode" => $quotation->quotation_code, "token" => $quotation->public_token]) }}';

            title.textContent = action === 'accept' ? '{{ __('sales.public.accept_quotation') }}' : action === 'reject' ? '{{ __('sales.public.reject_quotation') }}' : '{{ __('sales.public.request_revision') }}';
            reasonField.classList.toggle('hidden', action === 'accept');
            positionField.classList.toggle('hidden', action !== 'accept');
            phoneField.classList.toggle('hidden', action !== 'accept');
            modal.classList.add('flex');
            modal.classList.remove('hidden');
        }

        function hideConfirmForm() {
            const modal = document.getElementById('confirmModal');
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    </script>
</body>
</html>
