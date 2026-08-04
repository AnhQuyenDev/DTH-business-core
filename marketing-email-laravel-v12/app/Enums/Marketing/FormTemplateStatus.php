<?php

namespace App\Enums\Marketing;

enum FormTemplateStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Archived = 'archived';

    public static function values(): array
    {
        return array_map(static fn (self $s) => $s->value, self::cases());
    }

    public static function options(): array
    {
        return array_combine(self::values(), array_map(static fn (self $s) => $s->label(), self::cases()));
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft    => __('enum.form_template_status.draft'),
            self::Active   => __('enum.form_template_status.active'),
            self::Archived => __('enum.form_template_status.archived'),
        };
    }
}
