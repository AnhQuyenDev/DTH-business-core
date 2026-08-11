<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Jobs\Sales\SendQuotationAcceptedNotificationJob;
use App\Jobs\Sales\SendQuotationRejectedNotificationJob;
use App\Jobs\Sales\SendQuotationRevisionRequestedNotificationJob;
use App\Mail\Sales\QuotationOtpMail;
use App\Services\Marketing\SendingAccountMailerService;
use App\Services\Business\WorkflowPolicyService;
use App\Services\Sales\QuotationConfirmationService;
use App\Services\Sales\QuotationPaymentNoticeService;
use App\Services\Sales\QuotationPdfService;
use App\Services\Sales\QuotationPublicAccessService;
use App\Services\Sales\QuotationSendingAccountResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class QuotationPublicController extends Controller
{
    public function __construct(
        private readonly QuotationPublicAccessService $access,
        private readonly WorkflowPolicyService $workflowPolicy,
    ) {
        $this->middleware('throttle:30,1');
    }

    public function show(string $quotationCode, string $token)
    {
        $quotation = $this->findOrFail($quotationCode, $token);

        $this->access->trackView($quotation);

        return view('sales.public.show', compact('quotation'));
    }

    public function markViewed(string $quotationCode, string $token): RedirectResponse
    {
        $quotation = $this->findOrFail($quotationCode, $token);

        $this->access->trackView($quotation);

        return back()->with('success', __('sales.public.marked_viewed'));
    }

    public function pdf(string $quotationCode, string $token)
    {
        $quotation = $this->findOrFail($quotationCode, $token);

        $this->access->trackView($quotation);

        $doc = app(QuotationPdfService::class)->generate($quotation);

        return Storage::disk('local')->response($doc->file_path, $doc->file_name);
    }

    /**
     * Return a fresh CSRF token for long-lived public quotation tabs.
     *
     * Sales users may log in/out in another tab while the customer-facing page
     * remains open. Refreshing the token immediately before a write prevents a
     * stale page from failing with HTTP 419 / CSRF token mismatch.
     */
    public function csrfToken(string $quotationCode, string $token): JsonResponse
    {
        $this->findOrFail($quotationCode, $token);

        return response()->json([
            'csrf_token' => csrf_token(),
        ]);
    }

    public function accept(
        Request $request,
        string $quotationCode,
        string $token,
    ): RedirectResponse {
        $quotation = $this->findOrFail($quotationCode, $token);

        $validated = $request->validate([
            'signer_name' => 'required|string|max:255',
            'signer_position' => 'nullable|string|max:255',
            'signer_email' => 'required|email|max:255',
            'signer_phone' => 'nullable|string|max:30',
            'otp_email' => $this->usesOtpConfirmation() ? 'required|email|max:255' : 'nullable|email|max:255',
        ]);

        [$verifiedEmail, $otpKey] = $this->resolvePublicConfirmation(
            $quotation,
            (string) $validated['signer_email'],
            (string) ($validated['otp_email'] ?? ''),
        );

        $validated['signer_email'] = $verifiedEmail;
        $validated['verification_method'] = $this->usesOtpConfirmation()
            ? 'email_otp'
            : 'public_link';

        $result = app(QuotationConfirmationService::class)->accept(
            $quotation,
            $validated,
            $otpKey !== null ? $verifiedEmail : null,
        );

        if ($otpKey !== null) {
            $this->access->consumeOtpVerification($otpKey);
        }

        SendQuotationAcceptedNotificationJob::dispatchSync($result);

        return $this->redirectToPublicQuotation(
            $quotationCode,
            $token,
            __('sales.public.accept_success'),
        );
    }

    public function reject(
        Request $request,
        string $quotationCode,
        string $token,
    ): RedirectResponse {
        $quotation = $this->findOrFail($quotationCode, $token);

        $validated = $request->validate([
            'signer_name' => 'required|string|max:255',
            'signer_email' => 'required|email|max:255',
            'otp_email' => $this->usesOtpConfirmation() ? 'required|email|max:255' : 'nullable|email|max:255',
            'reason' => 'required|string|max:1000',
        ]);

        [$verifiedEmail, $otpKey] = $this->resolvePublicConfirmation(
            $quotation,
            (string) $validated['signer_email'],
            (string) ($validated['otp_email'] ?? ''),
        );

        $validated['signer_email'] = $verifiedEmail;
        $validated['verification_method'] = $this->usesOtpConfirmation()
            ? 'email_otp'
            : 'public_link';

        $result = app(QuotationConfirmationService::class)->reject(
            $quotation,
            $validated,
            $otpKey !== null ? $verifiedEmail : null,
        );

        if ($otpKey !== null) {
            $this->access->consumeOtpVerification($otpKey);
        }

        SendQuotationRejectedNotificationJob::dispatchSync(
            $result,
            (string) $validated['reason'],
        );

        return $this->redirectToPublicQuotation(
            $quotationCode,
            $token,
            __('sales.public.reject_success'),
        );
    }

    public function requestRevision(
        Request $request,
        string $quotationCode,
        string $token,
    ): RedirectResponse {
        $quotation = $this->findOrFail($quotationCode, $token);

        $validated = $request->validate([
            'signer_name' => 'required|string|max:255',
            'signer_email' => 'required|email|max:255',
            'otp_email' => $this->usesOtpConfirmation() ? 'required|email|max:255' : 'nullable|email|max:255',
            'reason' => 'required|string|max:1000',
        ]);

        [$verifiedEmail, $otpKey] = $this->resolvePublicConfirmation(
            $quotation,
            (string) $validated['signer_email'],
            (string) ($validated['otp_email'] ?? ''),
        );

        $validated['signer_email'] = $verifiedEmail;
        $validated['verification_method'] = $this->usesOtpConfirmation()
            ? 'email_otp'
            : 'public_link';

        $result = app(QuotationConfirmationService::class)->requestRevision(
            $quotation,
            $validated,
            $otpKey !== null ? $verifiedEmail : null,
        );

        if ($otpKey !== null) {
            $this->access->consumeOtpVerification($otpKey);
        }

        SendQuotationRevisionRequestedNotificationJob::dispatchSync(
            $result,
            (string) $validated['reason'],
        );

        return $this->redirectToPublicQuotation(
            $quotationCode,
            $token,
            __('sales.public.revision_success'),
        );
    }

    public function sendOtp(
        Request $request,
        string $quotationCode,
        string $token,
    ): JsonResponse {
        $quotation = $this->findOrFail($quotationCode, $token);
        $this->assertCanConfirm($quotation);
        $this->assertOtpModeEnabled();

        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = $this->access->assertAuthorizedSignerEmail(
            $quotation,
            (string) $validated['email'],
        );
        $key = $this->access->otpKey($quotation, $email);

        $this->access->checkOtpSendThrottle($key);

        $otp = $this->access->generateOtp();

        try {
            $preferredSendingAccountId = $quotation->emailLogs()
                ->whereNotNull('sending_account_id')
                ->latest('id')
                ->value('sending_account_id');

            $sendingAccount = app(QuotationSendingAccountResolver::class)
                ->resolve(
                    quotation: $quotation,
                    preferredSendingAccountId: $preferredSendingAccountId
                        ? (int) $preferredSendingAccountId
                        : null,
                );

            app(SendingAccountMailerService::class)->send(
                account: $sendingAccount,
                recipientEmail: $email,
                mailable: new QuotationOtpMail($quotationCode, $otp, 5),
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Không thể gửi mã OTP. Vui lòng liên hệ nhân viên phụ trách báo giá.',
            ], 500);
        }

        // Only store an OTP that was actually sent successfully.
        $this->access->storeOtp($key, $otp);
        $this->access->hitOtpSendThrottle($key);

        return response()->json([
            'message' => __('sales.public.otp_sent'),
        ]);
    }

    public function verifyOtp(
        Request $request,
        string $quotationCode,
        string $token,
    ): JsonResponse {
        $quotation = $this->findOrFail($quotationCode, $token);
        $this->assertCanConfirm($quotation);
        $this->assertOtpModeEnabled();

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'otp' => 'required|string|size:6|regex:/^\\d{6}$/',
        ]);

        $email = $this->access->assertAuthorizedSignerEmail(
            $quotation,
            (string) $validated['email'],
        );
        $key = $this->access->otpKey($quotation, $email);

        if (! $this->access->verifyOtp($key, (string) $validated['otp'])) {
            throw ValidationException::withMessages([
                'otp' => __('sales.public.otp_invalid'),
            ]);
        }

        return response()->json([
            'message' => __('sales.public.otp_verified'),
        ]);
    }

    public function notifyPayment(
        Request $request,
        string $quotationCode,
        string $token,
    ): RedirectResponse {
        $quotation = $this->findOrFail($quotationCode, $token);

        $validated = $request->validate([
            'payer_name' => 'required|string|max:255',
            'payer_email' => 'required|email|max:255',
            'declared_amount' => 'required|numeric|min:0.01',
            'transfer_reference' => 'nullable|string|max:255',
            'proof_files' => ($this->paymentEvidenceRequired() ? 'required' : 'nullable').'|array|max:'.max(1, (int) config('finance.evidence_max_files', 3)),
            'proof_files.*' => 'nullable|file|mimes:'.implode(',', (array) config('finance.evidence_mimes', ['jpg', 'jpeg', 'png', 'webp', 'pdf'])).'|max:'.max(1, (int) config('finance.evidence_max_kb', 10240)),
            'note' => 'nullable|string|max:2000',
        ]);

        app(QuotationPaymentNoticeService::class)->submit(
            $quotation,
            $validated,
            $request,
        );

        return $this->redirectToPublicQuotation(
            $quotationCode,
            $token,
            __('sales.public.payment_notice_submitted'),
        );
    }

    public function receipt(string $quotationCode, string $token)
    {
        $quotation = $this->findOrFail($quotationCode, $token);
        $quotation->loadMissing('payment.receipt');

        $receipt = $quotation->payment?->receipt;

        if ($receipt === null || ! Storage::disk($receipt->disk)->exists($receipt->file_path)) {
            abort(404);
        }

        return Storage::disk($receipt->disk)->response(
            $receipt->file_path,
            $receipt->file_name,
            ['Content-Type' => $receipt->mime_type],
        );
    }

    private function findOrFail(string $quotationCode, string $token)
    {
        $quotation = $this->access->findQuotation($quotationCode, $token);

        if (! $quotation) {
            abort(404);
        }

        return $quotation;
    }

    private function assertCanConfirm($quotation): void
    {
        if (! $quotation->status->canConfirm()) {
            throw ValidationException::withMessages([
                'confirmation' => 'Báo giá hiện không còn ở trạng thái chờ khách xác nhận.',
            ]);
        }
    }

    /** @return array{0:string,1:?string} */
    private function resolvePublicConfirmation(
        $quotation,
        string $signerEmail,
        string $otpEmail = '',
    ): array {
        $this->assertCanConfirm($quotation);

        $signerEmail = $this->access->assertAuthorizedSignerEmail(
            $quotation,
            $signerEmail,
        );

        if (! $this->usesOtpConfirmation()) {
            return [$signerEmail, null];
        }

        return $this->assertVerifiedOtp($quotation, $signerEmail, $otpEmail);
    }

    /** @return array{0:string,1:string} */
    private function assertVerifiedOtp(
        $quotation,
        string $signerEmail,
        string $otpEmail,
    ): array {
        $otpEmail = $this->access->assertAuthorizedSignerEmail(
            $quotation,
            $otpEmail,
        );

        if (! hash_equals($signerEmail, $otpEmail)) {
            throw ValidationException::withMessages([
                'otp' => __('sales.public.otp_required'),
            ]);
        }

        $key = $this->access->otpKey($quotation, $otpEmail);

        if (! $this->access->isOtpVerified($key)) {
            throw ValidationException::withMessages([
                'otp' => __('sales.public.otp_required'),
            ]);
        }

        return [$otpEmail, $key];
    }

    private function usesOtpConfirmation(): bool
    {
        return $this->workflowPolicy->usesOtpConfirmation();
    }

    private function assertOtpModeEnabled(): void
    {
        if (! $this->usesOtpConfirmation()) {
            abort(404);
        }
    }

    private function paymentEvidenceRequired(): bool
    {
        return $this->workflowPolicy->paymentEvidenceRequired();
    }

    private function redirectToPublicQuotation(
        string $quotationCode,
        string $token,
        string $message,
    ): RedirectResponse {
        return redirect()->route('sales.quotation.public.show', [
            'quotationCode' => $quotationCode,
            'token' => $token,
        ])->with('success', $message);
    }
}
