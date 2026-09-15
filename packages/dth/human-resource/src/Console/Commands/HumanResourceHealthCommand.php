<?php

namespace Dth\HumanResource\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

final class HumanResourceHealthCommand extends Command
{
    protected $signature = 'human-resource:health {--json : Output JSON}';
    protected $description = 'Validate DTH Human Resource runtime readiness.';

    public function handle(): int
    {
        $tables = [
            'hr_departments',
            'hr_positions',
            'hr_employees',
            'hr_employee_availabilities',
            'hr_employee_business_functions',
        ];

        $results = collect($tables)->mapWithKeys(fn (string $table): array => [
            $table => Schema::hasTable($table),
        ])->all();
        $healthy = ! in_array(false, $results, true);

        if ($this->option('json')) {
            $this->line(json_encode([
                'healthy' => $healthy,
                'tables' => $results,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');
        } else {
            foreach ($results as $table => $ok) {
                $this->line(($ok ? 'PASS ' : 'FAIL ').$table);
            }
        }

        return $healthy ? self::SUCCESS : self::FAILURE;
    }
}
