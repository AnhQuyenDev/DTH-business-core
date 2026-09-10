<?php

namespace Dth\Email\Services;

use InvalidArgumentException;
use RuntimeException;

class HtmlTemplateImportService
{
    public const MAX_BYTES = 2 * 1024 * 1024;

    public function fromPath(string $path): string
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new InvalidArgumentException('The uploaded HTML file cannot be read.');
        }

        $size = filesize($path);

        if ($size === false || $size > self::MAX_BYTES) {
            throw new InvalidArgumentException('The HTML file must not exceed 2 MB.');
        }

        $html = file_get_contents($path);

        if ($html === false) {
            throw new RuntimeException('Unable to read the uploaded HTML file.');
        }

        $html = preg_replace('/^\xEF\xBB\xBF/', '', $html) ?? $html;

        if (trim($html) === '') {
            throw new InvalidArgumentException('The uploaded HTML file is empty.');
        }

        return $html;
    }
}
