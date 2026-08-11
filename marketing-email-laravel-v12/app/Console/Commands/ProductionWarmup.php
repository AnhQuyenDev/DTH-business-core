<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProductionWarmup extends Command
{
    protected $signature = 'system:production-warmup';
    protected $description = 'Warm Laravel + Filament caches for production deployment.';

    public function handle(): int
    {
        $this->call('optimize');
        if ($this->getApplication()?->has('filament:optimize')) $this->call('filament:optimize');
        $this->call('permission:cache-reset');
        $this->info('Production caches warmed.');
        return self::SUCCESS;
    }
}
