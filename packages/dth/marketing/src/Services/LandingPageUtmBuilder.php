<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Models\LandingPage;
use Dth\Marketing\Support\UiText;
use Illuminate\Validation\ValidationException;

final class LandingPageUtmBuilder
{
    /** @param array<string, mixed> $values */
    public function build(LandingPage $page, array $values): string
    {
        $source = trim((string) ($values['source'] ?? ''));
        $medium = trim((string) ($values['medium'] ?? ''));

        if ($source === '' || $medium === '') {
            throw ValidationException::withMessages([
                'source' => UiText::get('utm.validation.source_medium_required', 'UTM source and medium are required.'),
            ]);
        }

        $page->loadMissing('marketingCampaign');
        $campaign = trim((string) ($values['campaign'] ?? ''));

        if ($campaign === '') {
            $campaign = (string) ($page->marketingCampaign?->slug ?: $page->marketingCampaign?->name ?: $page->slug);
        }

        $query = array_filter([
            'utm_source' => $source,
            'utm_medium' => $medium,
            'utm_campaign' => $campaign,
            'utm_content' => trim((string) ($values['content'] ?? '')),
            'utm_term' => trim((string) ($values['term'] ?? '')),
        ], static fn (string $value): bool => $value !== '');

        return $page->publicUrl().'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
}
