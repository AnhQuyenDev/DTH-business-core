<?php

namespace Dth\Commercial\Support;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

final class PageHeading
{
    public static function make(string $title, string|\BackedEnum|Htmlable|null $icon): Htmlable
    {
        return new HtmlString(view('dth-commercial::filament.components.page-title', [
            'title' => $title,
            'icon' => $icon,
        ])->render());
    }
}
