<?php

namespace Dth\Crm\Filament;

use Dth\Crm\Filament\Pages\CrmOverview;
use Dth\Crm\Filament\Resources\BusinessContactResource;
use Dth\Crm\Filament\Resources\CompanyResource;
use Dth\Crm\Filament\Resources\ContactQualificationResource;
use Dth\Crm\Filament\Resources\ContactResource;
use Dth\Crm\Filament\Resources\CrmAgentProfileResource;
use Dth\Crm\Filament\Resources\CustomerDistributionBatchResource;
use Dth\Crm\Filament\Resources\CustomerResource;
use Dth\Crm\Filament\Resources\LeadResource;
use Dth\Crm\Filament\Resources\PersonalContactResource;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;

class CrmPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'dth-crm';
    }

    public function register(Panel $panel): void
    {
        if (! config('dth-crm.enabled', true)) {
            return;
        }

        $panel
            ->pages([
                CrmOverview::class,
            ])
            ->resources([
                ContactResource::class,
                PersonalContactResource::class,
                BusinessContactResource::class,
                CompanyResource::class,
                LeadResource::class,
                ContactQualificationResource::class,
                CustomerResource::class,
                CrmAgentProfileResource::class,
                CustomerDistributionBatchResource::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => view('dth-crm::filament.partials.ui-assets')->render(),
        );
    }
}
