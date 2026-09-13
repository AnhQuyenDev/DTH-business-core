<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Enums\FormAudienceType;
use RuntimeException;

/**
 * Landing import behavior follows the original Marketing source:
 * - keep the Landing Page document/style
 * - remove the old form section/container from that document
 * - remove all runtime form placeholders
 * - runtime forms are appended later by LandingPageRenderService
 *
 * The current package still returns embedded_forms so the already-stable M-D
 * convenience (auto-create Draft Form Templates) remains available. That is an
 * additive compatibility feature; it is not written back into the page markup.
 */
final class LandingPageHtmlImportService
{
    public function __construct(
        private readonly HtmlFormParser $forms,
        private readonly LandingPageThemeService $themeService,
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
        [$pageHtml, $embeddedForms] = $this->removeImportedFormSections($sanitized);

        // Exact original-source rule: imported Landing Pages must not retain
        // any form placeholder. The renderer owns one canonical forms section
        // and appends it before </body>.
        $pageHtml = str_replace([
            '{{form}}',
            '{{form_personal}}',
            '{{form_business}}',
            '{{forms_section}}',
        ], '', $pageHtml);

        $pageHtml = trim($pageHtml);

        return [
            'html' => $pageHtml,
            'title' => $title !== '' ? $title : null,
            'theme' => $this->themeService->detectFromHtml($pageHtml),
            'embedded_forms' => $embeddedForms,
        ];
    }

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
    private function removeImportedFormSections(string $html): array
    {
        if (! class_exists(\DOMDocument::class)) {
            return $this->removeImportedFormsFallback($html);
        }

        // Preserve CSS/allowed scripts byte-for-byte while DOMDocument removes
        // the old form container. This also avoids Unicode entities inside CSS.
        $protected = [];
        $htmlForDom = preg_replace_callback(
            '/<style\b[^>]*>.*?<\/style>|<script\b[^>]*>.*?<\/script>/is',
            static function (array $matches) use (&$protected): string {
                $token = '__DTH_SOURCE_PARITY_BLOCK_'.count($protected).'__';
                $protected[$token] = $matches[0];

                return $token;
            },
            $html,
        ) ?? $html;

        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML(
            '<?xml encoding="UTF-8">'.$htmlForDom,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();

        if (! $loaded) {
            return $this->removeImportedFormsFallback($html);
        }

        $xpath = new \DOMXPath($dom);
        $forms = $xpath->query('//form');
        if ($forms === false || $forms->length === 0) {
            return [$html, []];
        }

        $nodes = [];
        foreach ($forms as $form) {
            if ($form instanceof \DOMElement) {
                $nodes[] = $form;
            }
        }

        $embedded = [];
        foreach ($nodes as $form) {
            $formHtml = $dom->saveHTML($form) ?: '';
            if ($formHtml !== '') {
                $formHtml = strtr($formHtml, $protected);
                $audience = $this->forms->detectAudienceType($formHtml);
                $embedded[] = [
                    // The original source imports a Form Template from its form
                    // itself; page-level CSS is not carried into runtime forms.
                    'html' => $formHtml,
                    'audience_type' => $audience->value,
                    'placeholder' => $audience === FormAudienceType::Business
                        ? '{{form_business}}'
                        : '{{form_personal}}',
                ];
            }

            if ($form->parentNode === null) {
                continue;
            }

            $target = $this->findImportedFormContainer($form) ?? $form;
            $target->parentNode?->removeChild($target);
        }

        $output = trim($dom->saveHTML() ?: $htmlForDom);
        $output = preg_replace('/^<\?xml[^>]*\?>\s*/i', '', $output) ?? $output;

        return [strtr($output, $protected), $embedded];
    }

    private function findImportedFormContainer(\DOMElement $form): ?\DOMElement
    {
        $node = $form->parentNode;
        $fallback = null;

        while ($node instanceof \DOMElement) {
            $tag = strtolower($node->tagName);
            $identity = strtolower($node->getAttribute('id').' '.$node->getAttribute('class'));

            if (
                $tag === 'section'
                && preg_match('/form|contact|register|registration|signup|lead|inquiry|booking/', $identity)
            ) {
                return $node;
            }

            if (
                $fallback === null
                && preg_match('/form-section|form-wrapper|contact-form|register-form/', $identity)
            ) {
                $fallback = $node;
            }

            if ($tag === 'body') {
                break;
            }

            $node = $node->parentNode;
        }

        return $fallback;
    }

    /**
     * @return array{0:string,1:array<int,array{html:string,audience_type:string,placeholder:string}>}
     */
    private function removeImportedFormsFallback(string $html): array
    {
        $embedded = [];
        foreach ($this->forms->extractForms($html) as $formHtml) {
            $audience = $this->forms->detectAudienceType($formHtml);
            $embedded[] = [
                'html' => $formHtml,
                'audience_type' => $audience->value,
                'placeholder' => $audience === FormAudienceType::Business
                    ? '{{form_business}}'
                    : '{{form_personal}}',
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
}
