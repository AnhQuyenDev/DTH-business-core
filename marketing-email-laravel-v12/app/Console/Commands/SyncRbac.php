<?php

namespace App\Console\Commands;

use App\Services\Security\RbacSyncService;
use Illuminate\Console\Command;

class SyncRbac extends Command
{
    protected $signature = 'rbac:sync {--users : Đồng bộ role chuẩn cho toàn bộ user từ system role + business functions}';
    protected $description = 'Đồng bộ permission catalog, role chuẩn và tùy chọn đồng bộ user.';

    public function handle(RbacSyncService $service): int
    {
        $service->syncDefinitions();
        if ($this->option('users')) $service->syncAllUsers();
        $this->info('RBAC đã được đồng bộ.');
        return self::SUCCESS;
    }
}
