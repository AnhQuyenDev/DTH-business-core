<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Jobs\Sales\SendQuotationAcceptedNotificationJob;
use App\Jobs\Sales\SendQuotationRejectedNotificationJob;
use App\Jobs\Sales\SendQuotationRevisionRequestedNotificationJob;
use App\Models\Sales\Quotation;
use App\Models\Sales\QuotationConfirmation;
use App\Services\Sales\QuotationConfirmationService;
use App\Services\Sales\QuotationPdfService;
use App\Services\Sales\QuotationPublicAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class QuotationPublicController extends Controller
{
    private QuotationPublicAccessService $access;

    public function __construct(QuotationPublicAccessService $access)
    {
        $this->access = $access;
        $this->middleware('throttle:30,1');
    }

    public function show(string $quotationCode, string $token)
    {
        $quotation = $this->access->findQuotation($quotationCode, $token);
        if (!$quotation) abort(404);

        $this->access->trackView($quotation);

        return view('sales.public.show', compact('quotation'));
    }

    public function pdf(string $quotationCode, string $token)
    {
        $quotation = $this->access->findQuotation($quotationCode, $token);
        if (!$quotation) abort(404);

        $this->access->trackView($quotation);

        $doc = app(QuotationPdfService::class)->generate($quotation);

        return Storage::disk('local')->download($doc->file_path, $doc->file_name);
    }

    public function accept(Request $request, string $quotationCode, string $token)
    {
        $quotation = $this->access->findQuotation($quotationCode, $token);
        if (!$quotation) abort(404);

        $validated = $request->validate([
            'signer_name' => 'required|string|max:255',
            'signer_position' => 'nullable|string|max:255',
            'signer_email' => 'required|email|max:255',
            'signer_phone' => 'nullable|string|max:20',
        ]);

        $result = app(QuotationConfirmationService::class)->accept($quotation, $validated);

        SendQuotationAcceptedNotificationJob::dispatch($result);

        return redirect()->route('sales.quotation.public.show', [
            'quotationCode' => $quotationCode,
            'token' => $token,
        ])->with('success', 'Báo giá đã được chấp nhận.');
    }

    public function reject(Request $request, string $quotationCode, string $token)
    {
        $quotation = $this->access->findQuotation($quotationCode, $token);
        if (!$quotation) abort(404);

        $validated = $request->validate([
            'signer_name' => 'required|string|max:255',
            'signer_email' => 'required|email|max:255',
            'reason' => 'nullable|string|max:1000',
        ]);

        app(QuotationConfirmationService::class)->reject($quotation, $validated);

        SendQuotationRejectedNotificationJob::dispatch($quotation, $validated['reason'] ?? '');

        return redirect()->route('sales.quotation.public.show', [
            'quotationCode' => $quotationCode,
            'token' => $token,
        ])->with('success', 'Báo giá đã được từ chối.');
    }

    public function requestRevision(Request $request, string $quotationCode, string $token)
    {
        $quotation = $this->access->findQuotation($quotationCode, $token);
        if (!$quotation) abort(404);

        $validated = $request->validate([
            'signer_name' => 'required|string|max:255',
            'signer_email' => 'required|email|max:255',
            'reason' => 'required|string|max:1000',
        ]);

        app(QuotationConfirmationService::class)->requestRevision($quotation, $validated);

        SendQuotationRevisionRequestedNotificationJob::dispatch($quotation, $validated['reason']);

        return redirect()->route('sales.quotation.public.show', [
            'quotationCode' => $quotationCode,
            'token' => $token,
        ])->with('success', 'Yêu cầu chỉnh sửa đã được gửi.');
    }

    public function sendOtp(Request $request, string $quotationCode, string $token)
    {
        $quotation = $this->access->findQuotation($quotationCode, $token);
        if (!$quotation) abort(404);

        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $key = "{$quotationCode}:{$validated['email']}";
        $this->access->checkOtpThrottle($key);

        $otp = $this->access->generateOtp();

        Cache::put("otp:{$key}", $otp, now()->addMinutes(5));

        $this->access->hitOtpThrottle($key);

        \Illuminate\Support\Facades\Log::info('OTP sent', [
            'quotation_code' => $quotationCode,
            'email' => $validated['email'],
            'otp' => $otp,
        ]);

        return response()->json(['message' => 'OTP đã được gửi.']);
    }

    public function verifyOtp(Request $request, string $quotationCode, string $token)
    {
        $quotation = $this->access->findQuotation($quotationCode, $token);
        if (!$quotation) abort(404);

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'otp' => 'required|string|size:6',
        ]);

        $key = "{$quotationCode}:{$validated['email']}";
        $cached = Cache::get("otp:{$key}");

        if (!$cached || $cached !== $validated['otp']) {
            return response()->json(['message' => 'OTP không hợp lệ hoặc đã hết hạn.'], 422);
        }

        Cache::forget("otp:{$key}");

        QuotationConfirmation::where('quotation_id', $quotation->id)
            ->where('signer_email', $validated['email'])
            ->whereNull('otp_verified_at')
            ->update(['otp_verified_at' => now()]);

        return response()->json(['message' => 'OTP đã được xác thực.']);
    }
}
