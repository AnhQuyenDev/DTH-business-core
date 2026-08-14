<?php

namespace Tests\Feature\Configuration;

use App\Filament\Resources\Security\RbacRoleResource;
use App\Models\Security\Role;
use App\Services\Security\RbacSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesV1Actors;
use Tests\TestCase;

class RbacRoleFormModuleTest extends TestCase
{
    use MakesV1Actors;
    use RefreshDatabase;

    public function test_saving_one_module_keeps_permissions_of_other_modules(): void
    {
        app(RbacSyncService::class)->syncDefinitions();

        $role = Role::query()->create([
            'name' => 'custom_module_tester',
            'guard_name' => 'web',
            'label' => 'Module Tester',
            'is_system' => false,
        ]);

        $role->syncPermissions([
            'marketing.view-campaigns',
            'marketing.manage-contacts',
            'crm.view-leads',
            'system.manage-users',
        ]);

        RbacRoleResource::syncRolePermissions($role, 'CRM', ['crm.view-leads', 'crm.process-lead']);

        $this->assertSame(
            [
                'crm.process-lead',
                'crm.view-leads',
                'marketing.manage-contacts',
                'marketing.view-campaigns',
                'system.manage-users',
            ],
            $role->permissions()->orderBy('name')->pluck('name')->all(),
        );
    }

    public function test_clearing_module_keeps_everything_assigned(): void
    {
        app(RbacSyncService::class)->syncDefinitions();

        $role = Role::query()->create([
            'name' => 'custom_module_clear_tester',
            'guard_name' => 'web',
            'label' => 'Module Clear Tester',
            'is_system' => false,
        ]);

        $role->syncPermissions(['marketing.view-campaigns', 'crm.view-leads']);

        RbacRoleResource::syncRolePermissions($role, null, []);

        $this->assertSame(
            ['crm.view-leads', 'marketing.view-campaigns'],
            $role->permissions()->orderBy('name')->pluck('name')->all(),
        );
    }

    public function test_create_and_edit_role_pages_render_for_super_admin(): void
    {
        $this->actingAs($this->makeV1SuperAdmin());

        $this->get('/admin/security/rbac-roles/create')->assertOk();

        $role = Role::query()->firstOrFail();

        $this->get("/admin/security/rbac-roles/{$role->id}/edit")->assertOk();
    }
}
