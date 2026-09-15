<?php

namespace Dth\Marketing\Filament\Support;

use Illuminate\Support\HtmlString;

final class MarketingPageUi
{
    public static function title(string $title, string $icon = 'marketing', string $tone = 'indigo'): HtmlString
    {
        return new HtmlString(sprintf(
            '<span class="dth-mkt-page-title dth-mkt-page-title--%s"><span class="dth-mkt-page-title__icon" aria-hidden="true">%s</span><span>%s</span></span>',
            e($tone),
            self::iconSvg($icon),
            e($title),
        ));
    }

    private static function iconSvg(string $icon): string
    {
        return match ($icon) {
            'campaign' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v-2.2a2 2 0 0 1 2-2h2.7l7.8-3.3v15l-7.8-3.3H6a2 2 0 0 1-2-2V12Z"/><path d="M8.7 16.2 10 20h2"/><path d="M19 8.3a4.5 4.5 0 0 1 0 7.4"/></svg>',
            'landing' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2.5"/><path d="M3 8h18"/><path d="M7 6h.01M10 6h.01"/><path d="m9 14 2 2 4-5"/></svg>',
            'form' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="3" width="16" height="18" rx="2.5"/><path d="M8 8h8M8 12h8M8 16h5"/><path d="M7 8h.01M7 12h.01M7 16h.01"/></svg>',
            'submission' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 3h10l4 4v14H5z"/><path d="M15 3v5h5"/><path d="M8 13h8M8 17h5"/></svg>',
            'audience' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><path d="M3.5 19c.6-3 2.4-5 5.5-5s4.9 2 5.5 5"/><circle cx="17" cy="9" r="2.3"/><path d="M15.2 14.7c2.7-.6 4.7.8 5.3 3.3"/></svg>',
            'segment' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16M7 10h10M10 15h4"/><circle cx="12" cy="20" r="1.5"/></svg>',
            'report' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg>',
            default => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v-2.2a2 2 0 0 1 2-2h2.7l7.8-3.3v15l-7.8-3.3H6a2 2 0 0 1-2-2V12Z"/><path d="M19 8.3a4.5 4.5 0 0 1 0 7.4"/></svg>',
        };
    }
}
