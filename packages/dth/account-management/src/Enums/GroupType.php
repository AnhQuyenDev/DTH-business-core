<?php

namespace Dth\AccountManagement\Enums;

use Dth\AccountManagement\Support\UiText;

enum GroupType: string
{
    case Team = 'team';
    case Department = 'department';
    case Project = 'project';
    case Custom = 'custom';

    public static function options(): array
    {
        return [
            self::Team->value => UiText::get('group_type.team', 'Nhóm'),
            self::Department->value => UiText::get('group_type.department', 'Đơn vị'),
            self::Project->value => UiText::get('group_type.project', 'Dự án'),
            self::Custom->value => UiText::get('group_type.custom', 'Tùy chỉnh'),
        ];
    }
}
