<?php
namespace Dth\AccountManagement\Services;

use Dth\AccountManagement\Models\AccountPermission;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PermissionRegistryService
{
    public function definitions(): array
    {
        return (array) config('dth-account-management.permissions', []);
    }

    public function sync(): int
    {
        if (! Schema::hasTable('account_permissions')) return 0;
        $count = 0;
        foreach ($this->definitions() as $key => $definition) {
            AccountPermission::query()->updateOrCreate(
                ['key' => $key],
                [
                    'module' => (string) ($definition['module'] ?? str($key)->before('.')),
                    'name' => (string) ($definition['name'] ?? $key),
                    'description' => $definition['description'] ?? null,
                ],
            );
            $count++;
        }
        return $count;
    }

    public function groupedOptions(): array
    {
        if (! Schema::hasTable('account_permissions')) return [];
        return AccountPermission::query()->orderBy('module')->orderBy('name')->get()
            ->groupBy('module')
            ->map(fn ($items) => $items->pluck('name', 'id')->all())
            ->all();
    }

    public function registerGates(GateContract $gate, AccessControlService $access): void
    {
        foreach (array_keys($this->definitions()) as $permission) {
            if (method_exists($gate, 'has') && $gate->has($permission)) continue;
            $gate->define($permission, fn ($user): bool => $access->allows($user, $permission));
        }
    }
}
