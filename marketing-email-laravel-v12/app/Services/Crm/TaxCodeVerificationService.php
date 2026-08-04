<?php

namespace App\Services\Crm;

use App\Contracts\Tax\TaxCodeVerificationProvider;
use App\DTO\Tax\TaxVerificationResult;
use App\Models\Crm\BusinessContactProfile;
use Illuminate\Support\Facades\Cache;

final class TaxCodeVerificationService
{
    private const CACHE_PREFIX = 'tax_verification_';
    private const CACHE_DAYS = 14;

    public function __construct(
        private TaxCodeVerificationProvider $provider,
    ) {}

    public function verify(BusinessContactProfile $profile): TaxVerificationResult
    {
        $taxCode = $profile->tax_code;

        if (blank($taxCode)) {
            return new TaxVerificationResult(
                status: \App\Enums\Crm\TaxVerificationStatus::Error,
                message: 'No tax code provided.',
            );
        }

        return Cache::remember(
            self::CACHE_PREFIX . $taxCode,
            now()->addDays(self::CACHE_DAYS),
            fn () => $this->provider->verify($taxCode),
        );
    }
}
