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
        return match ($this) {
            self::Pending => 'Chờ đối soát',
            self::Verified => 'Đã xác minh',
            self::Rejected => 'Không khớp',
        };
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
