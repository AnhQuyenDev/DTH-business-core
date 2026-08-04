<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Jobs\Sales\SendQuotationAcceptedNotificationJob;
use App\Jobs\Sales\SendQuotationRejectedNotificationJob;
use App\Jobs\Sales\SendQuotationRevisionRequestedNotificationJob;
use App\Mail\Sales\QuotationOtpMail;
use App\Models\Sales\QuotationConfirmation;
use App\Services\Sales\QuotationConfirmationService;
use App\Services\Sales\QuotationPdfService;
use App\Services\Sales\QuotationPublicAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
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
        if (! $quotation) {
            abort(404);
        }

        $this->access->trackView($quotation);

        return view('sales.public.show', compact('quotation'));
    }

    public function markViewed(string $quotationCode, string $token)
    {
        $quotation = $this->access->findQuotation($quotationCode, $token);
        if (! $quotation) {
            abort(404);
        }

        $this->access->markViewed($quotation);

        return back()->with('success', __('sales.public.marked_viewed'));
    }

    public function pdf(string $quotationCode, string $token)
    {
        $quotation = $this->access->findQuotation($quotationCode, $token);
        if (! $quotation) {
            abort(404);
        }

        $this->access->trackView($quotation);

        $doc = app(QuotationPdfService::class)->generate($quotation);

        return Storage::disk('local')->response($doc->file_path, $doc->file_name);
    }

    public function accept(Request $request, string $quotationCode, string $token)
    {
        $quotation = $this->access->findQuotation($quotationCode, $token);
        if (! $quotation) {
            abort(404);
        }

        $validated = $request->validate([
            'signer_name' => 'required|string|max:255',
            'signer_position' => 'nullable|string|max:255',
            'signer_email' => 'required|email|max:255',
            'signer_phone' => 'nullable|string|max:20',
            'otp_email' => 'nullable|email|max:255',
        ]);

        $otpVerifiedEmail = null;

        if (filled($validated['otp_email'] ?? null)) {
            $otpVerifiedEmail = $validated['otp_email'];

            if (! Cache::get("otp-verified:{$quotationCode}:{$otpVerifiedEmail}")) {
                return back()->withErrors(['otp' => __('sales.public.otp_required')])->withInput();
            }
        }

        try {
            $result = app(QuotationConfirmationService::class)->accept($quotation, $validated, $otpVerifiedEmail);
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['confirmation' => $e->getMessage()]);
        }

        SendQuotationAcceptedNotificationJob::dispatch($result);

        return redirect()->route('sales.quotation.public.show', [
            'quotationCode' => $quotationCode,
            'token' => $token,
        ])->with('success', __('sales.public.accept_success'));
    }

    public function reject(Request $request, string $quotationCode, string $token)
    {
        $quotation = $this->access->findQuotation($quotationCode, $token);
        if (! $quotation) {
            abort(404);
        }

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
        ])->with('success', __('sales.public.reject_success'));
    }

    public function requestRevision(Request $request, string $quotationCode, string $token)
    {
        $quotation = $this->access->findQuotation($quotationCode, $token);
        if (! $quotation) {
            abort(404);
        }

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
        ])->with('success', __('sales.public.revision_success'));
    }

    public function sendOtp(Request $request, string $quotationCode, string $token)
    {
        $quotation = $this->access->findQuotation($quotationCode, $token);
        if (! $quotation) {
            abort(404);
        }

        $validated = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $key = "{$quotationCode}:{$validated['email']}";

        try {
            $this->access->checkOtpThrottle($key);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 429);
        }

        $otp = $this->access->generateOtp();

        Cache::put("otp:{$key}", $otp, now()->addMinutes(5));

        $this->access->hitOtpThrottle($key);

        Mail::to($validated['email'])->send(new QuotationOtpMail($quotationCode, $otp, 5));

        return response()->json(['message' => __('sales.public.otp_sent')]);
    }

    public function verifyOtp(Request $request, string $quotationCode, string $token)
    {
        $quotation = $this->access->findQuotation($quotationCode, $token);
        if (! $quotation) {
            abort(404);
        }

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'otp' => 'required|string|size:6',
        ]);

        $key = "{$quotationCode}:{$validated['email']}";
        $cached = Cache::get("otp:{$key}");

        if (! $cached || ! hash_equals((string) $cached, $validated['otp'])) {
            return response()->json(['message' => __('sales.public.otp_invalid')], 422);
        }

        Cache::forget("otp:{$key}");
        Cache::put("otp-verified:{$key}", true, now()->addMinutes(10));

        QuotationConfirmation::where('quotation_id', $quotation->id)
            ->where('signer_email', $validated['email'])
            ->whereNull('otp_verified_at')
            ->update(['otp_verified_at' => now()]);

        return response()->json(['message' => __('sales.public.otp_verified')]);
    }
}
