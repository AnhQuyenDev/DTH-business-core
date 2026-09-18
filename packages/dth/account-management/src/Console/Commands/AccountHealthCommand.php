<?php
namespace Dth\AccountManagement\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class AccountHealthCommand extends Command
{
    protected $signature = 'dth:accounts:health';
    protected $description = 'Check DTH Account Management module health.';
    public function handle(): int
    {
        $tables = ['account_roles','account_permissions','account_role_user','account_groups','account_invitations','account_audit_logs','account_settings'];
        $missing = array_values(array_filter($tables, fn (string $table): bool => ! Schema::hasTable($table)));
        if ($missing) { $this->error('Missing tables: '.implode(', ', $missing)); return self::FAILURE; }
        $this->info('DTH Account Management is healthy.');
        return self::SUCCESS;
    }
}
