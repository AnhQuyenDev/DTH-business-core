<?php

namespace Dth\Email;

use Dth\Email\Console\Commands\CheckEmailAnalyticsCommand;
use Dth\Email\Console\Commands\ProcessScheduledCampaignsCommand;
use Dth\Email\Contracts\DnsResolver;
use Dth\Email\Contracts\EmailTransport;
use Dth\Email\Services\CampaignAnalyticsService;
use Dth\Email\Services\EmailAnalyticsService;
use Dth\Email\Services\EmailDashboardFilterResolver;
use Dth\Email\Services\NativeDnsResolver;
use Dth\Email\Services\SmtpEmailTransport;
use Dth\Email\Services\TransportCapabilityService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/email.php', 'dth-email');

        $this->app->bind(EmailTransport::class, SmtpEmailTransport::class);
        $this->app->singleton(DnsResolver::class, NativeDnsResolver::class);

        // Shared reporting services. Keeping the data layer centralized ensures
        // Dashboard, Campaign Report, Export and Insight phases all consume the
        // same metric definitions instead of re-implementing SQL per widget.
        $this->app->singleton(TransportCapabilityService::class);
        $this->app->singleton(CampaignAnalyticsService::class);
        $this->app->singleton(EmailAnalyticsService::class);
        $this->app->singleton(EmailDashboardFilterResolver::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'dth-email');

        $this->publishes([
            __DIR__.'/../config/email.php' => config_path('dth-email.php'),
        ], 'dth-email-config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ProcessScheduledCampaignsCommand::class,
                CheckEmailAnalyticsCommand::class,
            ]);

            $this->app->booted(function (): void {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('email:campaigns:process')
                    ->everyMinute()
                    ->withoutOverlapping();
            });
        }
    }
}
