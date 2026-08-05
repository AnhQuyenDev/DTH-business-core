<?php

namespace App\Services\Crm;

use Illuminate\Support\Str;

final class CompanyNormalizationService
{
    private const FREE_EMAIL_DOMAINS = [
        'gmail.com',
        'outlook.com',
        'hotmail.com',
        'yahoo.com',
        'icloud.com',
    ];

    public function normalizeName(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = Str::lower(Str::ascii(trim($value)));
        $value = preg_replace(
            '/\b(cong ty|cty|tnhh|co phan|jsc|ltd|company|corporation|corp)\b/',
            ' ',
            $value
        ) ?? $value;
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? $value;
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? $value);

        return $value !== '' ? $value : null;
    }

    public function normalizeTaxCode(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = preg_replace('/\D+/', '', $value) ?? '';

        return $value !== '' ? $value : null;
    }

    public function normalizePhone(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $value = preg_replace('/\D+/', '', $value) ?? '';

        return $value !== '' ? $value : null;
    }

    public function normalizeDomain(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $domain = Str::lower(trim($value));
        $domain = preg_replace('#^https?://#', '', $domain) ?? $domain;
        $domain = preg_replace('#^www\.#', '', $domain) ?? $domain;
        $domain = trim(Str::before($domain, '/'));

        if ($domain === '' || in_array($domain, self::FREE_EMAIL_DOMAINS, true)) {
            return null;
        }

        return $domain;
    }

    public function extractBusinessDomain(?string $email): ?string
    {
        if (blank($email) || ! str_contains($email, '@')) {
            return null;
        }

        return $this->normalizeDomain(Str::after($email, '@'));
    }

    public function isFreeEmailDomain(?string $domain): bool
    {
        if (blank($domain)) {
            return false;
        }

        return in_array(Str::lower(trim($domain)), self::FREE_EMAIL_DOMAINS, true);
    }
}
