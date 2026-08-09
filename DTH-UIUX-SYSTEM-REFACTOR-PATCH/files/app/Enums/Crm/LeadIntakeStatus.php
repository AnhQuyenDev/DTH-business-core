<?php

namespace App\Enums\Crm;

use App\Support\Ui\BadgePalette;

enum LeadIntakeStatus: string
{
    case New = 'new';
    case Active = 'active';
    case Duplicate = 'duplicate';
    case Spam = 'spam';
    case Closed = 'closed';
    case ConvertedToOpportunity = 'converted_to_opportunity';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Mới',
            self::Active => 'Đang xử lý',
            self::Duplicate => 'Trùng lặp',
            self::Spam => 'Spam',
            self::Closed => 'Đã đóng',
            self::ConvertedToOpportunity => 'Đã tạo cơ hội',
        };
    }

    public function color(): string
    {
        $fallback = match ($this) {
            self::New => 'gray',
            self::Active => 'info',
            self::Duplicate => 'warning',
            self::Spam => 'danger',
            self::Closed => 'gray',
            self::ConvertedToOpportunity => 'success',
        };

        return BadgePalette::status($this, $fallback);
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [
                $status->value => $status->label(),
            ])
            ->all();
    }
}
