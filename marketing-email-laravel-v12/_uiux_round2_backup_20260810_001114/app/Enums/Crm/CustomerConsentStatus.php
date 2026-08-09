<?php

namespace App\Enums\Crm;

enum CustomerConsentStatus: string
{
    case Pending = 'pending';
    case Subscribed = 'subscribed';
    case Unsubscribed = 'unsubscribed';
    case Bounced = 'bounced';
    case Complained = 'complained';
    case DoNotContact = 'do_not_contact';

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
            self::Pending => __('enum.consent.pending'),
            self::Subscribed => __('enum.consent.subscribed'),
            self::Unsubscribed => __('enum.consent.unsubscribed'),
            self::Bounced => __('enum.consent.bounced'),
            self::Complained => __('enum.consent.complained'),
            self::DoNotContact => __('enum.consent.do_not_contact'),
        };
    }

    public function isMarketable(): bool
    {
        return $this === self::Subscribed;
    }
}
