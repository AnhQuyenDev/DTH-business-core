<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Jobs\Sales\SendQuotationAcceptedNotificationJob;
use App\Jobs\Sales\SendQuotationRejectedNotificationJob;
use App\Jobs\Sales\SendQuotationRevisionRequestedNotificationJob;
use App\Mail\Sales\QuotationOtpMail;
use App\Services\Sales\QuotationConfirmationService;
use App\Services\Sales\QuotationPaymentNoticeService;
use App\Services\Sales\QuotationPdfService;
use App\Services\Sales\QuotationPublicAccessService;
use App\Services\Sales\QuotationSendingAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class QuotationPublicController extends Controller
{
    public function __construct(
        private readonly QuotationPublicAccessService $access,
    ) {
        $this->middleware('throttle:30,1');
    }

    public function show(string $quotationCode, string $token)
    {
        $quotation = $this->access->findQuotation($quotationCode, $token);
        if (! $quotation) {
            abort(404);
        }

        $this->access->trackView($quotation);
        $quotation->refresh();
        $quotation->loadMissing([
            'items',
            'priceBook',
            'assignedStaff.user',
            'confirmations',
            'documents',
            'paymentNotices',
        ]);

        return view('sales.public.show', compact('quotation'));
    }

    public function pdf(string $quotationCode, string $token)
    {
        $quotation = $this->access->findQuotation($quotationCode, $token);
        if (! $quotation) {
            abort(404);
        }

        $this->access->trackView($quotation);

        // The customer must receive the exact approved document that was sent,
        // not a newly regenerated PDF built from mutable master data.
        $doc = app(QuotationPdfService::class)->getLatestPdf($quotation);
        if ($doc === null || ! Storage::disk('local')->exists($doc->file_path)) {
            abort(404);
        }

        return Storage::disk('local')->response(
            $doc->file_path,
            $doc->file_name,
        );
    }

    public function accept(
        Request $request,
        string $quotationCode,
        string $token,
    ) {
        $quotation = $this->findOrFail($quotationCode, $token);

        $validated = $request->validate([
            'signer_name' => 'required|string|max:255',
            'signer_position' => 'nullable|string|max:255',
            'signer_email' => 'required|email|max:255',
            'signer_phone' => 'nullable|string|max:20',
            'otp_email' => 'required|email|max:255',
        ]);

        $email = $this->assertVerifiedOtp(
            $quotation,
            (string) $validated['otp_email'],
        );
        $this->access->assertAuthorizedSignerEmail(
            $quotation,
            (string) $validated['signer_email'],
        );

        if (mb_strtolower(trim($validated['signer_email'])) !== $email) {
            throw ValidationException::withMessages([
                'signer_email' => 'Email người ký phải trùng với email đã xác thực OTP.',
            ]);
        }

        try {
            $result = app(QuotationConfirmationService::class)->accept(
                $quotation,
                $validated,
                $email,
            );
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'confirmation' => $e->getMessage(),
            ]);
        }

        $this->access->consumeOtpVerification(
            $this->access->otpKey($quotation, $email)
        );
        SendQuotationAcceptedNotificationJob::dispatch($result);

        return $this->redirectWithSuccess(
            $quotationCode,
            $token,
            __('sales.public.accept_success'),
        );
    }

    public function reject(
        Request $request,
        string $quotationCode,
        string $token,
    ) {
        $quotation = $this->findOrFail($quotationCode, $token);

        $validated = $request->validate([
            'signer_name' => 'required|string|max:255',
            'signer_email' => 'required|email|max:255',
            'reason' => 'required|string|max:1000',
            'otp_email' => 'required|email|max:255',
        ]);

        $email = $this->assertVerifiedOtp($quotation, (string) $validated['otp_email']);
        $this->access->assertAuthorizedSignerEmail($quotation, (string) $validated['signer_email']);

        if (mb_strtolower(trim($validated['signer_email'])) !== $email) {
            throw ValidationException::withMessages([
                'signer_email' => 'Email người xác nhận phải trùng với email đã xác thực OTP.',
            ]);
        }

        try {
            app(QuotationConfirmationService::class)->reject($quotation, $validated, $email);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'confirmation' => $e->getMessage(),
            ]);
        }

        $this->access->consumeOtpVerification($this->access->otpKey($quotation, $email));
        SendQuotationRejectedNotificationJob::dispatch($quotation, $validated['reason']);

        return $this->redirectWithSuccess(
            $quotationCode,
            $token,
            __('sales.public.reject_success'),
        );
    }

    public function requestRevision(
        Request $request,
        string $quotationCode,
        string $token,
    ) {
        $quotation = $this->findOrFail($quotationCode, $token);

        $validated = $request->validate([
            'signer_name' => 'required|string|max:255',
            'signer_email' => 'required|email|max:255',
            'reason' => 'required|string|max:1000',
            'otp_email' => 'required|email|max:255',
        ]);

        $email = $this->assertVerifiedOtp($quotation, (string) $validated['otp_email']);
        $this->access->assertAuthorizedSignerEmail($quotation, (string) $validated['signer_email']);

        if (mb_strtolower(trim($validated['signer_email'])) !== $email) {
            throw ValidationException::withMessages([
                'signer_email' => 'Email người xác nhận phải trùng với email đã xác thực OTP.',
            ]);
        }

        try {
            app(QuotationConfirmationService::class)->requestRevision($quotation, $validated, $email);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'confirmation' => $e->getMessage(),
            ]);
        }

        $this->access->consumeOtpVerification($this->access->otpKey($quotation, $email));
        SendQuotationRevisionRequestedNotificationJob::dispatch(
            $quotation,
            $validated['reason'],
        );

        return $this->redirectWithSuccess(
            $quotationCode,
            $token,
            __('sales.public.revision_success'),
        );
    }

    public function sendOtp(
        Request $request,
        string $quotationCode,
        string $token,
    ) {
        $quotation = $this->findOrFail($quotationCode, $token);

        if (! $quotation->status->canConfirm()) {
            throw ValidationException::withMessages([
                'otp' => 'Báo giá hiện không còn ở trạng thái chờ khách xác nhận.',
            ]);
        }

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
        $this->access->storeOtp($key, $otp);
        $this->access->hitOtpSendThrottle($key);

        $sendingAccounts = app(QuotationSendingAccountService::class);
        $account = $sendingAccounts->resolve($quotation);
        $mailer = $sendingAccounts->configureMailer($account);

        try {
            Mail::mailer($mailer)
                ->to($email)
                ->send(new QuotationOtpMail($quotationCode, $otp, 5));
        } catch (\Throwable $exception) {
            // The customer should see a recoverable validation message rather
            // than a framework 500 page when SMTP is temporarily unavailable.
            throw ValidationException::withMessages([
                'otp' => 'Chưa thể gửi mã OTP lúc này. Vui lòng thử lại sau hoặc liên hệ nhân viên phụ trách.',
            ]);
        }

        return response()->json([
            'message' => __('sales.public.otp_sent'),
        ]);
    }

    public function verifyOtp(
        Request $request,
        string $quotationCode,
        string $token,
    ) {
        $quotation = $this->findOrFail($quotationCode, $token);

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'otp' => 'required|string|size:6|regex:/^\d{6}$/',
        ]);

        $email = $this->access->assertAuthorizedSignerEmail(
            $quotation,
            (string) $validated['email'],
        );
        $key = $this->access->otpKey($quotation, $email);

        if (! $this->access->verifyOtp($key, (string) $validated['otp'])) {
            return response()->json([
                'message' => __('sales.public.otp_invalid'),
            ], 422);
        }

        return response()->json([
            'message' => __('sales.public.otp_verified'),
        ]);
    }

    public function notifyPayment(
        Request $request,
        string $quotationCode,
        string $token,
    ) {
        $quotation = $this->findOrFail($quotationCode, $token);

        $validated = $request->validate([
            'payer_name' => 'required|string|max:255',
            'payer_email' => 'required|email|max:255',
            'declared_amount' => 'required|numeric|min:1',
            'transfer_reference' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1000',
        ]);

        app(QuotationPaymentNoticeService::class)->submit(
            $quotation,
            $validated,
            $request,
        );

        return $this->redirectWithSuccess(
            $quotationCode,
            $token,
            'Đã gửi thông báo thanh toán. Bộ phận Tài chính sẽ đối soát trước khi xác nhận.',
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

    private function assertVerifiedOtp($quotation, string $email): string
    {
        $email = $this->access->assertAuthorizedSignerEmail($quotation, $email);
        $key = $this->access->otpKey($quotation, $email);

        if (! $this->access->isOtpVerified($key)) {
            throw ValidationException::withMessages([
                'otp' => __('sales.public.otp_required'),
            ]);
        }

        return $email;
    }

    private function redirectWithSuccess(
        string $quotationCode,
        string $token,
        string $message,
    ) {
        return redirect()->route('sales.quotation.public.show', [
            'quotationCode' => $quotationCode,
            'token' => $token,
        ])->with('success', $message);
    }
}
