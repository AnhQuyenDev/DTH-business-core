<?php

namespace Dth\Email\Support;

final class SpreadsheetExportSanitizer
{
    public static function value(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return self::text($value);
    }

    public static function text(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        // Spreadsheet applications can interpret values beginning with these
        // characters as formulas. Prefixing with an apostrophe keeps exported
        // recipient/campaign content as literal text.
        if (preg_match('/^[\x00-\x20]*[=+\-@]/u', $value) === 1) {
            return "'".$value;
        }

        return $value;
    }
}
