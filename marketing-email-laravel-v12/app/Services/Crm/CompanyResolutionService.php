<?php

namespace App\Services\Crm;

use App\Data\Crm\CompanyResolutionResult;
use App\Models\Crm\Company;
use Illuminate\Support\Str;

final class CompanyResolutionService
{
    private const FREE_EMAIL_DOMAINS = [
        'gmail.com',
        'outlook.com',
        'hotmail.com',
        'yahoo.com',
        'icloud.com',
    ];

    public function resolve(array $data): CompanyResolutionResult
    {
        $taxCode = $this->normalizeTaxCode($data['tax_code'] ?? null);

        if ($taxCode !== null) {
            $company = Company::query()->where('tax_code', $taxCode)->first();

            if ($company) {
                return CompanyResolutionResult::matched($company, 100, 'tax_code');
            }
        }

        $domain = $this->extractBusinessDomain($data['business_email'] ?? null);

        if ($domain !== null) {
            $matches = Company::query()->where('email_domain', $domain)->get();

            if ($matches->count() === 1) {
                $company = $matches->first();

                if ($taxCode === null || blank($company->tax_code) || $company->tax_code === $taxCode) {
                    return CompanyResolutionResult::matched($company, 85, 'email_domain');
                }
            }
        }

        $normalizedName = $this->normalizeName($data['company_name'] ?? null);

        if ($normalizedName !== null) {
            $company = Company::query()
                ->where('normalized_name', $normalizedName)
                ->first();

            if ($company) {
                return CompanyResolutionResult::candidate($company, 65, 'normalized_name');
            }
        }

        return CompanyResolutionResult::notFound();
    }

    public function normalizeName(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = Str::lower(Str::ascii(trim($value)));
        $value = preg_replace('/\b(cong ty|cty|tnhh|co phan|jsc|ltd|company)\b/', ' ', $value) ?? $value;
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    private function normalizeTaxCode(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = preg_replace('/\D+/', '', $value) ?? '';

        return $value !== '' ? $value : null;
    }

    public function extractBusinessDomain(?string $email): ?string
    {
        if (blank($email) || ! str_contains($email, '@')) {
            return null;
        }

        $domain = Str::lower(Str::after($email, '@'));

        return in_array($domain, self::FREE_EMAIL_DOMAINS, true)
            ? null
            : $domain;
    }
}
