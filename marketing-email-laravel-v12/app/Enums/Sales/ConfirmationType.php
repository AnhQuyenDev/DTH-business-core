<?php

namespace App\Enums\Sales;

enum ConfirmationType: string
{
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case RevisionRequested = 'revision_requested';

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
            self::Accepted => __('enum.sales.confirmation_type.accepted'),
            self::Rejected => __('enum.sales.confirmation_type.rejected'),
            self::RevisionRequested => __('enum.sales.confirmation_type.revision_requested'),
        };
    }
}
