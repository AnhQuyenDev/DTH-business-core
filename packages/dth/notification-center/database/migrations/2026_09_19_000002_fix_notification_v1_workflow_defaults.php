<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dth_notification_templates')) {
            DB::table('dth_notification_templates')
                ->whereIn('code', ['hr.account_request_submitted', 'hr.account_request_resolved'])
                ->update([
                    'is_mandatory' => true,
                    'updated_at' => now(),
                ]);
        }

        if (! Schema::hasTable('account_roles')
            || ! Schema::hasTable('account_permissions')
            || ! Schema::hasTable('account_permission_role')) {
            return;
        }

        // Keep this migration self-contained: on installations where the
        // Account permission sync command has not been run yet, ensure the two
        // permissions required by the HR Manager defaults exist before the
        // role assignment below.
        $definitions = (array) config('dth-account-management.permissions', []);
        $now = now();
        foreach (['notifications.send', 'notifications.send.departments'] as $key) {
            $definition = (array) ($definitions[$key] ?? []);
            DB::table('account_permissions')->updateOrInsert(
                ['key' => $key],
                [
                    'module' => (string) ($definition['module'] ?? 'notifications'),
                    'name' => (string) ($definition['name'] ?? $key),
                    'description' => $definition['description'] ?? null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }

        $roleIds = DB::table('account_roles')
            ->where('is_active', true)
            ->get(['id', 'key', 'name'])
            ->filter(function ($role): bool {
                $key = strtolower(trim((string) $role->key));
                $name = strtolower(trim((string) $role->name));

                return in_array($key, ['hr_manager', 'hr-manager', 'human_resource_manager', 'human-resource-manager'], true)
                    || in_array($name, ['hr manager', 'human resource manager', 'trưởng phòng nhân sự'], true);
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        if ($roleIds->isEmpty()) {
            return;
        }

        $permissionIds = DB::table('account_permissions')
            ->whereIn('key', ['notifications.send', 'notifications.send.departments'])
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values();

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('account_permission_role')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('dth_notification_templates')) {
            DB::table('dth_notification_templates')
                ->whereIn('code', ['hr.account_request_submitted', 'hr.account_request_resolved'])
                ->update([
                    'is_mandatory' => false,
                    'updated_at' => now(),
                ]);
        }

        if (! Schema::hasTable('account_roles')
            || ! Schema::hasTable('account_permissions')
            || ! Schema::hasTable('account_permission_role')) {
            return;
        }

        $roleIds = DB::table('account_roles')
            ->get(['id', 'key', 'name'])
            ->filter(function ($role): bool {
                $key = strtolower(trim((string) $role->key));
                $name = strtolower(trim((string) $role->name));

                return in_array($key, ['hr_manager', 'hr-manager', 'human_resource_manager', 'human-resource-manager'], true)
                    || in_array($name, ['hr manager', 'human resource manager', 'trưởng phòng nhân sự'], true);
            })
            ->pluck('id');

        $permissionIds = DB::table('account_permissions')
            ->whereIn('key', ['notifications.send', 'notifications.send.departments'])
            ->pluck('id');

        if ($roleIds->isNotEmpty() && $permissionIds->isNotEmpty()) {
            DB::table('account_permission_role')
                ->whereIn('role_id', $roleIds)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }
    }
};
