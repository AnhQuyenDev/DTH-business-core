<?php

namespace Dth\Marketing\Support;

/**
 * Minimal dependency-free PDF writer for management reports.
 * Uses built-in Helvetica and ASCII transliteration for maximum portability.
 */
final class SimplePdfWriter
{
    /** @param array<int, string> $lines */
    public function build(string $title, array $lines): string
    {
        $title = $this->ascii($title);
        $renderLines = [$title, str_repeat('=', min(80, max(10, strlen($title))))];
        foreach ($lines as $line) {
            foreach ($this->wrap($this->ascii((string) $line), 96) as $wrapped) {
                $renderLines[] = $wrapped;
            }
        }

        $pages = array_chunk($renderLines, 48);
        if ($pages === []) {
            $pages = [['DTH Marketing Report']];
        }

        $objectCount = 3 + (count($pages) * 2);
        $objects = array_fill(0, $objectCount + 1, '');
        $pageRefs = [];

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';

        foreach ($pages as $index => $pageLines) {
            $pageObject = 4 + ($index * 2);
            $contentObject = $pageObject + 1;
            $pageRefs[] = $pageObject.' 0 R';
            $stream = $this->pageStream($pageLines, $index === 0);
            $objects[$pageObject] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents '.$contentObject.' 0 R >>';
            $objects[$contentObject] = '<< /Length '.strlen($stream).' >>' . "\nstream\n" . $stream . "\nendstream";
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $pageRefs).'] /Count '.count($pageRefs).' >>';

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0 => 0];
        for ($i = 1; $i <= $objectCount; $i++) {
            $offsets[$i] = strlen($pdf);
            $pdf .= $i." 0 obj\n".$objects[$i]."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".($objectCount + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $objectCount; $i++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$i])."\n";
        }
        $pdf .= 'trailer << /Size '.($objectCount + 1).' /Root 1 0 R >>' . "\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    /** @param array<int, string> $lines */
    private function pageStream(array $lines, bool $firstPage): string
    {
        $commands = ["BT", "/F1 10 Tf"];
        $y = 806;
        foreach ($lines as $index => $line) {
            $isHeading = $firstPage && $index === 0;
            $fontSize = $isHeading ? 17 : 10;
            $commands[] = '/F1 '.$fontSize.' Tf';
            $commands[] = '1 0 0 1 46 '.$y.' Tm';
            $commands[] = '('.$this->pdfEscape($line).') Tj';
            $y -= $isHeading ? 24 : 15;
        }
        $commands[] = "ET";

        return implode("\n", $commands);
    }

    /** @return array<int, string> */
    private function wrap(string $text, int $width): array
    {
        if ($text === '') {
            return [''];
        }

        $wrapped = wordwrap($text, $width, "\n", true);

        return explode("\n", $wrapped);
    }

    private function ascii(string $value): string
    {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($converted) && $converted !== '') {
            return preg_replace('/[^\x20-\x7E]/', '', $converted) ?? $converted;
        }

        return preg_replace('/[^\x20-\x7E]/', '?', $value) ?? $value;
    }

    private function pdfEscape(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}
