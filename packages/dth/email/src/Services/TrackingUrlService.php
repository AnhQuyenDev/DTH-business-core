<?php

namespace Dth\Email\Services;

use DOMDocument;
use Dth\Email\Models\EmailMessage;
use Dth\Email\Models\EmailTrackedLink;
use Illuminate\Support\Str;

class TrackingUrlService
{
    public function unsubscribeUrl(EmailMessage $message): string
    {
        return $this->publicRoute(
            'dth.email.unsubscribe',
            ['token' => $message->unsubscribe_token],
        );
    }

    public function openPixelUrl(EmailMessage $message): string
    {
        return $this->publicRoute(
            'dth.email.open',
            ['token' => $message->tracking_token],
        );
    }

    public function injectOpenPixel(string $html, string $pixelUrl): string
    {
        if (! config('dth-email.tracking.enabled', true) || trim($html) === '' || trim($pixelUrl) === '') {
            return $html;
        }

        // Keep this deliberately equivalent to the tracker markup used by the
        // pre-package implementation that was already proven to work in Gmail.
        $pixel = '<img src="'.e($pixelUrl).'" alt="" width="1" height="1" style="display:none!important;border:0;" />';

        // Match the legacy implementation: insert immediately before the final
        // </body>. If this is an HTML fragment, append it at the end.
        if (preg_match('/<\/body\s*>/i', $html, $matches, PREG_OFFSET_CAPTURE) === 1) {
            $lastPos = strripos($html, '</body>');

            if ($lastPos !== false) {
                return substr($html, 0, $lastPos).$pixel.substr($html, $lastPos);
            }
        }

        return $html.$pixel;
    }

    public function rewriteLinks(EmailMessage $message, string $html): string
    {
        if (! config('dth-email.tracking.enabled', true) || trim($html) === '') {
            return $html;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $loaded = $dom->loadHTML('<?xml encoding="UTF-8"?>'.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        if (! $loaded) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            return $html;
        }

        foreach ($dom->getElementsByTagName('a') as $anchor) {
            $href = trim($anchor->getAttribute('href'));

            if ($href === '' || str_starts_with($href, '#') || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
                continue;
            }

            if (str_contains($href, '/email/unsubscribe/')) {
                continue;
            }

            $tracked = EmailTrackedLink::query()->create([
                'message_id' => $message->id,
                'original_url' => $href,
                'tracking_token' => (string) Str::uuid(),
            ]);

            $anchor->setAttribute(
                'href',
                $this->publicRoute(
                    'dth.email.click',
                    ['token' => $tracked->tracking_token],
                ),
            );
        }

        $output = $dom->saveHTML() ?: $html;
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return preg_replace('/^<\?xml encoding="UTF-8"\?>/', '', $output) ?: $output;
    }

    private function publicRoute(string $name, array $parameters): string
    {
        // Important for sync queues and local admin URLs: absolute route()
        // would otherwise inherit the current request host (127.0.0.1/localhost).
        $path = route($name, $parameters, false);
        $baseUrl = rtrim(
            (string) (config('dth-email.tracking.base_url') ?: config('app.url')),
            '/',
        );

        return $baseUrl.'/'.ltrim($path, '/');
    }
}
