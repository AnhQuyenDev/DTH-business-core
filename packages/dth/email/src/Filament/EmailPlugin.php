<?php

namespace Dth\Email\Filament;

use Dth\Email\Filament\Pages\EmailDashboard;
use Dth\Email\Filament\Resources\EmailCampaignResource;
use Dth\Email\Filament\Resources\EmailDeliveryLogResource;
use Dth\Email\Filament\Resources\EmailSuppressionResource;
use Dth\Email\Filament\Resources\EmailTemplateCategoryResource;
use Dth\Email\Filament\Resources\EmailTemplateResource;
use Dth\Email\Filament\Resources\SendingAccountResource;
use Dth\Email\Filament\Resources\SendingDomainResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

class EmailPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'dth-email';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([
            EmailDashboard::class,
        ]);

        $panel->resources([
            SendingDomainResource::class,
            SendingAccountResource::class,
            EmailTemplateCategoryResource::class,
            EmailTemplateResource::class,
            EmailCampaignResource::class,
            EmailDeliveryLogResource::class,
            EmailSuppressionResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => view('dth-email::filament.partials.ui-assets')->render(),
        );
    }
}
