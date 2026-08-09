<?php

namespace App\Enums\Sales;

use App\Support\Ui\BadgePalette;

enum PaymentStatus: string
{
    case NotRequired = 'not_required';
    case Unpaid = 'unpaid';
    case PendingVerification = 'pending_verification';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';
    case Refunded = 'refunded';
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
            self::NotRequired => __('enum.sales.payment_status.not_required'),
            self::Unpaid => __('enum.sales.payment_status.unpaid'),
            self::PendingVerification => __('enum.sales.payment_status.pending_verification'),
            self::PartiallyPaid => __('enum.sales.payment_status.partially_paid'),
            self::Paid => __('enum.sales.payment_status.paid'),
            self::Refunded => __('enum.sales.payment_status.refunded'),
            self::Cancelled => __('enum.sales.payment_status.cancelled'),
        };
    }

    public function color(): string
    {
        $fallback = match ($this) {
            self::NotRequired => 'gray',
            self::Unpaid => 'warning',
            self::PendingVerification => 'warning',
            self::PartiallyPaid => 'info',
            self::Paid => 'success',
            self::Refunded => 'info',
            self::Cancelled => 'danger',
        };

        return BadgePalette::status($this, $fallback);
    }
}
