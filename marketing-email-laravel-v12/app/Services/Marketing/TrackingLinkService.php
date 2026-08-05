<?php

namespace App\Services\Marketing;

use App\Models\Marketing\CampaignRecipient;
use App\Models\Marketing\TrackedLink;
use Illuminate\Support\Str;

class TrackingLinkService
{
    public function processHtml(string $html, CampaignRecipient $recipient): string
    {
        $html = preg_replace_callback('/href=("|")(.*?)(\1)/i', function (array $matches) use ($recipient) {
            $originalUrl = html_entity_decode($matches[2], ENT_QUOTES, 'UTF-8');

            if ($originalUrl === '' || Str::startsWith($originalUrl, ['mailto:', '#', 'javascript:'])) {
                return $matches[0];
            }

            // Skip internal tracking / unsubscribe routes so they are not double-tracked
            $parsedPath = parse_url($originalUrl, PHP_URL_PATH) ?? '';
            if (Str::startsWith($parsedPath, '/m/')) {
                return $matches[0];
            }

            $trackedLink = TrackedLink::query()->firstOrCreate(
                [
                    'campaign_id' => $recipient->campaign_id,
                    'campaign_recipient_id' => $recipient->id,
                    'original_url' => $originalUrl,
                ],
                [
                    'tracking_token' => Str::uuid()->toString(),
                    'click_count' => 0,
                ]
            );

            $trackingUrl = route('marketing.track.click', ['token' => $trackedLink->tracking_token]);

            return 'href="'.e($trackingUrl).'"';
        }, $html) ?? $html;

        return $this->appendOpenPixel($html, $recipient);
    }

    public function appendOpenPixel(string $html, CampaignRecipient $recipient): string
    {
        $pixelUrl = route('marketing.track.open', ['token' => $recipient->tracking_token]);
        $pixelTag = '<img src="'.e($pixelUrl).'" alt="" width="1" height="1" style="display:none!important;border:0;" />';

        return Str::contains($html, '</body>')
            ? Str::replaceLast('</body>', $pixelTag.'</body>', $html)
            : $html.$pixelTag;
    }
}
