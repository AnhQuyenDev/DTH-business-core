<?php

namespace Dth\HumanResource;

use Dth\HumanResource\Console\Commands\HumanResourceHealthCommand;
use Dth\HumanResource\Models\Department;
use Dth\HumanResource\Models\Employee;
use Dth\HumanResource\Models\EmployeeAvailability;
use Dth\HumanResource\Models\EmployeeBusinessFunction;
use Dth\HumanResource\Models\Position;
use Dth\HumanResource\Policies\HumanResourcePolicy;
use Dth\HumanResource\Services\HumanResourceAnalyticsService;
use Dth\HumanResource\Services\HumanResourceDataExchangeService;
use Dth\HumanResource\Services\EmployeeAccountService;
use Dth\HumanResource\Services\WorkforceService;
use Dth\HumanResource\Support\CodeGenerator;
use Dth\HumanResource\Support\HumanResourceAuthorization;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\ServiceProvider;

final class HumanResourceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/human-resource.php', 'dth-human-resource');

        foreach ([
            CodeGenerator::class,
            HumanResourceAuthorization::class,
            HumanResourceAnalyticsService::class,
            HumanResourceDataExchangeService::class,
            EmployeeAccountService::class,
            WorkforceService::class,
        ] as $service) {
            $this->app->singleton($service);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'dth-human-resource');

        $this->publishes([
            __DIR__.'/../config/human-resource.php' => config_path('dth-human-resource.php'),
        ], 'dth-human-resource-config');

        $gate = app(GateContract::class);
        foreach ([Department::class, Position::class, Employee::class, EmployeeAvailability::class, EmployeeBusinessFunction::class] as $model) {
            if ($gate->getPolicyFor($model) === null) {
                $gate->policy($model, HumanResourcePolicy::class);
            }
        }

        if ($this->app->runningInConsole()) {
            $this->commands([HumanResourceHealthCommand::class]);
        }
    }
}
