<?php

namespace App\Services\Crm;

use App\Contracts\Tax\TaxCodeVerificationProvider;
use App\DTO\Tax\TaxVerificationResult;
use App\Enums\Crm\TaxVerificationStatus;

final class FakeTaxVerificationProvider implements TaxCodeVerificationProvider
{
    public function verify(string $taxCode): TaxVerificationResult
    {
        $normalized = strtoupper(preg_replace('/[^A-Z0-9]/', '', $taxCode));

        if (strlen($normalized) < 8) {
            return new TaxVerificationResult(
                status: TaxVerificationStatus::NotFound,
                message: 'Tax code too short or invalid format.',
            );
        }

        $num = (int) filter_var($normalized, FILTER_SANITIZE_NUMBER_INT);

        if ($num % 3 === 0) {
            return new TaxVerificationResult(
                status: TaxVerificationStatus::Mismatch,
                companyName: 'CÔNG TY TNHH '.substr($normalized, 0, 8),
                message: 'Tax code exists but company name does not match.',
            );
        }

        return new TaxVerificationResult(
            status: TaxVerificationStatus::Verified,
            companyName: 'CÔNG TY TNHH THƯƠNG MẠI '.substr($normalized, 0, 6),
            companyAddress: '123 Đường Láng, Đống Đa, Hà Nội',
            legalName: 'NGUYỄN VĂN A',
            rawData: [
                'tax_code' => $normalized,
                'status' => 'active',
                'provider' => 'fake',
            ],
            message: 'Verified successfully (fake provider).',
        );
    }
}
