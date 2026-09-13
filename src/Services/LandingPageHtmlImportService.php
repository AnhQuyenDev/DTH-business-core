<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Enums\FormAudienceType;
use RuntimeException;

final class LandingPageHtmlImportService
{
    public function __construct(
        private readonly HtmlFormParser $forms,
    ) {}

    /**
     * @return array{
     *   html:string,
     *   title:?string,
     *   theme:array<string,string>,
     *   embedded_forms:array<int,array{html:string,audience_type:string,placeholder:string}>
     * }
     */
    public function fromPath(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('The HTML file cannot be read.');
        }

        $size = filesize($path);

        if ($size === false || $size > 2 * 1024 * 1024) {
            throw new RuntimeException('Landing Page HTML must be 2 MB or smaller.');
        }

        $html = file_get_contents($path);

        if ($html === false || trim($html) === '') {
            throw new RuntimeException('The HTML file is empty.');
        }

        return $this->prepare($html);
    }

    /**
     * Landing import owns the page document. Embedded forms are removed from
     * the page and converted to self-contained Form Template sources. The form
     * source carries the original presentation assets + nearest visual wrapper,
     * so when it is attached at the end of the Landing Page its UI is not lost.
     *
     * @return array{
     *   html:string,
     *   title:?string,
     *   theme:array<string,string>,
     *   embedded_forms:array<int,array{html:string,audience_type:string,placeholder:string}>
     * }
     */
    public function prepare(string $html): array
    {
        $title = null;

        if (preg_match('/<title\b[^>]*>(.*?)<\/title>/is', $html, $match)) {
            $title = trim(html_entity_decode(strip_tags((string) $match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        $sanitized = $this->sanitize($html);
        [$pageHtml, $embeddedForms] = $this->extractFormsWithPresentation($sanitized);

        return [
            'html' => trim($pageHtml),
            'title' => $title !== '' ? $title : null,
            'theme' => $this->detectTheme($pageHtml),
            'embedded_forms' => $embeddedForms,
        ];
    }

    /**
     * Sanitize executable vectors while retaining presentation resources that
     * are required for faithful Preview: <head>, <style>, stylesheet links,
     * CSS classes and trusted Tailwind/browser CDN runtime.
     */
    public function sanitize(string $html): string
    {
        $html = preg_replace_callback(
            '/<script\b([^>]*)>(.*?)<\/script>/is',
            function (array $match): string {
                $attributes = (string) ($match[1] ?? '');
                $body = (string) ($match[2] ?? '');

                if (preg_match('/\bsrc\s*=\s*(["\'])(.*?)\1/is', $attributes, $srcMatch)) {
                    return $this->isTrustedImportScript((string) $srcMatch[2]) ? $match[0] : '';
                }

                return stripos($body, 'tailwind.config') !== false ? $match[0] : '';
            },
            $html,
        ) ?? $html;

        $html = preg_replace('/<script\b[^>]*\/>/is', '', $html) ?? $html;
        $html = preg_replace('/<(iframe|object|embed)\b[^>]*>.*?<\/\1>/is', '', $html) ?? $html;
        $html = preg_replace('/<(iframe|object|embed)\b[^>]*\/?>/is', '', $html) ?? $html;
        $html = preg_replace('/<meta\b[^>]*http-equiv\s*=\s*(["\']?)refresh\1[^>]*>/is', '', $html) ?? $html;
        $html = preg_replace('/\son[a-z0-9_-]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/is', '', $html) ?? $html;
        $html = preg_replace('/\b(?:javascript|vbscript)\s*:/i', '', $html) ?? $html;
        $html = preg_replace('/data\s*:\s*text\/html/i', '', $html) ?? $html;

        return trim($html);
    }

    /**
     * @return array{0:string,1:array<int,array{html:string,audience_type:string,placeholder:string}>}
     */
    private function extractFormsWithPresentation(string $html): array
    {
        $assets = $this->forms->extractPresentationAssets($html);

        if (! class_exists(\DOMDocument::class)) {
            return $this->extractFormsFallback($html, $assets);
        }

        $protected = [];
        $htmlForDom = preg_replace_callback(
            '/<style\b[^>]*>.*?<\/style>|<script\b[^>]*>.*?<\/script>/is',
            static function (array $match) use (&$protected): string {
                $token = '__DTH_PRESENTATION_BLOCK_'.count($protected).'__';
                $protected[$token] = $match[0];

                return $token;
            },
            $html,
        ) ?? $html;

        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">'.$htmlForDom);
        libxml_clear_errors();

        if (! $loaded) {
            return $this->extractFormsFallback($html, $assets);
        }

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//form');
        if ($nodes === false || $nodes->length === 0) {
            return [$html, []];
        }

        $forms = [];
        foreach ($nodes as $node) {
            if ($node instanceof \DOMElement) {
                $forms[] = $node;
            }
        }

        $embedded = [];
        foreach ($forms as $form) {
            if ($form->parentNode === null) {
                continue;
            }

            $formHtml = $dom->saveHTML($form) ?: '';
            $audience = $this->forms->detectAudienceType($formHtml);
            $target = $this->findFormVisualContainer($form) ?? $form;
            $captured = $dom->saveHTML($target) ?: $formHtml;
            $captured = strtr($captured, $protected);

            $embedded[] = [
                'html' => '<!doctype html><html><head>'.$assets.'</head><body>'.$captured.'</body></html>',
                'audience_type' => $audience->value,
                'placeholder' => '',
            ];

            $target->parentNode?->removeChild($target);
        }

        $output = $dom->saveHTML() ?: $htmlForDom;
        $output = preg_replace('/^<\?xml[^>]*>\s*/i', '', $output) ?? $output;
        $output = strtr($output, $protected);

        return [trim($output), $embedded];
    }

    private function findFormVisualContainer(\DOMElement $form): ?\DOMElement
    {
        $node = $form->parentNode;
        $best = null;
        $depth = 0;

        while ($node instanceof \DOMElement && $depth < 5) {
            $tag = strtolower($node->tagName);
            if (in_array($tag, ['body', 'html'], true)) {
                break;
            }

            if ($node->getElementsByTagName('form')->length !== 1) {
                break;
            }

            $identity = strtolower(trim($node->getAttribute('id').' '.$node->getAttribute('class')));
            if (preg_match('/form|contact|register|registration|signup|lead|inquiry|booking|consult/', $identity)) {
                $best = $node;
            } elseif ($best === null && in_array($tag, ['section', 'article', 'aside'], true)) {
                $best = $node;
            } elseif ($best === null && $tag === 'div' && $depth <= 2) {
                $best = $node;
            }

            $node = $node->parentNode;
            $depth++;
        }

        return $best;
    }

    /**
     * @return array{0:string,1:array<int,array{html:string,audience_type:string,placeholder:string}>}
     */
    private function extractFormsFallback(string $html, string $assets): array
    {
        $embedded = [];
        foreach ($this->forms->extractForms($html) as $formHtml) {
            $audience = $this->forms->detectAudienceType($formHtml);
            $embedded[] = [
                'html' => '<!doctype html><html><head>'.$assets.'</head><body>'.$formHtml.'</body></html>',
                'audience_type' => $audience->value,
                'placeholder' => '',
            ];

            $position = strpos($html, $formHtml);
            if ($position !== false) {
                $html = substr_replace($html, '', $position, strlen($formHtml));
            }
        }

        return [$html, $embedded];
    }

    private function isTrustedImportScript(string $src): bool
    {
        $src = trim($src);
        if (str_starts_with($src, '//')) {
            $src = 'https:'.$src;
        }

        $host = strtolower((string) parse_url($src, PHP_URL_HOST));

        return in_array($host, [
            'cdn.tailwindcss.com',
            'cdn.jsdelivr.net',
            'cdnjs.cloudflare.com',
            'unpkg.com',
        ], true);
    }

    /** @return array<string, string> */
    private function detectTheme(string $html): array
    {
        $theme = [];
        $patterns = [
            'primary' => '/--(?:lp-)?primary\s*:\s*(#[0-9a-f]{3,8})/i',
            'background' => '/body\s*\{[^}]*background(?:-color)?\s*:\s*(#[0-9a-f]{3,8})/i',
            'text' => '/body\s*\{[^}]*color\s*:\s*(#[0-9a-f]{3,8})/i',
        ];

        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $html, $match)) {
                $theme[$key] = strtolower((string) $match[1]);
            }
        }

        return $theme;
    }
}
