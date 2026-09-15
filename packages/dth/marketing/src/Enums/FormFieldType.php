<?php

namespace Dth\Marketing\Enums;

use Dth\Marketing\Support\UiText;

enum FormFieldType: string
{
    case Text = 'text';
    case Email = 'email';
    case Phone = 'phone';
    case Date = 'date';
    case Textarea = 'textarea';
    case Select = 'select';
    case Checkbox = 'checkbox';
    case Hidden = 'hidden';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::Text->value => UiText::get('form.types.text', 'Text'),
            self::Email->value => UiText::get('form.types.email', 'Email'),
            self::Phone->value => UiText::get('form.types.phone', 'Phone'),
            self::Date->value => UiText::get('form.types.date', 'Date'),
            self::Textarea->value => UiText::get('form.types.textarea', 'Textarea'),
            self::Select->value => UiText::get('form.types.select', 'Select'),
            self::Checkbox->value => UiText::get('form.types.checkbox', 'Checkbox'),
            self::Hidden->value => UiText::get('form.types.hidden', 'Hidden'),
        ];
    }
}
