<?php

namespace Dth\Crm\Filament\Support;

use Illuminate\Support\HtmlString;

final class CrmPageUi
{
    public static function title(string $title, string $icon = 'crm', string $tone = 'teal'): HtmlString
    {
        return new HtmlString(sprintf(
            '<span class="dth-crm-page-title dth-crm-page-title--%s"><span class="dth-crm-page-title__icon" aria-hidden="true">%s</span><span>%s</span></span>',
            e($tone),
            self::iconSvg($icon),
            e($title),
        ));
    }

    private static function iconSvg(string $icon): string
    {
        return match ($icon) {
            'contact' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.2"/><path d="M5 20c.7-4 3-6.2 7-6.2S18.3 16 19 20"/></svg>',
            'company' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V6.5A1.5 1.5 0 0 1 5.5 5h8A1.5 1.5 0 0 1 15 6.5V21"/><path d="M15 10h3.5A1.5 1.5 0 0 1 20 11.5V21M8 9h3M8 13h3M8 17h3M18 14h.01M18 18h.01M2 21h20"/></svg>',
            'lead' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16l-6.4 7.1v5.2l-3.2 1.7v-6.9L4 5Z"/><path d="M17 3v4M15 5h4"/></svg>',
            'qualification' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="3" width="14" height="18" rx="2.5"/><path d="M9 8h6M9 12h6M9 16h3"/><path d="m14 16 1.5 1.5L18 15"/></svg>',
            'customer' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3"/><path d="M3.5 19c.6-3 2.4-5 5.5-5s4.9 2 5.5 5"/><circle cx="17" cy="9" r="2.3"/><path d="M15.2 14.7c2.7-.6 4.7.8 5.3 3.3"/></svg>',
            'agent' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="4" width="16" height="16" rx="2.5"/><circle cx="9" cy="10" r="2.2"/><path d="M6.2 16c.5-2 1.5-3 2.8-3s2.3 1 2.8 3M14 9h3M14 13h3"/></svg>',
            'distribution' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 5h10M7 19h10M5 7v10M19 7v10"/><path d="m8 9 4-4 4 4M8 15l4 4 4-4"/></svg>',
            'match' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 7h9a3 3 0 0 1 3 3v1"/><path d="m17 8 3 3 3-3"/><path d="M16 17H7a3 3 0 0 1-3-3v-1"/><path d="m7 16-3-3-3 3"/></svg>',
            'report' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg>',
            default => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v-2a2 2 0 0 1 2-2h2.7l7.8-3v14l-7.8-3H6a2 2 0 0 1-2-2v-2Z"/><path d="M19 8.5a4 4 0 0 1 0 7"/></svg>',
        };
    }
}
