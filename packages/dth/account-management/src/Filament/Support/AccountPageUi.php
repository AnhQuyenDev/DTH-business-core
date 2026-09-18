<?php
namespace Dth\AccountManagement\Filament\Support;

use Illuminate\Support\HtmlString;

final class AccountPageUi
{
    public static function title(string $title, string $icon = 'user'): HtmlString
    {
        $svg = match ($icon) {
            'role' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75M12 3l7.5 3v5.25c0 4.57-3.13 8.79-7.5 9.75-4.37-.96-7.5-5.18-7.5-9.75V6L12 3Z"/></svg>',
            'group' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21C6.33 21 4.183 20.376 2.34 19.284l-.005-.002A6.75 6.75 0 0 1 12.728 12.3M12 6.75a4.5 4.5 0 1 1-9 0 4.5 4.5 0 0 1 9 0Zm6.75 1.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/></svg>',
            'security' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.5 10.5V6.75a4.5 4.5 0 0 0-9 0v3.75m-.75 0h10.5A2.25 2.25 0 0 1 19.5 12.75v6A2.25 2.25 0 0 1 17.25 21H6.75A2.25 2.25 0 0 1 4.5 18.75v-6a2.25 2.25 0 0 1 2.25-2.25Z"/></svg>',
            default => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.118a7.5 7.5 0 0 1 15 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.5-1.632Z"/></svg>',
        };
        return new HtmlString('<span class="dth-acc-page-title"><span class="dth-acc-page-title__icon">'.$svg.'</span><span>'.e($title).'</span></span>');
    }
}
