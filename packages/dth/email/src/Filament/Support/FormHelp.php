<?php

namespace Dth\Email\Filament\Support;

use Filament\Schemas\Components\Icon;

final class FormHelp
{
    public static function icon(string $tooltip): Icon
    {
        return Icon::make('heroicon-o-question-mark-circle')
            ->color('gray')
            ->tooltip($tooltip);
    }
}
