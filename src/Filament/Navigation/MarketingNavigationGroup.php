<?php

namespace Dth\Marketing\Filament\Navigation;

use BackedEnum;
use Dth\Marketing\Support\UiText;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum MarketingNavigationGroup implements HasLabel, HasIcon
{
    case Marketing;

    public function getLabel(): string
    {
        return UiText::get('navigation.group', 'Marketing', context: 'navigation');
    }

    public function getIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-megaphone';
    }
}
