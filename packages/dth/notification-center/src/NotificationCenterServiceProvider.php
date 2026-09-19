<?php

namespace Dth\NotificationCenter;

use Dth\NotificationCenter\Events\NotificationRequested;
use Dth\NotificationCenter\Livewire\NotificationBell;
use Dth\NotificationCenter\Services\NotificationAuthorization;
use Dth\NotificationCenter\Services\NotificationManager;
use Dth\NotificationCenter\Services\NotificationPreferenceService;
use Dth\NotificationCenter\Services\RecipientDirectory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

final class NotificationCenterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/notification-center.php', 'dth-notification-center');
        $this->mergeConfigFrom(__DIR__.'/../config/translations.php', 'dth-notification-center-translations');

        foreach ([
            NotificationAuthorization::class,
            NotificationPreferenceService::class,
            RecipientDirectory::class,
            NotificationManager::class,
        ] as $service) {
            $this->app->singleton($service);
        }
    }

    public function boot(): void
    {
        if (! config('dth-notification-center.enabled', true)) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'dth-notification-center');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        $this->publishes([
            __DIR__.'/../config/notification-center.php' => config_path('dth-notification-center.php'),
        ], 'dth-notification-center-config');

        if (class_exists(Livewire::class)) {
            Livewire::component('dth-notification-bell', NotificationBell::class);
        }

        Event::listen(NotificationRequested::class, function (NotificationRequested $event): void {
            if (! Schema::hasTable('dth_notifications')) {
                return;
            }

            $manager = app(NotificationManager::class);

            if ($event->permission) {
                $manager->sendToPermission($event->permission, $event->message, $event->channels);
                return;
            }

            if ($event->targets !== []) {
                $manager->sendToTargets($event->message, $event->targets, $event->channels, $event->allowBroadcast);
                return;
            }

            $manager->send($event->message, $event->recipientUserIds, $event->channels);
        });
    }
}
