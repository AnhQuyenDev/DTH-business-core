<?php

namespace App\Services\Marketing;

use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageView;
use Illuminate\Http\Request;

class LandingPageTrackingService
{
    /**
     * Record a page view for the given landing page.
     */
    public function trackView(LandingPage $landingPage, Request $request): LandingPageView
    {
        $utm = $this->extractUtm($request);

        return LandingPageView::create([
            'landing_page_id' => $landingPage->id,
            'session_id' => $request->session()->getId(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referrer' => $request->headers->get('referer'),
            'utm_source' => $utm['utm_source'] ?? null,
            'utm_medium' => $utm['utm_medium'] ?? null,
            'utm_campaign' => $utm['utm_campaign'] ?? null,
            'utm_content' => $utm['utm_content'] ?? null,
            'utm_term' => $utm['utm_term'] ?? null,
            'viewed_at' => now(),
        ]);
    }

    /**
     * Extract UTM parameters from the request.
     *
     * @return array<string, string|null>
     */
    public function extractUtm(Request $request): array
    {
        return [
            'utm_source' => $request->query('utm_source'),
            'utm_medium' => $request->query('utm_medium'),
            'utm_campaign' => $request->query('utm_campaign'),
            'utm_content' => $request->query('utm_content'),
            'utm_term' => $request->query('utm_term'),
        ];
    }
}
