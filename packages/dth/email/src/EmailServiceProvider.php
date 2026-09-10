<?php

namespace Dth\Email;

use Dth\Email\Console\Commands\ProcessScheduledCampaignsCommand;
use Dth\Email\Contracts\DnsResolver;
use Dth\Email\Contracts\EmailTransport;
use Dth\Email\Services\NativeDnsResolver;
use Dth\Email\Services\SmtpEmailTransport;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/email.php', 'dth-email');

        $this->app->bind(EmailTransport::class, SmtpEmailTransport::class);
        $this->app->singleton(DnsResolver::class, NativeDnsResolver::class);
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
