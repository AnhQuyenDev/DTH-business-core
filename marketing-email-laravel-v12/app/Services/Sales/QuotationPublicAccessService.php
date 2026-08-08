<?php

namespace App\Services\Sales;

use App\Enums\Sales\QuotationStatus;
use App\Models\Sales\Quotation;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuotationPublicAccessService
{
    private const PUBLIC_STATUSES = [
        'sent',
        'viewed',
        'accepted',
        'rejected',
        'revision_requested',
        'expired',
        'cancelled',
        'superseded',
    ];

    public function __construct(
        private readonly QuotationInteractionService $interactions,
        private readonly QuotationOpportunitySyncService $opportunitySync,
    ) {}

    public function findQuotation(string $quotationCode, string $token): ?Quotation
    {
        $quotation = Quotation::query()
            ->where('quotation_code', $quotationCode)
            ->where('public_token', $token)
            ->whereIn('status', self::PUBLIC_STATUSES)
            ->whereNotNull('sent_at')
            ->with([
                'items',
                'priceBook',
                'assignedStaff.user',
                'confirmations',
                'documents',
                'paymentNotices',
            ])
            ->first();

        if ($quotation === null) {
            return null;
        }

        if (
            in_array($quotation->status, [QuotationStatus::Sent, QuotationStatus::Viewed], true)
            && $quotation->valid_until?->isPast()
        ) {
            $quotation->update([
                'status' => QuotationStatus::Expired->value,
                'expired_at' => $quotation->expired_at ?? now(),
            ]);
            $quotation->refresh();
        }

        return $quotation;
    }

    public function trackView(Quotation $quotation): void
    {
        if (! in_array($quotation->status, [QuotationStatus::Sent, QuotationStatus::Viewed], true)) {
            return;
        }

        $firstView = $quotation->first_viewed_at === null;
        $updates = [
            'last_viewed_at' => now(),
        ];

        if ($quotation->status === QuotationStatus::Sent) {
            $updates['status'] = QuotationStatus::Viewed->value;
            $updates['first_viewed_at'] = $quotation->first_viewed_at ?? now();
        }

        $quotation->increment('view_count');
        $quotation->update($updates);

        if ($firstView) {
            $fresh = $quotation->fresh('opportunity');
            $this->interactions->logViewed($fresh);
            $this->opportunitySync->onViewed($fresh);
        }
    }

    public function authorizedSignerEmail(Quotation $quotation): string
    {
        return $this->normalizeEmail(
            (string) ($quotation->authorized_signer_email ?? $quotation->party_email)
        );
    }

    public function assertAuthorizedSignerEmail(
        Quotation $quotation,
        string $email,
    ): string {
        $email = $this->normalizeEmail($email);
        $expected = $this->authorizedSignerEmail($quotation);

        if (
            $expected === ''
            || $email === ''
            || ! hash_equals($expected, $email)
        ) {
            throw ValidationException::withMessages([
                'email' => 'Email này không được chỉ định để xác nhận báo giá.',
            ]);
        }

        return $email;
    }

    public function checkOtpSendThrottle(string $key): void
    {
        $minuteKey = "quotation-otp-send-minute:{$key}";
        $hourKey = "quotation-otp-send-hour:{$key}";

        if (RateLimiter::tooManyAttempts($minuteKey, 3)) {
            throw ValidationException::withMessages([
                'otp' => 'Bạn yêu cầu OTP quá nhanh. Vui lòng thử lại sau '
                    .RateLimiter::availableIn($minuteKey).' giây.',
            ]);
        }

        if (RateLimiter::tooManyAttempts($hourKey, 8)) {
            throw ValidationException::withMessages([
                'otp' => 'Đã vượt số lần gửi OTP cho phép trong một giờ.',
            ]);
        }
    }

    public function hitOtpSendThrottle(string $key): void
    {
        RateLimiter::hit("quotation-otp-send-minute:{$key}", 60);
        RateLimiter::hit("quotation-otp-send-hour:{$key}", 3600);
    }

    public function storeOtp(string $key, string $otp): void
    {
        Cache::put(
            $this->otpCacheKey($key),
            Hash::make($otp),
            now()->addMinutes(5),
        );
    }

    public function verifyOtp(string $key, string $otp): bool
    {
        $attemptKey = "quotation-otp-verify:{$key}";

        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            throw ValidationException::withMessages([
                'otp' => 'Bạn đã nhập sai OTP quá nhiều lần. Vui lòng thử lại sau '
                    .RateLimiter::availableIn($attemptKey).' giây.',
            ]);
        }

        $hash = Cache::get($this->otpCacheKey($key));

        if (! is_string($hash) || ! Hash::check($otp, $hash)) {
            RateLimiter::hit($attemptKey, 600);

            return false;
        }

        RateLimiter::clear($attemptKey);
        Cache::forget($this->otpCacheKey($key));
        Cache::put($this->verifiedCacheKey($key), true, now()->addMinutes(10));

        return true;
    }

    public function isOtpVerified(string $key): bool
    {
        return (bool) Cache::get($this->verifiedCacheKey($key));
    }

    public function consumeOtpVerification(string $key): void
    {
        Cache::forget($this->verifiedCacheKey($key));
    }

    public function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public function otpKey(Quotation $quotation, string $email): string
    {
        return $quotation->quotation_code.':'.$this->normalizeEmail($email);
    }

    private function otpCacheKey(string $key): string
    {
        return "quotation-otp:{$key}";
    }

    private function verifiedCacheKey(string $key): string
    {
        return "quotation-otp-verified:{$key}";
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }
}
