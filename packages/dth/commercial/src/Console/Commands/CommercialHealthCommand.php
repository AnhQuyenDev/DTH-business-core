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
            ['Service catalog', Schema::hasTable('commercial_services') ? 'ready' : 'missing'],
            ['Product catalog', Schema::hasTable('commercial_products') && Schema::hasTable('commercial_product_prices') ? 'ready' : 'missing'],
            ['Bundle catalog', Schema::hasTable('commercial_bundles') && Schema::hasTable('commercial_bundle_items') ? 'ready' : 'missing'],
            ['Opportunity tables', Schema::hasTable('commercial_opportunities') && Schema::hasTable('commercial_opportunity_items') ? 'ready' : 'missing'],
            ['Legacy package table', Schema::hasTable('commercial_service_packages') ? 'available' : 'not present'],
            ['Marketing adapter', interface_exists('Dth\\Marketing\\Contracts\\CatalogProvider') ? 'available' : 'not installed'],
            ['CRM handoff adapter', interface_exists('Dth\\Crm\\Contracts\\SalesHandoffProvider') ? 'available' : 'not installed'],
        ];

        $this->table(['Check', 'Status'], $rows);

        return self::SUCCESS;
    }
}
