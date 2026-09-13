<?php

namespace Dth\Marketing\Support;

/**
 * Tiny dependency-free ZIP writer using STORE (no compression).
 * It is intentionally narrow: enough for deterministic XLSX generation without
 * forcing a host application to install ext-zip or a spreadsheet package.
 */
final class SimpleZipArchive
{
    /** @param array<string, string> $files */
    public function build(array $files): string
    {
        $local = '';
        $central = '';
        $offset = 0;
        $count = 0;
        [$dosTime, $dosDate] = $this->dosTimestamp();

        foreach ($files as $name => $contents) {
            $name = str_replace('\\', '/', ltrim($name, '/'));
            $crc = crc32($contents);
            $size = strlen($contents);
            $nameLength = strlen($name);

            $localHeader = pack(
                'VvvvvvVVVvv',
                0x04034b50,
                20,
                0,
                0,
                $dosTime,
                $dosDate,
                $crc,
                $size,
                $size,
                $nameLength,
                0,
            );
            $local .= $localHeader.$name.$contents;

            $centralHeader = pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                0,
                $dosTime,
                $dosDate,
                $crc,
                $size,
                $size,
                $nameLength,
                0,
                0,
                0,
                0,
                0,
                $offset,
            );
            $central .= $centralHeader.$name;
            $offset += strlen($localHeader) + $nameLength + $size;
            $count++;
        }

        $centralOffset = strlen($local);
        $centralSize = strlen($central);

        $end = pack(
            'VvvvvVVv',
            0x06054b50,
            0,
            0,
            $count,
            $count,
            $centralSize,
            $centralOffset,
            0,
        );

        return $local.$central.$end;
    }

    /** @return array{int,int} */
    private function dosTimestamp(): array
    {
        $parts = getdate();
        $year = max(1980, min(2107, (int) $parts['year']));
        $time = (((int) $parts['hours']) << 11)
            | (((int) $parts['minutes']) << 5)
            | intdiv((int) $parts['seconds'], 2);
        $date = (($year - 1980) << 9)
            | (((int) $parts['mon']) << 5)
            | (int) $parts['mday'];

        return [$time, $date];
    }
}
