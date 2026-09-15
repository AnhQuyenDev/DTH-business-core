<?php

namespace Dth\Email\Filament\Support;

use Illuminate\Support\HtmlString;

final class EmailPageUi
{
    public static function title(string $title, string $icon = 'envelope', string $tone = 'amber'): HtmlString
    {
        $title = e($title);

        return new HtmlString(sprintf(
            '<span class="dth-email-page-title dth-email-page-title--%s"><span class="dth-email-page-title__icon" aria-hidden="true">%s</span><span>%s</span></span>',
            e($tone),
            self::iconSvg($icon),
            $title,
        ));
    }

    private static function iconSvg(string $icon): string
    {
        return match ($icon) {
            'campaign' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5.75A2.75 2.75 0 0 1 5.75 3h12.5A2.75 2.75 0 0 1 21 5.75v12.5A2.75 2.75 0 0 1 18.25 21H5.75A2.75 2.75 0 0 1 3 18.25V5.75Z" /><path d="m5.6 7.2 5.16 4.02a2 2 0 0 0 2.48 0L18.4 7.2" /></svg>',
            'template' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5.75A2.75 2.75 0 0 1 6.75 3h10.5A2.75 2.75 0 0 1 20 5.75v12.5A2.75 2.75 0 0 1 17.25 21H6.75A2.75 2.75 0 0 1 4 18.25V5.75Z" /><path d="M8 8h8M8 12h8M8 16h5" /></svg>',
            'folder' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7.75A2.75 2.75 0 0 1 5.75 5h4.1c.56 0 1.1.22 1.5.62l1.28 1.28c.4.4.94.62 1.5.62h4.12A2.75 2.75 0 0 1 21 10.27v7.98A2.75 2.75 0 0 1 18.25 21H5.75A2.75 2.75 0 0 1 3 18.25V7.75Z" /></svg>',
            'log' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6.75 3h10.5A2.75 2.75 0 0 1 20 5.75v12.5A2.75 2.75 0 0 1 17.25 21H6.75A2.75 2.75 0 0 1 4 18.25V5.75A2.75 2.75 0 0 1 6.75 3Z"/><path d="M8 8h8M8 12h8M8 16h5"/><path d="M15.5 16 17 17.5l2.5-2.5"/></svg>',
            'shield' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 5.75 5.5v5.62c0 4.2 2.64 8 6.25 9.88 3.61-1.88 6.25-5.69 6.25-9.88V5.5L12 3Z" /><path d="m9.5 12 1.7 1.7 3.3-3.7" /></svg>',
            'account' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7.75A2.75 2.75 0 0 1 6.75 5h10.5A2.75 2.75 0 0 1 20 7.75v8.5A2.75 2.75 0 0 1 17.25 19H6.75A2.75 2.75 0 0 1 4 16.25v-8.5Z" /><path d="m6 9 6 4 6-4" /></svg>',
            'domain' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a15.3 15.3 0 0 1 0 18"/><path d="M12 3a15.3 15.3 0 0 0 0 18"/></svg>',
            default => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 5.75A2.75 2.75 0 0 1 5.75 3h12.5A2.75 2.75 0 0 1 21 5.75v12.5A2.75 2.75 0 0 1 18.25 21H5.75A2.75 2.75 0 0 1 3 18.25V5.75Z" /><path d="m5.6 7.2 5.16 4.02a2 2 0 0 0 2.48 0L18.4 7.2" /></svg>',
        };
    }
}
