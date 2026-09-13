<?php

namespace Dth\Marketing\Console\Commands;

use Dth\Marketing\Models\MarketingAuditLog;
use Illuminate\Console\Command;

final class MarketingAuditPruneCommand extends Command
{
    protected $signature = 'marketing:audit-prune {--days= : Override retention days}';
    protected $description = 'Prune Marketing audit rows older than the configured retention window.';

    public function handle(): int
    {
        $days = $this->option('days');
        $days = $days !== null && $days !== ''
            ? max(1, (int) $days)
            : max(1, (int) config('dth-marketing.audit.retention_days', 365));

        $deleted = MarketingAuditLog::query()
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Pruned {$deleted} Marketing audit rows older than {$days} days.");

        return self::SUCCESS;
    }
}
