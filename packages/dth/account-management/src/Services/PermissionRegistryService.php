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
        $definitions = (array) config('dth-account-management.permissions', []);

        // Optional module permissions remain in Account Management's stable registry,
        // but disappear from roles/gates when that module is detached or disabled.
        if (! (bool) config('dth-notification-center.enabled', false)) {
            $definitions = array_filter(
                $definitions,
                fn (array $definition, string $key): bool => ! str_starts_with($key, 'notifications.'),
                ARRAY_FILTER_USE_BOTH,
            );
        }

        return $definitions;
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
        $keys = array_keys($this->definitions());
        if ($keys === []) return [];

        return AccountPermission::query()->whereIn('key', $keys)->orderBy('module')->orderBy('name')->get()
            ->groupBy('module')
            ->map(fn ($items) => $items->mapWithKeys(fn (AccountPermission $item) => [$item->id => UiText::permission($item->key, $item->name)])->all())
            ->all();
    }

    /** @return array<string, string> */
    public function moduleOptions(): array
    {
        return collect($this->definitions())
            ->map(fn (array $definition): string => (string) ($definition['module'] ?? 'core'))
            ->unique()
            ->sort()
            ->mapWithKeys(fn (string $module): array => [$module => UiText::get('modules.'.$module, str($module)->replace('-', ' ')->headline()->toString())])
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
