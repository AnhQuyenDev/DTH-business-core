<?php

namespace App\Enums\Crm;

use App\Support\Ui\BadgePalette;

enum PositionGroup: string
{
    case Leadership = 'leadership';
    case Management = 'management';
    case Professional = 'professional';
    case Operations = 'operations';
    case Temporary = 'temporary';
    case Other = 'other';

    public function label(): string
    {
        return __('configuration.position.groups.'.$this->value);
    }

    public function defaultColor(): string
    {
        return match ($this) {
            self::Leadership => 'danger',
            self::Management => 'warning',
            self::Professional => 'info',
            self::Operations => 'success',
            self::Temporary => 'gray',
            self::Other => 'gray',
        };
    }

    public function color(): string
    {
        return BadgePalette::managed('organization.position_group', $this, $this->defaultColor());
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $group): array => [$group->value => $group->label()])
            ->all();
    }
}
