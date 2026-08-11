<?php

namespace App\Enums\Crm;

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
