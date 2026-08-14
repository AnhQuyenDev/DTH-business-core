<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;

class ProductionWarmup extends Command
{
    protected $signature = 'system:production-warmup';
    protected $description = 'Warm Laravel + Filament caches for production deployment.';

    public function handle(): int
    {
        // Reset stale RBAC data before warming caches. Resetting at the end
        // makes the first real login rebuild the complete permission graph.
        $this->call('permission:cache-reset');
        $this->call('optimize');
        if ($this->getApplication()?->has('filament:optimize')) {
            $this->call('filament:optimize');
        }

        app(PermissionRegistrar::class)->registerPermissions();
        $this->info('Production caches warmed.');

        return self::SUCCESS;
    }
}
