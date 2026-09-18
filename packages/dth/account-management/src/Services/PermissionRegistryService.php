<?php
namespace Dth\AccountManagement\Services;

use Dth\AccountManagement\Models\AccountPermission;
use Dth\AccountManagement\Support\UiText;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Support\Facades\Schema;

final class PermissionRegistryService
{
    public function definitions(): array
    {
        return (array) config('dth-account-management.permissions', []);
    }

    public function dependencies(): array
    {
        return (array) config('dth-account-management.permission_dependencies', []);
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
            ->map(fn ($items) => $items->mapWithKeys(fn (AccountPermission $item) => [$item->id => UiText::permission($item->key, $item->name)])->all())
            ->all();
    }

    /** @param array<int, string> $keys @return array<int, string> */
    public function expandPermissionKeys(array $keys): array
    {
        $dependencies = $this->dependencies();
        $expanded = array_values(array_unique(array_filter($keys)));
        $queue = $expanded;

        while ($queue !== []) {
            $key = array_shift($queue);
            foreach ((array) ($dependencies[$key] ?? []) as $required) {
                $required = (string) $required;
                if ($required === '' || in_array($required, $expanded, true)) continue;
                $expanded[] = $required;
                $queue[] = $required;
            }
        }

        return $expanded;
    }

    /** @param array<int, int> $ids @return array<int, int> */
    public function expandPermissionIds(array $ids): array
    {
        if ($ids === [] || ! Schema::hasTable('account_permissions')) return $ids;
        $keys = AccountPermission::query()->whereIn('id', $ids)->pluck('key')->all();
        $keys = $this->expandPermissionKeys($keys);

        return AccountPermission::query()->whereIn('key', $keys)->pluck('id')->map(fn ($id) => (int) $id)->unique()->values()->all();
    }

    /** @return array<int, string> */
    public function permissionsImplying(string $permission): array
    {
        $result = [];
        foreach (array_keys($this->definitions()) as $candidate) {
            if ($candidate === $permission) continue;
            if (in_array($permission, $this->expandPermissionKeys([$candidate]), true)) {
                $result[] = $candidate;
            }
        }
        return $result;
    }

    public function registerGates(GateContract $gate, AccessControlService $access): void
    {
        foreach (array_keys($this->definitions()) as $permission) {
            if (method_exists($gate, 'has') && $gate->has($permission)) continue;
            $gate->define($permission, fn ($user): bool => $access->allows($user, $permission));
        }
    }
}
