<?php
namespace Dth\AccountManagement\Console\Commands;

use Dth\AccountManagement\Models\AccountRole;
use Dth\AccountManagement\Services\PermissionRegistryService;
use Illuminate\Console\Command;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'dth:accounts:sync-permissions';
    protected $description = 'Synchronize DTH account permission definitions into the database.';

    public function handle(PermissionRegistryService $registry): int
    {
        $count = $registry->sync();
        foreach (AccountRole::query()->whereIn('key', ['super-admin','administrator'])->get() as $role) {
            $role->permissions()->syncWithoutDetaching(\Dth\AccountManagement\Models\AccountPermission::query()->pluck('id')->all());
        }
        $this->info("Synchronized {$count} permissions.");
        return self::SUCCESS;
    }
}
