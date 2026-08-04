<?php

namespace App\Enums\Marketing;

enum DnsStatus: string
{
    case Unknown = 'unknown';
    case Pending = 'pending';
    case Verified = 'verified';
    case Failed = 'failed';

    public static function values(): array
    {
        return array_map(static fn (self $status) => $status->value, self::cases());
    }

    public static function options(): array
    {
        return array_combine(self::values(), array_map(static fn (self $status) => $status->label(), self::cases()));
    }

    public function label(): string
    {
        return match ($this) {
            self::Unknown  => __('enum.dns_status.unknown'),
            self::Pending  => __('enum.dns_status.pending'),
            self::Verified => __('enum.dns_status.verified'),
            self::Failed   => __('enum.dns_status.failed'),
        };
    }
}