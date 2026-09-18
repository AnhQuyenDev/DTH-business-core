<?php

namespace Dth\Crm;

use Dth\Crm\Console\Commands\CrmHealthCommand;
use Dth\Crm\Contracts\SalesHandoffProvider;
use Dth\Crm\Contracts\TaxVerificationProvider;
use Dth\Crm\Integrations\Sales\NullSalesHandoffProvider;
use Dth\Crm\Models\Company;
use Dth\Crm\Models\CompanyMatchCandidate;
use Dth\Crm\Models\Contact;
use Dth\Crm\Models\ContactQualification;
use Dth\Crm\Models\CrmAgentProfile;
use Dth\Crm\Models\Customer;
use Dth\Crm\Models\CustomerDistributionBatch;
use Dth\Crm\Models\Lead;
use Dth\Crm\Policies\CrmResourcePolicy;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\ServiceProvider;

final class CrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/crm.php', 'dth-crm');
        $this->app->singleton(SalesHandoffProvider::class, NullSalesHandoffProvider::class);
        $this->app->singleton(TaxVerificationProvider::class, fn () => new class implements TaxVerificationProvider {
            public function verify(?string $taxCode): array
            {
                return ['verified' => null, 'tax_code' => $taxCode, 'provider' => 'none'];
            }
        });

    }

    public function boot(): void
    {
        // Marketing registers null adapters as safe fallbacks. Rebind here,
        // after all register() methods have run, so CRM becomes the real
        // AudienceProvider / LeadProvider whenever both modules are installed.
        $this->bindMarketing();

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'dth-crm');

        $this->publishes([
            __DIR__.'/../config/crm.php' => config_path('dth-crm.php'),
        ], 'dth-crm-config');

        $gate = app(GateContract::class);
        foreach ([
            Contact::class,
            Company::class,
            Lead::class,
            ContactQualification::class,
            Customer::class,
            CrmAgentProfile::class,
            CustomerDistributionBatch::class,
            CompanyMatchCandidate::class,
        ] as $model) {
            if ($gate->getPolicyFor($model) === null) {
                $gate->policy($model, CrmResourcePolicy::class);
            }
        }

        if ($this->app->runningInConsole()) {
            $this->commands([CrmHealthCommand::class]);
        }
    }

    private function bindMarketing(): void
    {
        if (! config('dth-crm.enabled', true) || ! config('dth-marketing.enabled', true)) {
            return;
        }

        $this->bindMarketingIntegration(
            contract: 'Dth\\Marketing\\Contracts\\AudienceProvider',
            fallback: 'Dth\\Marketing\\Integrations\\Crm\\NullAudienceProvider',
            implementation: \Dth\Crm\Integrations\Marketing\DthMarketingAudienceProvider::class,
        );

        $this->bindMarketingIntegration(
            contract: 'Dth\\Marketing\\Contracts\\LeadProvider',
            fallback: 'Dth\\Marketing\\Integrations\\Crm\\NullLeadProvider',
            implementation: \Dth\Crm\Integrations\Marketing\DthMarketingLeadProvider::class,
        );
    }

    private function bindMarketingIntegration(string $contract, string $fallback, string $implementation): void
    {
        if (! interface_exists($contract) || ! $this->canReplaceIntegration($contract, $fallback)) {
            return;
        }

        $this->app->singleton($contract, $implementation);
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
