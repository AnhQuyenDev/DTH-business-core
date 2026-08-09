<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AdminDashboard;
use App\Http\Middleware\SetLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName(fn (): string => company_name())
            ->brandLogo(fn () => view('filament.brand-logo'))
            ->brandLogoHeight('2rem')
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                AdminDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label(fn (): string => __('navigation.group.email_marketing'))
                    ->icon('heroicon-o-envelope'),
                NavigationGroup::make()
                    ->label(fn (): string => __('navigation.group.marketing'))
                    ->icon('heroicon-o-megaphone'),
                NavigationGroup::make()
                    ->label(fn (): string => __('navigation.group.crm'))
                    ->icon('heroicon-o-user-group'),
                NavigationGroup::make()
                    ->label(fn (): string => __('navigation.group.sales'))
                    ->icon('heroicon-o-briefcase'),
                NavigationGroup::make()
                    ->label(fn (): string => __('navigation.group.finance'))
                    ->icon('heroicon-o-banknotes'),
                NavigationGroup::make()
                    ->label(fn (): string => __('navigation.group.customer_care'))
                    ->icon('heroicon-o-heart'),
                NavigationGroup::make()
                    ->label(fn (): string => __('navigation.group.configuration'))
                    ->icon('heroicon-o-adjustments-horizontal'),
                NavigationGroup::make()
                    ->label(fn (): string => __('navigation.group.system'))
                    ->icon('heroicon-o-shield-check'),
            ])
            ->plugin(FilamentFullCalendarPlugin::make())
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label(fn (): string => app()->getLocale() === 'vi' ? __('action.switch_to_english') : __('action.switch_to_vietnamese'))
                    ->url(fn (): string => route('language.switch'))
                    ->icon('heroicon-o-language'),
            ])
            ->renderHook(PanelsRenderHook::HEAD_END, function (): string {
                $confirmMsg = json_encode(__('action.confirm_locale_switch'), JSON_UNESCAPED_UNICODE);

                return <<<HTML
<script>
(function() {
    var confirmMsg = {$confirmMsg};

    function getCSRFToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function refreshPageComponent(fallbackUrl) {
        var el = document.querySelector('[wire\\:id]');
        if (el && typeof Alpine !== 'undefined') {
            try {
                Alpine.evaluate(el, '\$wire.\$refresh()');
                return;
            } catch(e) {}
        }
        window.location.href = fallbackUrl;
    }

    document.addEventListener('click', function(e) {
        var link = e.target.closest('a[href*="/language/switch"]');
        if (!link) return;

        var dirtyEls = document.querySelectorAll('[wire\\:dirty]');
        if (dirtyEls.length > 0) {
            if (!confirm(confirmMsg)) {
                e.preventDefault();
                return;
            }
        }

        e.preventDefault();

        fetch('/language/switch', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': getCSRFToken(),
                'Accept': 'application/json',
            },
        }).then(function(res) {
            if (res.ok) {
                refreshPageComponent(link.href);
            } else {
                window.location.href = link.href;
            }
        }).catch(function() {
            window.location.href = link.href;
        });
    });
})();
</script>
HTML;
            })
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => view('filament.ui-system')->render(),
            );
    }
}
