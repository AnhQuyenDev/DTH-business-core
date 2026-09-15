<?php

namespace Dth\HumanResource\Enums;

enum EmploymentStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Resigned = 'resigned';

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => \Dth\HumanResource\Support\UiText::get('status.employment.active', 'Active'),
            self::Inactive => \Dth\HumanResource\Support\UiText::get('status.employment.inactive', 'Inactive'),
            self::Resigned => \Dth\HumanResource\Support\UiText::get('status.employment.resigned', 'Resigned'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'warning',
            self::Resigned => 'gray',
        };
    }
}
