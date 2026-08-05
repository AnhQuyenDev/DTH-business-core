<?php

namespace App\Enums\Marketing;

enum EmailEventType: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Opened = 'opened';
    case Clicked = 'clicked';
    case Failed = 'failed';
    case Bounced = 'bounced';
    case Complained = 'complained';
    case Unsubscribed = 'unsubscribed';
    case Skipped = 'skipped';

    public static function values(): array
    {
        return array_map(static fn (self $eventType) => $eventType->value, self::cases());
    }

    public static function options(): array
    {
        return array_combine(self::values(), array_map(static fn (self $eventType) => $eventType->label(), self::cases()));
    }

    public function label(): string
    {
        return match ($this) {
            self::Queued => __('enum.email_event.queued'),
            self::Sent => __('enum.email_event.sent'),
            self::Delivered => __('enum.email_event.delivered'),
            self::Opened => __('enum.email_event.opened'),
            self::Clicked => __('enum.email_event.clicked'),
            self::Failed => __('enum.email_event.failed'),
            self::Bounced => __('enum.email_event.bounced'),
            self::Complained => __('enum.email_event.complained'),
            self::Unsubscribed => __('enum.email_event.unsubscribed'),
            self::Skipped => __('enum.email_event.skipped'),
        };
    }
}
