<?php

namespace Dth\Marketing\Filament;

use Dth\Marketing\Filament\Pages\MarketingOverview;
use Dth\Marketing\Filament\Resources\FormTemplateResource;
use Dth\Marketing\Filament\Resources\LandingPageResource;
use Dth\Marketing\Filament\Resources\MarketingCampaignResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class MarketingPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'dth-marketing';
    }

    public function register(Panel $panel): void
    {
        if (! config('dth-marketing.enabled', true)) {
            return;
        }

        $resources = [];

        if (config('dth-marketing.features.campaigns', false)) {
            $resources[] = MarketingCampaignResource::class;
        }

        if (config('dth-marketing.features.landing_pages', false)) {
            $resources[] = LandingPageResource::class;
        }

        if (config('dth-marketing.features.form_templates', false)) {
            $resources[] = FormTemplateResource::class;
        }

        $panel
            ->pages([
                MarketingOverview::class,
            ])
            ->resources($resources);
    }

    public function boot(Panel $panel): void
    {
        // Reserved for Marketing-only panel hooks. Email hooks remain isolated.
    }
}
