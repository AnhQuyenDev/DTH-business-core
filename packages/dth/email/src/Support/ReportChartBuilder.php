<?php

namespace Dth\Email\Support;

final class ReportChartBuilder
{
    /**
     * Build a lightweight SVG chart that Dompdf can render without JavaScript.
     *
     * @param array<int, string> $labels
     * @param array<int, array{label: string, values: array<int, int|float>, color: string}> $series
     */
    public function lineChartDataUri(
        array $labels,
        array $series,
        int $width = 980,
        int $height = 280,
    ): string {
        $width = max(480, $width);
        $height = max(220, $height);
        $left = 58;
        $right = 20;
        $top = 42;
        $bottom = 42;
        $plotWidth = $width - $left - $right;
        $plotHeight = $height - $top - $bottom;
        $count = max(count($labels), 1);
        $maxValue = 0.0;

        foreach ($series as $item) {
            foreach ($item['values'] as $value) {
                $maxValue = max($maxValue, (float) $value);
            }
        }

        $maxValue = max(1.0, $maxValue);
        $svg = [];
        $svg[] = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">',
            $width,
            $height,
            $width,
            $height,
        );
        $svg[] = '<rect width="100%" height="100%" fill="#ffffff"/>';

        for ($i = 0; $i <= 4; $i++) {
            $ratio = $i / 4;
            $y = $top + ($plotHeight * $ratio);
            $value = (int) round($maxValue * (1 - $ratio));
            $svg[] = sprintf(
                '<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="#e5e7eb" stroke-width="1"/>',
                $left,
                $this->n($y),
                $left + $plotWidth,
                $this->n($y),
            );
            $svg[] = sprintf(
                '<text x="%s" y="%s" font-family="DejaVu Sans, sans-serif" font-size="10" fill="#6b7280" text-anchor="end">%s</text>',
                $left - 8,
                $this->n($y + 3),
                $value,
            );
        }

        $tickStep = max(1, (int) ceil($count / 8));

        foreach ($labels as $index => $label) {
            if (($index % $tickStep) !== 0 && $index !== $count - 1) {
                continue;
            }

            $x = $count === 1
                ? $left + ($plotWidth / 2)
                : $left + ($plotWidth * ($index / ($count - 1)));
            $svg[] = sprintf(
                '<text x="%s" y="%s" font-family="DejaVu Sans, sans-serif" font-size="9" fill="#6b7280" text-anchor="middle">%s</text>',
                $this->n($x),
                $height - 16,
                $this->e($label),
            );
        }

        foreach ($series as $seriesIndex => $item) {
            $points = [];
            $values = $item['values'];

            foreach ($labels as $index => $_label) {
                $value = (float) ($values[$index] ?? 0);
                $x = $count === 1
                    ? $left + ($plotWidth / 2)
                    : $left + ($plotWidth * ($index / ($count - 1)));
                $y = $top + $plotHeight - (($value / $maxValue) * $plotHeight);
                $points[] = $this->n($x).','.$this->n($y);
            }

            if ($points !== []) {
                $svg[] = sprintf(
                    '<polyline points="%s" fill="none" stroke="%s" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>',
                    implode(' ', $points),
                    $this->e($item['color']),
                );
            }

            $legendX = $left + ($seriesIndex * 190);
            $svg[] = sprintf(
                '<rect x="%s" y="16" width="12" height="3" rx="1" fill="%s"/>',
                $legendX,
                $this->e($item['color']),
            );
            $svg[] = sprintf(
                '<text x="%s" y="22" font-family="DejaVu Sans, sans-serif" font-size="10" fill="#374151">%s</text>',
                $legendX + 18,
                $this->e($item['label']),
            );
        }

        $svg[] = '</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode(implode('', $svg));
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function n(float|int $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }
}
