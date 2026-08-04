<?php

namespace App\Enums\Sales;

enum DocumentType: string
{
    case Pdf = 'pdf';
    case SignedSnapshot = 'signed_snapshot';

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
            self::Pdf => __('enum.sales.document_type.pdf'),
            self::SignedSnapshot => __('enum.sales.document_type.signed_snapshot'),
        };
    }
}
