<?php

namespace App\Services\Sales;

use App\Models\Sales\Quotation;
use Illuminate\Support\Facades\RateLimiter;

class QuotationPublicAccessService
{
    public function findQuotation(string $quotationCode, string $token): ?Quotation
    {
        return Quotation::where('quotation_code', $quotationCode)
            ->where('public_token', $token)
            ->first();
    }

    public function trackView(Quotation $quotation): void
    {
        if ($quotation->status->value === 'sent') {
            $quotation->update([
                'status' => 'viewed',
                'first_viewed_at' => $quotation->first_viewed_at ?? now(),
                'last_viewed_at' => now(),
            ]);
        } else {
            $quotation->increment('view_count');
            $quotation->update(['last_viewed_at' => now()]);
        }
    }

    public function checkOtpThrottle(string $key): bool
    {
        if (RateLimiter::tooManyAttempts("otp-send:{$key}", 3)) {
            $seconds = RateLimiter::availableIn("otp-send:{$key}");
            throw new \RuntimeException("Vui lòng thử lại sau {$seconds} giây.");
        }
        return true;
    }

    public function hitOtpThrottle(string $key): void
    {
        RateLimiter::hit("otp-send:{$key}", 60);
    }

    public function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
