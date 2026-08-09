<?php

namespace App\Enums\Sales;

use App\Support\Ui\BadgePalette;

enum QuotationEmailStatus: string
{
    case Draft = 'draft';
    case Queued = 'queued';
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

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
            self::Draft => __('enum.sales.quotation_email_status.draft'),
            self::Queued => __('enum.sales.quotation_email_status.queued'),
            self::Sending => __('enum.sales.quotation_email_status.sending'),
            self::Sent => __('enum.sales.quotation_email_status.sent'),
            self::Failed => __('enum.sales.quotation_email_status.failed'),
            self::Cancelled => __('enum.sales.quotation_email_status.cancelled'),
        };
    }

    public function color(): string
    {
        $fallback = match ($this) {
            self::Draft => 'gray',
            self::Queued => 'info',
            self::Sending => 'info',
            self::Sent => 'success',
            self::Failed => 'danger',
            self::Cancelled => 'danger',
        };

        return BadgePalette::status($this, $fallback);
    }
}
