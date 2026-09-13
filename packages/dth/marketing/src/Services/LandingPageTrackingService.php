<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Models\LandingPage;
use Dth\Marketing\Models\LandingPageView;
use Illuminate\Http\Request;

final class LandingPageTrackingService
{
    public function trackView(LandingPage $landingPage, Request $request): LandingPageView
    {
        $utm = $this->extractUtm($request);

        return LandingPageView::query()->create([
            'landing_page_id' => $landingPage->getKey(),
            'marketing_campaign_id' => $landingPage->marketing_campaign_id,
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->headers->get('referer'),
            'source' => $this->acquisitionSource($landingPage, $request),
            ...$utm,
            'viewed_at' => now(),
        ]);
    }

    /** @return array<string, string|null> */
    public function extractUtm(Request $request): array
    {
        $read = static function (string $key) use ($request): ?string {
            $value = $request->query($key);
            if ($value === null || trim((string) $value) === '') {
                $value = $request->input($key);
            }

            $value = is_scalar($value) ? trim((string) $value) : '';

            return $value !== '' ? mb_substr($value, 0, 255) : null;
        };

        return [
            'utm_source' => $read('utm_source'),
            'utm_medium' => $read('utm_medium'),
            'utm_campaign' => $read('utm_campaign'),
            'utm_content' => $read('utm_content'),
            'utm_term' => $read('utm_term'),
        ];
    }

    public function acquisitionSource(LandingPage $landingPage, Request $request): string
    {
        $utm = $this->extractUtm($request);
        if (filled($utm['utm_source'] ?? null)) {
            return (string) $utm['utm_source'];
        }

        if (filled($landingPage->tracking_source)) {
            return trim((string) $landingPage->tracking_source);
        }

        $referrer = trim((string) $request->headers->get('referer', ''));
        if ($referrer !== '') {
            $host = parse_url($referrer, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                return mb_substr($host, 0, 255);
            }
        }

        return 'direct';
    }
}
