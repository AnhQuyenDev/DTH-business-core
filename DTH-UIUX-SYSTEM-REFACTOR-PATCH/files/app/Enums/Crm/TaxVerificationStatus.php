<?php

namespace App\Enums\Crm;

use App\Support\Ui\BadgePalette;

enum TaxVerificationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case NotFound = 'not_found';
    case Mismatch = 'mismatch';
    case Error = 'error';
    case ManualReview = 'manual_review';

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
            self::Pending => __('enum.tax.pending'),
            self::Verified => __('enum.tax.verified'),
            self::NotFound => __('enum.tax.not_found'),
            self::Mismatch => __('enum.tax.mismatch'),
            self::Error => __('enum.tax.error'),
            self::ManualReview => __('enum.tax.manual_review'),
        };
    }

    public function color(): string
    {
        $fallback = match ($this) {
            self::Verified => 'success',
            self::Pending, self::ManualReview => 'warning',
            self::NotFound, self::Mismatch, self::Error => 'danger',
        };

        return BadgePalette::status($this, $fallback);
    }

    public function isVerified(): bool
    {
        return $this === self::Verified;
    }
}
