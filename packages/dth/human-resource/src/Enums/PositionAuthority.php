<?php

namespace Dth\HumanResource\Enums;

enum PositionAuthority: string
{
    case Executive = 'executive';
    case Manager = 'manager';
    case Lead = 'lead';
    case Member = 'member';
    case Limited = 'limited';

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $level): array => [$level->value => $level->label()])->all();
    }

    public function label(): string
    {
        return match ($this) {
            self::Executive => \Dth\HumanResource\Support\UiText::get('position_authority.executive', 'Executive'),
            self::Manager => \Dth\HumanResource\Support\UiText::get('position_authority.manager', 'Manager'),
            self::Lead => \Dth\HumanResource\Support\UiText::get('position_authority.lead', 'Team lead'),
            self::Member => \Dth\HumanResource\Support\UiText::get('position_authority.member', 'Member'),
            self::Limited => \Dth\HumanResource\Support\UiText::get('position_authority.limited', 'Limited'),
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Executive => 'danger',
            self::Manager => 'warning',
            self::Lead => 'info',
            self::Member => 'success',
            self::Limited => 'gray',
        };
    }

    public function isManager(): bool
    {
        return in_array($this, [self::Executive, self::Manager], true);
    }
}
