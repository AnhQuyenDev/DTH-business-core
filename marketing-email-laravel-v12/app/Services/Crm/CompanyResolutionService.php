<?php

namespace App\Services\Crm;

use App\Data\Crm\CompanyResolutionResult;
use App\Models\Crm\Company;

final class CompanyResolutionService
{
    public function __construct(
        private readonly CompanyNormalizationService $normalizer,
    ) {}

    public function resolve(array $data): CompanyResolutionResult
    {
        $taxCode = $this->normalizer->normalizeTaxCode(
            $data['tax_code'] ?? null
        );

        if ($taxCode !== null) {
            $company = Company::query()
                ->where('tax_code', $taxCode)
                ->first();

            if ($company !== null) {
                return CompanyResolutionResult::matched(
                    $company,
                    100,
                    'tax_code'
                );
            }
        }

        $domain = $this->normalizer->extractBusinessDomain(
            $data['business_email'] ?? null
        );

        if ($domain !== null) {
            $matches = Company::query()
                ->where('email_domain', $domain)
                ->limit(2)
                ->get();

            if ($matches->count() === 1) {
                $company = $matches->first();

                $hasTaxConflict = $taxCode !== null
                    && filled($company->tax_code)
                    && $company->tax_code !== $taxCode;

                if (! $hasTaxConflict) {
                    return CompanyResolutionResult::matched(
                        $company,
                        85,
                        'email_domain'
                    );
                }
            }
        }

        $normalizedName = $this->normalizer->normalizeName(
            $data['company_name'] ?? null
        );

        if ($normalizedName !== null) {
            $matches = Company::query()
                ->where('normalized_name', $normalizedName)
                ->limit(2)
                ->get();

            if ($matches->count() === 1) {
                $company = $matches->first();

                /*
                 * Hai doanh nghiệp có cùng tên nhưng MST khác nhau
                 * không được tạo candidate ghép vào nhau.
                 */
                $hasTaxConflict = $taxCode !== null
                    && filled($company->tax_code)
                    && $company->tax_code !== $taxCode;

                if (! $hasTaxConflict) {
                    return CompanyResolutionResult::candidate(
                        $company,
                        65,
                        'normalized_name'
                    );
                }
            }
        }

        return CompanyResolutionResult::notFound();
    }

    /*
     * Giữ các wrapper này để không làm hỏng caller/test hiện tại.
     */
    public function normalizeName(?string $value): ?string
    {
        return $this->normalizer->normalizeName($value);
    }

    public function normalizeTaxCode(?string $value): ?string
    {
        return $this->normalizer->normalizeTaxCode($value);
    }

    public function extractBusinessDomain(?string $email): ?string
    {
        return $this->normalizer->extractBusinessDomain($email);
    }
}
