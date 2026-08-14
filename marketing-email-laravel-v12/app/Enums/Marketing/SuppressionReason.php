<?php

namespace App\Enums\Marketing;

use App\Support\Ui\BadgePalette;

enum SuppressionReason: string
{
    case Unsubscribe = 'unsubscribe';
    case Bounce = 'bounce';
    case Complaint = 'complaint';
    case Manual = 'manual';
    case InvalidEmail = 'invalid_email';
    case DoNotContact = 'do_not_contact';

    public static function values(): array
    {
        return array_map(static fn (self $reason) => $reason->value, self::cases());
    }

    public static function options(): array
    {
        return array_combine(self::values(), array_map(static fn (self $reason) => $reason->label(), self::cases()));
    }

    public function label(): string
    {
        return match ($this) {
            self::Unsubscribe => __('enum.suppression_reason.unsubscribe'),
            self::Bounce => __('enum.suppression_reason.bounce'),
            self::Complaint => __('enum.suppression_reason.complaint'),
            self::Manual => __('enum.suppression_reason.manual'),
            self::InvalidEmail => __('enum.suppression_reason.invalid_email'),
            self::DoNotContact => __('enum.suppression_reason.do_not_contact'),
        };
    }

    public function color(): string
    {
        $fallback = match ($this) {
            self::Manual => 'warning',
            default => 'danger',
        };

        return BadgePalette::managed('marketing.suppression_reason', $this, $fallback);
    }
}
