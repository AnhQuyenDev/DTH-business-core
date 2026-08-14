<?php

namespace Tests\Feature\Configuration;

use App\Filament\Resources\Security\RbacRoleResource;
use App\Models\Security\Role;
use App\Models\User;
use App\Services\Security\RbacSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesV1Actors;
use Tests\TestCase;

class RbacRoleDeleteTest extends TestCase
{
    use MakesV1Actors;
    use RefreshDatabase;

    public function test_super_admin_can_delete_system_role_but_not_super_admin_role(): void
    {
        $this->actingAs($this->makeV1SuperAdmin());

        $systemRole = Role::query()->where('name', 'executive')->firstOrFail();
        $superAdminRole = Role::query()->where('name', 'super_admin')->firstOrFail();

        $this->assertTrue(RbacRoleResource::canDelete($systemRole));
        $this->assertFalse(RbacRoleResource::canDelete($superAdminRole));

        $this->assertTrue((bool) $systemRole->delete());
        $this->assertDatabaseMissing('roles', ['id' => $systemRole->id]);
    }

    public function test_system_admin_cannot_delete_system_roles_but_can_delete_custom_roles(): void
    {
        app(RbacSyncService::class)->syncDefinitions();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $systemRole = Role::query()->where('name', 'executive')->firstOrFail();

        $custom = Role::query()->create([
            'name' => 'custom_tester',
            'guard_name' => 'web',
            'label' => 'Custom Tester',
            'is_system' => false,
        ]);

        $this->assertFalse(RbacRoleResource::canDelete($systemRole));
        $this->assertTrue(RbacRoleResource::canDelete($custom));
    }
}
