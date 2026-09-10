<?php

namespace Dth\Email\Filament\Navigation;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum EmailNavigationGroup implements HasLabel, HasIcon
{
    case Email;

    public function getLabel(): string
    {
        return 'Email';
    }

    public function getIcon(): string | BackedEnum | Htmlable | null
    {
        return Heroicon::OutlinedEnvelope;
    }
}