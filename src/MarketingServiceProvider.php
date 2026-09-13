<?php

namespace Dth\Marketing;

use Dth\Marketing\Contracts\AudienceProvider;
use Dth\Marketing\Contracts\CatalogProvider;
use Dth\Marketing\Contracts\EmailMarketingBridge;
use Dth\Marketing\Contracts\LeadProvider;
use Dth\Marketing\Contracts\RevenueProvider;
use Dth\Marketing\Integrations\Crm\NullAudienceProvider;
use Dth\Marketing\Integrations\Crm\NullLeadProvider;
use Dth\Marketing\Integrations\Email\NullEmailMarketingBridge;
use Dth\Marketing\Integrations\Finance\NullRevenueProvider;
use Dth\Marketing\Integrations\Sales\NullCatalogProvider;
use Dth\Marketing\Services\CampaignServiceScopeService;
use Dth\Marketing\Services\FormMappingRegistry;
use Dth\Marketing\Services\FormTemplateHtmlImportService;
use Dth\Marketing\Services\FormTemplateLifecycleService;
use Dth\Marketing\Services\FormTemplatePreviewService;
use Dth\Marketing\Services\HtmlFormParser;
use Dth\Marketing\Services\LandingPageCatalogService;
use Dth\Marketing\Services\LandingPageHtmlImportService;
use Dth\Marketing\Services\LandingPageLifecycleService;
use Dth\Marketing\Services\LandingPageRenderService;
use Dth\Marketing\Services\LandingPageUtmBuilder;
use Dth\Marketing\Services\MarketingCampaignLifecycleService;
use Dth\Marketing\Support\IntegrationHealthService;
use Illuminate\Support\ServiceProvider;

class MarketingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/marketing.php', 'dth-marketing');

        $this->bindIntegration(
            AudienceProvider::class,
            'audience_provider',
            NullAudienceProvider::class,
        );
        $this->bindIntegration(
            LeadProvider::class,
            'lead_provider',
            NullLeadProvider::class,
        );
        $this->bindIntegration(
            CatalogProvider::class,
            'catalog_provider',
            NullCatalogProvider::class,
        );
        $this->bindIntegration(
            RevenueProvider::class,
            'revenue_provider',
            NullRevenueProvider::class,
        );
        $this->bindIntegration(
            EmailMarketingBridge::class,
            'email_marketing_bridge',
            NullEmailMarketingBridge::class,
        );

        $this->app->singleton(IntegrationHealthService::class);
        $this->app->singleton(CampaignServiceScopeService::class);
        $this->app->singleton(MarketingCampaignLifecycleService::class);
        $this->app->singleton(FormMappingRegistry::class);
        $this->app->singleton(HtmlFormParser::class);
        $this->app->singleton(FormTemplateHtmlImportService::class);
        $this->app->singleton(FormTemplateLifecycleService::class);
        $this->app->singleton(FormTemplatePreviewService::class);
        $this->app->singleton(LandingPageCatalogService::class);
        $this->app->singleton(LandingPageHtmlImportService::class);
        $this->app->singleton(LandingPageLifecycleService::class);
        $this->app->singleton(LandingPageRenderService::class);
        $this->app->singleton(LandingPageUtmBuilder::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'dth-marketing');

        $this->publishes([
            __DIR__.'/../config/marketing.php' => config_path('dth-marketing.php'),
        ], 'dth-marketing-config');
    }

    /** @param class-string $contract @param class-string $fallback */
    private function bindIntegration(string $contract, string $configKey, string $fallback): void
    {
        // Do not overwrite an adapter already supplied by CRM / Sales / Finance /
        // Email or by the root application. This keeps module ownership one-way.
        if ($this->app->bound($contract)) {
            return;
        }

        $configured = config('dth-marketing.integrations.'.$configKey);

        if ($configured === null || $configured === '') {
            $this->app->singleton($contract, $fallback);

            return;
        }

        if (! is_string($configured) || ! class_exists($configured)) {
            throw new \InvalidArgumentException(
                "Invalid Marketing integration adapter configured for {$configKey}."
            );
        }

        if (! is_a($configured, $contract, true)) {
            throw new \InvalidArgumentException(
                "Marketing integration adapter {$configured} must implement {$contract}."
            );
        }

        $this->app->singleton($contract, $configured);
    }
}
