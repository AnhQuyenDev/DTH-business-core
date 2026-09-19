<?php

namespace Dth\NotificationCenter\Filament\Navigation;

use Dth\NotificationCenter\Support\UiText;
use Filament\Support\Contracts\HasLabel;

enum NotificationNavigationGroup: string implements HasLabel
{
    case Notifications = 'notifications';

    public function getLabel(): ?string
    {
        return UiText::get('navigation.notifications', 'Thông báo');
    }
}
