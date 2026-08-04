<?php

namespace App\Enums\Marketing;

enum SendingAccountStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Testing = 'testing';

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
            self::Active   => __('enum.sending_account_status.active'),
            self::Inactive => __('enum.sending_account_status.inactive'),
            self::Testing  => __('enum.sending_account_status.testing'),
        };
    }
}