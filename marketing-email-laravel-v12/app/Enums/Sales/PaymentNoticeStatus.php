<?php

namespace App\Enums\Sales;

use App\Support\Ui\BadgePalette;

enum PaymentNoticeStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';

    public function label(): string
    {
        return __('enum.sales.payment_notice_status.'.$this->value);
    }

    public function color(): string
    {
        $fallback = match ($this) {
            self::Pending => 'warning',
            self::Verified => 'success',
            self::Rejected => 'danger',
        };

        return BadgePalette::status($this, $fallback);
    }
}
