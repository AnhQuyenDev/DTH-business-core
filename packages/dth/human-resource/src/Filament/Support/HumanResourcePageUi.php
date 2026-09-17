<?php

namespace Dth\HumanResource\Filament\Support;

use Illuminate\Support\HtmlString;

final class HumanResourcePageUi
{
    public static function title(string $title, string $icon = 'employee', string $tone = 'blue'): HtmlString
    {
        return new HtmlString(sprintf(
            '<span class="dth-hr-page-title dth-hr-page-title--%s"><span class="dth-hr-page-title__icon" aria-hidden="true">%s</span><span>%s</span></span>',
            e($tone),
            self::iconSvg($icon),
            e($title),
        ));
    }

    private static function iconSvg(string $icon): string
    {
        return match ($icon) {
            'department' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V6.5A1.5 1.5 0 0 1 5.5 5h8A1.5 1.5 0 0 1 15 6.5V21"/><path d="M15 10h3.5A1.5 1.5 0 0 1 20 11.5V21M8 9h3M8 13h3M8 17h3M18 14h.01M18 18h.01M2 21h20"/></svg>',
            'position' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3.5" y="7" width="17" height="12" rx="2.5"/><path d="M9 7V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5V7M3.5 11.5h17M10 11.5v2h4v-2"/></svg>',
            'report' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg>',
            'availability' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="16" rx="2.5"/><path d="M7 3v4M17 3v4M3 10h18"/><path d="m8 15 2 2 5-5"/></svg>',
            'function' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="6" height="6" rx="1.5"/><rect x="14" y="4" width="6" height="6" rx="1.5"/><rect x="4" y="14" width="6" height="6" rx="1.5"/><rect x="14" y="14" width="6" height="6" rx="1.5"/></svg>',
            default => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.2"/><path d="M5 20c.7-4 3-6.2 7-6.2S18.3 16 19 20"/><path d="M18.5 5.5v4M16.5 7.5h4"/></svg>',
        };
    }
}
