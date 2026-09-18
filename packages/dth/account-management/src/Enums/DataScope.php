<?php

namespace Dth\AccountManagement\Enums;

use Dth\AccountManagement\Support\UiText;

enum DataScope: string
{
    case Own = 'own';
    case Team = 'team';
    case All = 'all';

    public static function options(): array
    {
        return [
            self::Own->value => UiText::get('data_scope.own', 'Chỉ dữ liệu của tôi'),
            self::Team->value => UiText::get('data_scope.team', 'Dữ liệu nhóm'),
            self::All->value => UiText::get('data_scope.all', 'Toàn bộ dữ liệu'),
        ];
    }

    public function rank(): int
    {
        return match ($this) {
            self::Own => 10,
            self::Team => 20,
            self::All => 30,
        };
    }
}
