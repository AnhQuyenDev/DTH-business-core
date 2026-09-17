<?php

namespace Dth\Commercial\Filament\Navigation;

use BackedEnum;
use Dth\Commercial\Support\UiText;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

// Public name intentionally replaces the legacy "Sales" module label.
enum CommercialNavigationGroup implements HasLabel, HasIcon
{
    case Commercial;

    public function getLabel(): string
    {
        return UiText::get('navigation.group', 'Services & Commercial', context: 'navigation');
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-briefcase';
    }
}
