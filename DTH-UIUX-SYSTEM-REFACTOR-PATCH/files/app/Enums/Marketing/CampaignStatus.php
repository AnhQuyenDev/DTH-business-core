<?php

namespace App\Enums\Marketing;

use App\Support\Ui\BadgePalette;

enum CampaignStatus: string
{
    case Draft = 'draft';
    case Testing = 'testing';
    case Scheduled = 'scheduled';
    case Preparing = 'preparing';
    case Sending = 'sending';
    case Sent = 'sent';
    case Paused = 'paused';
    case Cancelled = 'cancelled';
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
            self::Draft => __('enum.campaign_status.draft'),
            self::Testing => __('enum.campaign_status.testing'),
            self::Scheduled => __('enum.campaign_status.scheduled'),
            self::Preparing => __('enum.campaign_status.preparing'),
            self::Sending => __('enum.campaign_status.sending'),
            self::Sent => __('enum.campaign_status.sent'),
            self::Paused => __('enum.campaign_status.paused'),
            self::Cancelled => __('enum.campaign_status.cancelled'),
            self::Failed => __('enum.campaign_status.failed'),
        };
    }

    public function color(): string
    {
        return BadgePalette::status($this);
    }

}
