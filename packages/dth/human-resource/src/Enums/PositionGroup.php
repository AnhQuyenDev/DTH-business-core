<?php

namespace Dth\HumanResource\Enums;

enum PositionGroup: string
{
    case Leadership = 'leadership';
    case Management = 'management';
    case Professional = 'professional';
    case Operations = 'operations';
    case Temporary = 'temporary';
    case Other = 'other';

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $group): array => [$group->value => $group->label()])->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Leadership => \Dth\HumanResource\Support\UiText::get('position_groups.leadership', 'Leadership'),
            self::Management => \Dth\HumanResource\Support\UiText::get('position_groups.management', 'Management'),
            self::Professional => \Dth\HumanResource\Support\UiText::get('position_groups.professional', 'Professional'),
            self::Operations => \Dth\HumanResource\Support\UiText::get('position_groups.operations', 'Operations'),
            self::Temporary => \Dth\HumanResource\Support\UiText::get('position_groups.temporary', 'Temporary'),
            self::Other => \Dth\HumanResource\Support\UiText::get('position_groups.other', 'Other'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Leadership => 'danger',
            self::Management => 'warning',
            self::Professional => 'info',
            self::Operations => 'success',
            self::Temporary, self::Other => 'gray',
        };
    }
}
