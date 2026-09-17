<?php

namespace Dth\Commercial;

use Dth\Commercial\Console\Commands\CommercialHealthCommand;
use Dth\Commercial\Services\CommercialAnalyticsService;
use Dth\Commercial\Services\CommercialInsightService;
use Dth\Commercial\Services\CommercialReportExportService;
use Dth\Commercial\Services\OpportunityCodeGenerator;
use Dth\Commercial\Services\OpportunityWorkflowService;
use Dth\Commercial\Support\SimplePdfWriter;
use Dth\Commercial\Support\SimpleXlsxWriter;
use Dth\Commercial\Support\SimpleZipArchive;
use Dth\Commercial\Support\CommercialAuthorization;
use Illuminate\Support\ServiceProvider;

final class CommercialServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/commercial.php', 'dth-commercial');
        $this->mergeModuleTranslations();

        if (! config('dth-commercial.enabled', true)) {
            return;
        }

        foreach ([
            OpportunityCodeGenerator::class,
            OpportunityWorkflowService::class,
            CommercialAnalyticsService::class,
            CommercialInsightService::class,
            CommercialReportExportService::class,
            SimplePdfWriter::class,
            SimpleXlsxWriter::class,
            SimpleZipArchive::class,
            CommercialAuthorization::class,
        ] as $service) {
            $this->app->singleton($service);
        }

        // Bind early when possible. The same bindings are refreshed in boot()
        // after every provider has completed register(), which keeps the module
        // attachable even when CRM/Marketing register their null adapters later.
        $this->bindMarketingCatalog();
        $this->bindCrmHandoff();
    }

    public function boot(): void
    {
        if (! config('dth-commercial.enabled', true)) {
            return;
        }

        // Laravel runs boot() only after all service providers have registered.
        // Refreshing here makes package load order irrelevant and avoids any
        // source modification inside CRM or Marketing just to install Commercial.
        $this->bindMarketingCatalog();
        $this->bindCrmHandoff();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'dth-commercial');

        $this->publishes([
            __DIR__.'/../config/commercial.php' => config_path('dth-commercial.php'),
        ], 'dth-commercial-config');

        if ($this->app->runningInConsole()) {
            $this->commands([CommercialHealthCommand::class]);
        }
    }

    /**
     * Keep Commercial translations physically inside the detachable package,
     * while still using the same root ui_t()/UiTranslator mechanism as the
     * other DTH modules. Host translations, when present, take precedence.
     */
    private function mergeModuleTranslations(): void
    {
        $path = __DIR__.'/../config/translations.php';
        if (! is_file($path)) {
            return;
        }

        /** @var array<string, array<string, mixed>> $translations */
        $translations = require $path;

        foreach ($translations as $locale => $moduleTranslations) {
            $key = "localization.translations.{$locale}.commercial";
            $hostTranslations = (array) config($key, []);

            config()->set(
                $key,
                array_replace_recursive($moduleTranslations, $hostTranslations),
            );
        }
    }

    private function bindMarketingCatalog(): void
    {
        $contract = 'Dth\\Marketing\\Contracts\\CatalogProvider';
        if (! interface_exists($contract) || ! $this->canReplaceIntegration($contract, 'Dth\\Marketing\\Integrations\\Sales\\NullCatalogProvider')) {
            return;
        }

        $this->app->singleton(
            $contract,
            \Dth\Commercial\Integrations\Marketing\DthMarketingCatalogProvider::class,
        );
    }

    private function bindCrmHandoff(): void
    {
        $contract = 'Dth\\Crm\\Contracts\\SalesHandoffProvider';
        if (! interface_exists($contract) || ! $this->canReplaceIntegration($contract, 'Dth\\Crm\\Integrations\\Sales\\NullSalesHandoffProvider')) {
            return;
        }

        $this->app->singleton(
            $contract,
            \Dth\Commercial\Integrations\Crm\DthCrmSalesHandoffProvider::class,
        );
    }

    private function canReplaceIntegration(string $contract, string $fallback): bool
    {
        if (! $this->app->bound($contract)) {
            return true;
        }

        $binding = $this->app->getBindings()[$contract]['concrete'] ?? null;
        if ($binding instanceof \Closure) {
            $binding = (new \ReflectionFunction($binding))->getStaticVariables()['concrete'] ?? null;
        }

        return is_string($binding) && $binding === $fallback;
    }
}
