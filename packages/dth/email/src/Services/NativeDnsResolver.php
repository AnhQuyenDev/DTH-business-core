<?php

namespace Dth\Email\Services;

use Dth\Email\Contracts\DnsResolver;

class NativeDnsResolver implements DnsResolver
{
    public function txt(string $host): array
    {
        $records = dns_get_record($host, DNS_TXT);

        if ($records === false) {
            return [];
        }

        $values = [];

        foreach ($records as $record) {
            $value = $record['txt'] ?? null;

            if (is_string($value) && $value !== '') {
                $values[] = $value;
                continue;
            }

            if (isset($record['entries']) && is_array($record['entries'])) {
                $joined = implode('', array_filter($record['entries'], 'is_string'));

                if ($joined !== '') {
                    $values[] = $joined;
                }
            }
        }

        return array_values(array_unique($values));
    }
}
