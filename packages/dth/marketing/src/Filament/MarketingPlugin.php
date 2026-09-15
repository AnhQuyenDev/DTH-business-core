<?php

namespace Dth\Marketing\Filament;

use Dth\Marketing\Filament\Pages\MarketingOverview;
use Dth\Marketing\Filament\Resources\ContactListResource;
use Dth\Marketing\Filament\Resources\FormTemplateResource;
use Dth\Marketing\Filament\Resources\LandingPageResource;
use Dth\Marketing\Filament\Resources\LandingPageSubmissionResource;
use Dth\Marketing\Filament\Resources\MarketingCampaignResource;
use Dth\Marketing\Filament\Resources\SegmentResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

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

        if (config('dth-marketing.features.public_submission', false)) {
            $resources[] = LandingPageSubmissionResource::class;
        }

        if (config('dth-marketing.features.audiences', false)) {
            $resources[] = ContactListResource::class;
        }

        if (config('dth-marketing.features.segments', false)) {
            $resources[] = SegmentResource::class;
        }

        $panel
            ->pages([
                MarketingOverview::class,
            ])
            ->resources($resources);
    }

    public function boot(Panel $panel): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => view('dth-marketing::filament.partials.ui-assets')->render(),
        );
    }
}
