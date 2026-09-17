<?php

namespace Dth\Commercial\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

final class CommercialHealthCommand extends Command
{
    protected $signature = 'commercial:health';
    protected $description = 'Check DTH Commercial module availability and optional integrations.';

    public function handle(): int
    {
        $rows = [
            ['Module enabled', config('dth-commercial.enabled', true) ? 'yes' : 'no'],
            ['Catalog tables', Schema::hasTable('commercial_services') && Schema::hasTable('commercial_service_packages') ? 'ready' : 'missing'],
            ['Opportunity table', Schema::hasTable('commercial_opportunities') ? 'ready' : 'missing'],
            ['Marketing adapter', interface_exists('Dth\\Marketing\\Contracts\\CatalogProvider') ? 'available' : 'not installed'],
            ['CRM handoff adapter', interface_exists('Dth\\Crm\\Contracts\\SalesHandoffProvider') ? 'available' : 'not installed'],
        ];

        $this->table(['Check', 'Status'], $rows);

        return self::SUCCESS;
    }
}
