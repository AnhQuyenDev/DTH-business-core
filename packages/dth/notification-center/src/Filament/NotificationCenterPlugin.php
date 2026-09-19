<?php

namespace Dth\NotificationCenter\Filament;

use Dth\NotificationCenter\Filament\Pages\NotificationSettingsPage;
use Dth\NotificationCenter\Filament\Pages\NotificationsPage;
use Dth\NotificationCenter\Filament\Resources\NotificationLogResource;
use Dth\NotificationCenter\Filament\Resources\NotificationTemplateResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

final class NotificationCenterPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'dth-notification-center';
    }

    public function register(Panel $panel): void
    {
        if (! config('dth-notification-center.enabled', true)) {
            return;
        }

        $panel
            ->pages([
                NotificationsPage::class,
                NotificationSettingsPage::class,
            ])
            ->resources([
                NotificationTemplateResource::class,
                NotificationLogResource::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => view('dth-notification-center::filament.partials.ui-assets')->render(),
        );

        if (config('dth-notification-center.features.topbar_bell', true)) {
            FilamentView::registerRenderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => view('dth-notification-center::filament.partials.topbar-bell')->render(),
            );
        }
    }
}
