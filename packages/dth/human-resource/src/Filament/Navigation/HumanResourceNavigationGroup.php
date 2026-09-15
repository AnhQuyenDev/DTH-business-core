<?php

namespace Dth\HumanResource\Filament\Navigation;

use BackedEnum;
use Dth\HumanResource\Support\UiText;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum HumanResourceNavigationGroup implements HasLabel, HasIcon
{
    case HumanResource;

    public function getLabel(): string
    {
        return UiText::get('navigation.group', 'Human Resource', context: 'navigation');
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return Heroicon::OutlinedIdentification;
    }
}
