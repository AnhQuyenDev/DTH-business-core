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

        $this->bindMarketing();

        if ($this->app->runningInConsole()) {
            $this->commands([CrmHealthCommand::class]);
        }
    }

    private function bindMarketing(): void
    {
        $marketingProvider = \Dth\Crm\Contracts\MarketingLeadProvider::class;
        if (! interface_exists($marketingProvider)) {
            return;
        }

        $implementation = \Dth\Crm\Integrations\Marketing\DthMarketingLeadProvider::class;
        $this->app->singleton($marketingProvider, $implementation);
    }
}
