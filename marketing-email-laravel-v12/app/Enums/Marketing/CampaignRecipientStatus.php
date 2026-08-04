<?php

namespace App\Enums\Marketing;

enum CampaignRecipientStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Opened = 'opened';
    case Clicked = 'clicked';
    case Failed = 'failed';
    case Bounced = 'bounced';
    case Unsubscribed = 'unsubscribed';
    case Skipped = 'skipped';

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
            self::Pending       => __('enum.campaign_recipient_status.pending'),
            self::Queued        => __('enum.campaign_recipient_status.queued'),
            self::Sent          => __('enum.campaign_recipient_status.sent'),
            self::Delivered     => __('enum.campaign_recipient_status.delivered'),
            self::Opened        => __('enum.campaign_recipient_status.opened'),
            self::Clicked       => __('enum.campaign_recipient_status.clicked'),
            self::Failed        => __('enum.campaign_recipient_status.failed'),
            self::Bounced       => __('enum.campaign_recipient_status.bounced'),
            self::Unsubscribed  => __('enum.campaign_recipient_status.unsubscribed'),
            self::Skipped       => __('enum.campaign_recipient_status.skipped'),
        };
    }
}