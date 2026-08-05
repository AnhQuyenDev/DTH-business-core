<?php

namespace App\Enums\Marketing;

enum FormFieldType: string
{
    case Text = 'text';
    case Email = 'email';
    case Phone = 'phone';
    case Textarea = 'textarea';
    case Select = 'select';
    case Checkbox = 'checkbox';
    case Hidden = 'hidden';

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
            self::Text => __('enum.form_field_type.text'),
            self::Email => __('enum.form_field_type.email'),
            self::Phone => __('enum.form_field_type.phone'),
            self::Textarea => __('enum.form_field_type.textarea'),
            self::Select => __('enum.form_field_type.select'),
            self::Checkbox => __('enum.form_field_type.checkbox'),
            self::Hidden => __('enum.form_field_type.hidden'),
        };
    }
}
