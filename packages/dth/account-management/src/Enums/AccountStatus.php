<?php

namespace Dth\AccountManagement\Enums;

use Dth\AccountManagement\Support\UiText;

enum AccountStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
    case Pending = 'pending';

    public static function options(): array
    {
        return [
            self::Active->value => UiText::get('status.active', 'Hoạt động'),
            self::Inactive->value => UiText::get('status.inactive', 'Không hoạt động'),
            self::Suspended->value => UiText::get('status.suspended', 'Tạm khóa'),
            self::Pending->value => UiText::get('status.pending', 'Chờ kích hoạt'),
        ];
    }
}
