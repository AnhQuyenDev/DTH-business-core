<?php

namespace Dth\AccountManagement\Enums;

use Dth\AccountManagement\Support\UiText;

enum InvitationStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Expired = 'expired';
    case Revoked = 'revoked';

    public static function options(): array
    {
        return [
            self::Pending->value => UiText::get('invitation_status.pending', 'Chờ xử lý'),
            self::Accepted->value => UiText::get('invitation_status.accepted', 'Đã chấp nhận'),
            self::Expired->value => UiText::get('invitation_status.expired', 'Đã hết hạn'),
            self::Revoked->value => UiText::get('invitation_status.revoked', 'Đã thu hồi'),
        ];
    }
}
