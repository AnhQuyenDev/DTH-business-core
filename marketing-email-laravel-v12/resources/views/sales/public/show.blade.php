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
            body { background: #fff !important; }
            .quotation-shell { box-shadow: none !important; border: 0 !important; }
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

        <div class="quotation-shell bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden p-6">
            @include('sales.partials.quotation-document', [
                'quotation' => $quotation,
                'interactive' => true,
            ])
        </div>
    </div>

    <div id="otpModal" class="fixed inset-0 bg-black bg-opacity-50 items-center justify-center hidden no-print z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-1" id="otpModalTitle">{{ __('sales.public.confirm_electronic') }}</h3>
            <p class="text-sm text-gray-500 mb-4">{{ __('sales.public.otp_modal_notice') }}</p>
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
        var csrfUrl = '{{ route("sales.quotation.public.csrf-token", ["quotationCode" => $quotation->quotation_code, "token" => $quotation->public_token]) }}';
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

        async function refreshCsrfToken() {
            var response = await fetch(csrfUrl, {
                method: 'GET',
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store'
            });

            if (!response.ok) {
                throw new Error('Unable to refresh CSRF token');
            }

            var data = await response.json();
            var meta = document.querySelector('meta[name="csrf-token"]');

            if (!data.csrf_token || !meta) {
                throw new Error('Missing CSRF token');
            }

            meta.setAttribute('content', data.csrf_token);
            return data.csrf_token;
        }

        async function submitWithFreshCsrf(event, form) {
            event.preventDefault();

            if (form.dataset.submitting === '1') {
                return false;
            }

            form.dataset.submitting = '1';

            try {
                var token = await refreshCsrfToken();
                var tokenInput = form.querySelector('input[name="_token"]');
                if (tokenInput) { tokenInput.value = token; }
                form.submit();
            } catch (error) {
                form.dataset.submitting = '0';
                showToast('{{ __("sales.public.error_occurred") }}');
            }

            return false;
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

        async function sendOtp() {
            var email = document.getElementById('otpEmail').value.trim();
            var name = document.getElementById('otpSignerName').value.trim();
            var reason = document.getElementById('otpReason').value.trim();
            if (!email || !name || (otpAction !== 'accept' && !reason)) {
                showOtpError('{{ __("sales.public.otp_fill_form") }}');
                return;
            }

            var btn = document.getElementById('sendOtpBtn');
            btn.disabled = true;

            try {
                var csrf = await refreshCsrfToken();
                var res = await fetch('{{ route("sales.quotation.public.send-otp", ["quotationCode" => $quotation->quotation_code, "token" => $quotation->public_token]) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ email: email }),
                });
                var data = await res.json();

                if (res.ok) {
                    document.getElementById('otpEmailShown').textContent = email;
                    document.getElementById('step1').classList.add('hidden');
                    document.getElementById('step2').classList.remove('hidden');
                    document.getElementById('otpError').classList.add('hidden');
                    return;
                }

                btn.disabled = false;
                var errors = data.errors || {};
                showOtpError(data.message || (errors.otp ? errors.otp[0] : '{{ __("sales.public.error_occurred") }}'));
            } catch (error) {
                btn.disabled = false;
                showOtpError('{{ __("sales.public.error_occurred") }}');
            }
        }

        async function verifyOtp() {
            var email = document.getElementById('otpEmail').value.trim();
            var otp = document.getElementById('otpCode').value.trim();
            if (!/^\d{6}$/.test(otp)) { showOtpError('{{ __("sales.public.otp_invalid_format") }}'); return; }

            var btn = document.getElementById('verifyOtpBtn');
            btn.disabled = true;

            try {
                var csrf = await refreshCsrfToken();
                var res = await fetch('{{ route("sales.quotation.public.verify-otp", ["quotationCode" => $quotation->quotation_code, "token" => $quotation->public_token]) }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ email: email, otp: otp }),
                });
                var data = await res.json();

                if (res.ok) {
                    otpVerifiedEmail = email;
                    await submitElectronicAction();
                    return;
                }

                btn.disabled = false;
                var errors = data.errors || {};
                showOtpError(data.message || (errors.otp ? errors.otp[0] : '{{ __("sales.public.otp_invalid") }}'));
            } catch (error) {
                btn.disabled = false;
                showOtpError('{{ __("sales.public.error_occurred") }}');
            }
        }

        async function submitElectronicAction() {
            var routeMap = {
                accept: '{{ route("sales.quotation.public.accept", ["quotationCode" => $quotation->quotation_code, "token" => $quotation->public_token]) }}',
                reject: '{{ route("sales.quotation.public.reject", ["quotationCode" => $quotation->quotation_code, "token" => $quotation->public_token]) }}',
                revision: '{{ route("sales.quotation.public.request-revision", ["quotationCode" => $quotation->quotation_code, "token" => $quotation->public_token]) }}'
            };

            try {
                var csrf = await refreshCsrfToken();
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = routeMap[otpAction];
                form.style.display = 'none';
                var fields = {
                    signer_name: document.getElementById('otpSignerName').value.trim(),
                    signer_email: document.getElementById('otpEmail').value.trim(),
                    otp_email: otpVerifiedEmail,
                    _token: csrf
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
            } catch (error) {
                document.getElementById('verifyOtpBtn').disabled = false;
                showOtpError('{{ __("sales.public.error_occurred") }}');
            }
        }
    </script>
</body>
</html>
