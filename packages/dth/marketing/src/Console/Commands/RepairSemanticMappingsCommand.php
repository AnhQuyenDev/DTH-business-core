<?php

namespace Dth\Marketing\Console\Commands;

use Dth\Marketing\Services\SemanticMappingRepairService;
use Illuminate\Console\Command;

final class RepairSemanticMappingsCommand extends Command
{
    protected $signature = 'marketing:repair-semantic-mappings';

    protected $description = 'Re-detect semantic form-field roles and backfill normalized Marketing submissions.';

    public function handle(SemanticMappingRepairService $repair): int
    {
        $result = $repair->repairAll();

        $this->info(sprintf(
            'Semantic mapping repair completed. Fields: %d; submissions: %d.',
            $result['fields'],
            $result['submissions'],
        ));

        return self::SUCCESS;
    }
}
