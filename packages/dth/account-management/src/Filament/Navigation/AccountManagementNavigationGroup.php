<?php

namespace Dth\AccountManagement\Filament\Navigation;

use BackedEnum;
use Dth\AccountManagement\Support\UiText;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum AccountManagementNavigationGroup implements HasLabel, HasIcon
{
    case Accounts;

    public function getLabel(): string
    {
        return UiText::get('navigation.group', 'Tài khoản & Phân quyền', context: 'navigation');
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return Heroicon::OutlinedShieldCheck;
    }
}
