<?php

namespace App\Enums\Marketing;

enum ContactConsentStatus: string
{
    case Subscribed = 'subscribed';
    case Unsubscribed = 'unsubscribed';
    case Pending = 'pending';
    case Bounced = 'bounced';
    case Complained = 'complained';
    case DoNotContact = 'do_not_contact';

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
            self::Subscribed    => __('enum.consent.subscribed'),
            self::Unsubscribed  => __('enum.consent.unsubscribed'),
            self::Pending       => __('enum.consent.pending'),
            self::Bounced       => __('enum.consent.bounced'),
            self::Complained    => __('enum.consent.complained'),
            self::DoNotContact  => __('enum.consent.do_not_contact'),
        };
    }
}