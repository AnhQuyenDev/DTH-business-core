<?php

namespace Dth\AccountManagement\Services;

use Dth\AccountManagement\Enums\DataScope;
use Dth\AccountManagement\Models\AccountPermission;
use Dth\AccountManagement\Models\AccountUser;
use Dth\AccountManagement\Support\UiText;
use Illuminate\Support\Collection;

final class EffectivePermissionService
{
    public function __construct(
        private readonly AccessControlService $access,
        private readonly PermissionRegistryService $registry,
    ) {}

    /**
     * Build the permissions that are actually effective for a user after role,
     * direct allow/deny and permission dependencies have been evaluated.
     *
     * @return array<string, mixed>
     */
    public function inspect(AccountUser $user): array
    {
        $user->loadMissing(['roles.permissions', 'directPermissions']);

        $definitions = $this->registry->definitions();
        $permissions = AccountPermission::query()
            ->whereIn('key', array_keys($definitions))
            ->get()
            ->keyBy('key');

        $activeRoles = $user->roles->where('is_active', true)->values();
        $direct = $user->directPermissions->keyBy('key');
        $isAdministrator = $this->access->isSuperAdministrator($user)
            || $this->access->isBootstrapAdministrator($user);

        $grouped = collect($definitions)
            ->map(function (array $definition, string $key) use ($user, $permissions, $activeRoles, $direct, $isAdministrator): ?array {
                $permission = $permissions->get($key);
                if (! $permission || ! $this->access->allows($user, $key)) {
                    return null;
                }

                $directPermission = $direct->get($key);
                $directEffect = $directPermission?->pivot?->effect;

                if ($isAdministrator) {
                    $sources = [UiText::get('permission_inspector.source_system', 'Toàn quyền hệ thống')];
                } elseif ($directEffect === 'allow') {
                    $sources = [UiText::get('permission_inspector.source_direct', 'Quyền cấp trực tiếp')];
                } else {
                    $roleNames = $activeRoles
                        ->filter(fn ($role): bool => $role->permissions->contains('key', $key))
                        ->pluck('name')
                        ->values()
                        ->all();

                    if ($roleNames !== []) {
                        $template = UiText::get('permission_inspector.source_role', 'Vai trò: :role');
                        $sources = array_map(
                            fn (string $name): string => str_replace(':role', $name, $template),
                            $roleNames,
                        );
                    } else {
                        $sources = [UiText::get('permission_inspector.source_inherited', 'Kế thừa quyền truy cập phân hệ')];
                    }
                }

                $fallbackName = (string) ($definition['name'] ?? $permission->name ?? $key);
                $fallbackDescription = (string) ($definition['description'] ?? $permission->description ?? '');

                return [
                    'key' => $key,
                    'module' => (string) ($definition['module'] ?? $permission->module),
                    'name' => UiText::permission($key, $fallbackName),
                    'description' => UiText::permissionDescription($key, $fallbackDescription),
                    'sources' => $sources,
                ];
            })
            ->filter()
            ->groupBy('module')
            ->map(function (Collection $items, string $module): array {
                return [
                    'module' => $module,
                    'label' => UiText::get('modules.'.$module, $module),
                    'permissions' => $items->values()->all(),
                ];
            });

        $scope = $this->access->dataScope($user);

        return [
            'roles' => $activeRoles->pluck('name')->values()->all(),
            'data_scope' => DataScope::options()[$scope->value] ?? $scope->value,
            'total' => $grouped->sum(fn (array $group): int => count($group['permissions'])),
            'groups' => $grouped->values()->all(),
        ];
    }
}
