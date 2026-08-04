<?php

use App\Models\CompanySetting;
use Illuminate\Support\Facades\Storage;

if (! function_exists('company_settings')) {
    function company_settings(): CompanySetting
    {
        static $settings = null;

        return $settings ??= CompanySetting::firstOrCreateDefault();
    }
}

if (! function_exists('company_name')) {
    function company_name(): string
    {
        return company_settings()->company_name ?: config('app.name');
    }
}

if (! function_exists('company_field')) {
    function company_field(string $field): ?string
    {
        $value = company_settings()->{$field};

        return filled($value) ? (string) $value : null;
    }
}

if (! function_exists('company_email')) {
    function company_email(): ?string
    {
        return company_field('email');
    }
}

if (! function_exists('company_phone')) {
    function company_phone(): ?string
    {
        return company_field('phone');
    }
}

if (! function_exists('company_address')) {
    function company_address(): ?string
    {
        return company_field('address');
    }
}

if (! function_exists('company_tax_code')) {
    function company_tax_code(): ?string
    {
        return company_field('tax_code');
    }
}

if (! function_exists('company_website')) {
    function company_website(): ?string
    {
        return company_field('website');
    }
}

if (! function_exists('company_logo_url')) {
    function company_logo_url(): ?string
    {
        $path = company_field('logo_path');

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }
}

if (! function_exists('company_logo_data_uri')) {
    function company_logo_data_uri(): ?string
    {
        $path = company_field('logo_path');

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        try {
            $mime = Storage::disk('public')->mimeType($path) ?: 'image/png';
            $data = Storage::disk('public')->get($path);

            return 'data:'.$mime.';base64,'.base64_encode($data);
        } catch (Throwable) {
            return null;
        }
    }
}

if (! function_exists('format_money')) {
    function format_money(int|float|string|null $amount): string
    {
        return number_format((float) ($amount ?? 0), 0, ',', '.');
    }
}
