<?php

namespace App\Services\Security;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\UserRole;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

class RbacSyncService
{
    public function tablesReady(): bool
    {
        return Schema::hasTable('permissions')
            && Schema::hasTable('roles')
            && Schema::hasTable('model_has_roles')
            && Schema::hasTable('role_has_permissions');
    }

    public function ready(): bool
    {
        return $this->tablesReady() && Permission::query()->exists();
    }

    public function syncDefinitions(): void
    {
        if (! $this->tablesReady()) return;

        foreach (RbacDefinition::permissions() as $name => $meta) {
            Permission::query()->updateOrCreate(['name' => $name, 'guard_name' => 'web'], $meta);
        }

        foreach (RbacDefinition::roles() as $name => $meta) {
            $role = Role::query()->updateOrCreate(['name' => $name, 'guard_name' => 'web'], [
                'label' => $meta['label'], 'description' => $meta['description'], 'is_system' => $meta['system'],
            ]);
            $role->syncPermissions($meta['permissions']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function syncAllUsers(): void
    {
        if (! $this->ready()) return;
        User::query()->with('staff.businessFunctions')->chunkById(100, fn ($users) => $users->each(fn (User $u) => $this->syncUserCanonicalRoles($u)));
    }

    public function syncUserCanonicalRoles(User $user): void
    {
        if (! $this->ready()) return;

        $canonical = array_keys(RbacDefinition::roles());
        $custom = $user->roles->pluck('name')->reject(fn (string $name) => in_array($name, $canonical, true))->all();
        $roles = $custom;

        $roles[] = match (UserRole::tryFrom((string) $user->role)) {
            UserRole::SuperAdmin => 'super_admin', UserRole::Admin => 'system_admin',
            UserRole::Executive => 'executive', UserRole::Viewer => 'viewer', default => null,
        };

        if ($user->hasBusinessFunction(DepartmentFunction::Marketing)) $roles[] = $user->hasBusinessManagerAuthority(DepartmentFunction::Marketing) ? 'marketing_manager' : 'marketing_staff';
        if ($user->hasBusinessFunction(DepartmentFunction::Sales)) $roles[] = $user->hasBusinessManagerAuthority(DepartmentFunction::Sales) ? 'sales_manager' : 'sales_staff';
        if ($user->hasBusinessFunction(DepartmentFunction::CustomerService)) $roles[] = $user->hasBusinessManagerAuthority(DepartmentFunction::CustomerService) ? 'customer_care_manager' : 'customer_care_staff';
        if ($user->hasBusinessFunction(DepartmentFunction::Finance)) $roles[] = 'finance';

        $user->syncRoles(array_values(array_unique(array_filter($roles))));
    }
}
