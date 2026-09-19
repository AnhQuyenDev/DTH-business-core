<?php

namespace Dth\Commercial\Filament;

use Dth\Commercial\Filament\Pages\CommercialOverview;
use Dth\Commercial\Filament\Resources\BundleResource;
use Dth\Commercial\Filament\Resources\OpportunityResource;
use Dth\Commercial\Filament\Resources\ProductResource;
use Dth\Commercial\Filament\Resources\ServiceResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

final class CommercialPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'dth-commercial';
    }

    public function register(Panel $panel): void
    {
        if (! config('dth-commercial.enabled', true)) {
            return;
        }

        $resources = [];
        if (config('dth-commercial.features.catalog', true)) {
            $resources[] = ServiceResource::class;
        }
        if (config('dth-commercial.features.products', true)) {
            $resources[] = ProductResource::class;
        }
        if (config('dth-commercial.features.bundles', config('dth-commercial.features.packages', true))) {
            $resources[] = BundleResource::class;
        }
        if (config('dth-commercial.features.opportunities', true)) {
            $resources[] = OpportunityResource::class;
        }

        $pages = config('dth-commercial.features.analytics', true) ? [CommercialOverview::class] : [];
        $panel->pages($pages)->resources($resources);
    }

    public function boot(Panel $panel): void
    {
        if (! config('dth-commercial.enabled', true)) {
            return;
        }

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => view('dth-commercial::filament.partials.ui-assets')->render(),
        );
    }
}
