<?php

namespace App\Providers;

use App\Support\Localization\LocaleManager;
use App\Support\Localization\UiTranslator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LocaleManager::class);
        $this->app->singleton(UiTranslator::class);
        $this->app->alias(UiTranslator::class, 'ui.translator');
    }

    public function boot(): void
    {
        // Root-level platform services are bootstrapped through their own
        // middleware/config. Modules only consume the stable ui_t() contract.
    }
}
