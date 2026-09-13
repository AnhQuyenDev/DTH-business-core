<?php

namespace Dth\Marketing\Console\Commands;

use Dth\Marketing\Support\IntegrationHealthService;
use Illuminate\Console\Command;

final class MarketingHealthCommand extends Command
{
    protected $signature = 'marketing:health {--json : Output JSON}';
    protected $description = 'Show Marketing integration capability health without treating optional modules as failures.';

    public function handle(IntegrationHealthService $health): int
    {
        $snapshot = $health->snapshot();

        if ($this->option('json')) {
            $this->line(json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');

            return self::SUCCESS;
        }

        $rows = [];
        foreach ($snapshot as $name => $status) {
            $rows[] = [
                ucfirst($name),
                ($status['healthy'] ?? true)
                    ? ($status['available'] ? 'Available' : 'N/A')
                    : 'ERROR',
                ($status['healthy'] ?? true)
                    ? (implode(', ', array_keys(array_filter($status['capabilities']))) ?: '—')
                    : ($status['error'] ?? 'Provider error'),
            ];
        }

        $this->table(['Integration', 'Status', 'Capabilities'], $rows);
        $this->info('N/A is valid for optional CRM/Sales/Finance/Email capabilities; Marketing must continue to boot safely.');

        return self::SUCCESS;
    }
}
