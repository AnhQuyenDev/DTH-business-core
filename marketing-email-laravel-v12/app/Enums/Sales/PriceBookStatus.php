<?php

namespace App\Enums\Sales;

enum PriceBookStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
    case Expired = 'expired';
    case Archived = 'archived';

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
            self::Draft => __('enum.sales.price_book_status.draft'),
            self::Active => __('enum.sales.price_book_status.active'),
            self::Inactive => __('enum.sales.price_book_status.inactive'),
            self::Expired => __('enum.sales.price_book_status.expired'),
            self::Archived => __('enum.sales.price_book_status.archived'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive, self::Expired => 'danger',
            self::Draft => 'gray',
            self::Archived => 'danger',
        };
    }
}
