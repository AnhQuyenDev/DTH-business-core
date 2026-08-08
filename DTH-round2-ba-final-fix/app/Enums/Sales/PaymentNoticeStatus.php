<?php

namespace App\Enums\Sales;

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
        return match ($this) {
            self::Pending => 'warning',
            self::Verified => 'success',
            self::Rejected => 'danger',
        };
    }
}
