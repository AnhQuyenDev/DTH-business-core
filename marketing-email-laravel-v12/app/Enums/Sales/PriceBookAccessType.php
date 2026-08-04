<?php

namespace App\Enums\Sales;

enum PriceBookAccessType: string
{
    case All = 'all';
    case Role = 'role';
    case Department = 'department';
    case Staff = 'staff';
    case Branch = 'branch';

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
            self::All => __('enum.sales.price_book_access_type.all'),
            self::Role => __('enum.sales.price_book_access_type.role'),
            self::Department => __('enum.sales.price_book_access_type.department'),
            self::Staff => __('enum.sales.price_book_access_type.staff'),
            self::Branch => __('enum.sales.price_book_access_type.branch'),
        };
    }
}
