<?php

namespace App\Services\Marketing;

final class LandingPageThemeService
{
    public function defaults(): array
    {
        return [
            'primary' => '#2563eb',
            'primary_hover' => '#1d4ed8',
            'background' => '#f8fafc',
            'surface' => '#ffffff',
            'text' => '#0f172a',
            'muted_text' => '#64748b',
            'border' => '#cbd5e1',
            'danger' => '#dc2626',
            'radius' => '12px',
        ];
    }

    public function detectFromHtml(string $html): array
    {
        $tokens = $this->defaults();
        $variables = $this->extractCssVariables($html);

        $primary = $this->normalizeHex(
            $variables['lp-primary']
            ?? $variables['primary']
            ?? null
        ) ?? $this->detectPrimaryColor($html);

        if ($primary !== null) {
            $tokens['primary'] = $primary;
            $tokens['primary_hover'] = $this->normalizeHex(
                $variables['primary-hover']
                ?? $variables['secondary']
                ?? null
            ) ?? $this->darken($primary, 12);
        }

        $tokens['background'] = $this->normalizeHex(
            $variables['lp-background']
            ?? $variables['background']
            ?? $variables['bg']
            ?? null
        ) ?? $this->detectBodyColor(
            $html,
            'background(?:-color)?'
        ) ?? $tokens['background'];

        $tokens['surface'] = $this->normalizeHex(
            $variables['lp-surface']
            ?? $variables['surface']
            ?? null
        ) ?? $tokens['surface'];

        $tokens['text'] = $this->normalizeHex(
            $variables['lp-text']
            ?? $variables['text']
            ?? null
        ) ?? $this->detectBodyColor(
            $html,
            'color'
        ) ?? $tokens['text'];

        if (preg_match(
            '/border-radius\s*:\s*(\d+(?:\.\d+)?(?:px|rem))/i',
            $html,
            $match
        )) {
            $tokens['radius'] = $match[1];
        }

        return $tokens;
    }

    public function normalize(?array $tokens): array
    {
        return array_merge(
            $this->defaults(),
            array_filter($tokens ?? [], static fn ($value) => filled($value))
        );
    }

    public function cssVariables(?array $tokens): string
    {
        $tokens = $this->normalize($tokens);

        return implode("\n", [
            '--lp-primary: '.$tokens['primary'].';',
            '--lp-primary-hover: '.$tokens['primary_hover'].';',
            '--lp-background: '.$tokens['background'].';',
            '--lp-surface: '.$tokens['surface'].';',
            '--lp-text: '.$tokens['text'].';',
            '--lp-muted-text: '.$tokens['muted_text'].';',
            '--lp-border: '.$tokens['border'].';',
            '--lp-danger: '.$tokens['danger'].';',
            '--lp-radius: '.$tokens['radius'].';',
        ]);
    }

    private function extractCssVariables(string $html): array
    {
        $variables = [];

        preg_match_all(
            '/--([a-z0-9-_]+)\s*:\s*([^;}]+)/i',
            $html,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $variables[strtolower($match[1])] = trim($match[2]);
        }

        return $variables;
    }

    private function normalizeHex(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^#[0-9a-f]{6}$/i', $value)) {
            return strtolower($value);
        }

        if (preg_match('/^#[0-9a-f]{3}$/i', $value)) {
            return strtolower(sprintf(
                '#%s%s%s%s%s%s',
                $value[1], $value[1],
                $value[2], $value[2],
                $value[3], $value[3],
            ));
        }

        return null;
    }

    private function detectPrimaryColor(string $html): ?string
    {
        $patterns = [
            '/--(?:lp-)?primary\s*:\s*(#[0-9a-f]{6})/i',
            '/<(?:button|a)\b[^>]*style=["\'][^"\']*background(?:-color)?\s*:\s*(#[0-9a-f]{6})/i',
            '/\.btn-primary\s*\{[^}]*background(?:-color)?\s*:\s*(#[0-9a-f]{6})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $match)) {
                return strtolower($match[1]);
            }
        }

        preg_match_all('/#[0-9a-f]{6}/i', $html, $matches);

        $ignored = [
            '#ffffff',
            '#000000',
            '#f8fafc',
            '#f1f5f9',
            '#e2e8f0',
            '#cbd5e1',
            '#64748b',
            '#475569',
            '#1e293b',
            '#0f172a',
        ];

        foreach (array_unique(array_map('strtolower', $matches[0] ?? [])) as $color) {
            if (! in_array($color, $ignored, true)) {
                return $color;
            }
        }

        return null;
    }

    private function detectBodyColor(
        string $html,
        string $propertyPattern
    ): ?string {
        if (! preg_match(
            '/body\s*\{[^}]*'.$propertyPattern.'\s*:\s*(#[0-9a-f]{3,6})/i',
            $html,
            $match
        )) {
            return null;
        }

        return $this->normalizeHex($match[1]);
    }

    private function darken(string $hex, int $percent): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) !== 6) {
            return '#1d4ed8';
        }

        $factor = max(0, 100 - $percent) / 100;

        $red = (int) round(hexdec(substr($hex, 0, 2)) * $factor);
        $green = (int) round(hexdec(substr($hex, 2, 2)) * $factor);
        $blue = (int) round(hexdec(substr($hex, 4, 2)) * $factor);

        return sprintf('#%02x%02x%02x', $red, $green, $blue);
    }
}