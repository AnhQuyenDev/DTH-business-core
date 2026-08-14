<?php

namespace App\Enums\Crm;

use App\Support\Ui\BadgePalette;

enum PositionAuthority: string
{
    case Executive = 'executive';
    case Manager = 'manager';
    case Lead = 'lead';
    case Member = 'member';
    case Limited = 'limited';

    public function label(): string
    {
        return __('configuration.position.authority_levels.'.$this->value);
    }

    public function isDepartmentManager(): bool
    {
        return in_array($this, [
            self::Executive,
            self::Manager,
        ], true);
    }

    public function defaultColor(): string
    {
        return match ($this) {
            self::Executive => 'danger',
            self::Manager => 'warning',
            self::Lead => 'info',
            self::Member => 'success',
            self::Limited => 'gray',
        };
    }

    public function color(): string
    {
        return BadgePalette::managed('organization.position_authority', $this, $this->defaultColor());
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(
                fn (self $level): array => [
                    $level->value => $level->label(),
                ]
            )
            ->all();
    }
}
