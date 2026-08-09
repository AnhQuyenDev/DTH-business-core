<?php

namespace App\Enums\Sales;

enum EmailStatus: string
{
    case Unsent = 'unsent';
    case Queued = 'queued';
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';

    public static function values(): array
    {
        return array_map(static fn (self $v) => $v->value, self::cases());
    }

    public static function options(): array
    {
        return array_combine(self::values(), array_map(static fn (self $v) => $v->label(), self::cases()));
    }

    public function label(): string
    {
        return match ($this) {
            self::Unsent => __('enum.sales.email_status.unsent'),
            self::Queued => __('enum.sales.email_status.queued'),
            self::Sending => __('enum.sales.email_status.sending'),
            self::Sent => __('enum.sales.email_status.sent'),
            self::Failed => __('enum.sales.email_status.failed'),
        };
    }

    public function color(): string
    {
        return \App\Support\Ui\BadgePalette::status($this);
    }

}
