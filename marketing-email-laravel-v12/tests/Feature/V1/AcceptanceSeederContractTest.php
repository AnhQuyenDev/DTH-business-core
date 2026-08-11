<?php

namespace Tests\Feature\V1;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\V1AcceptanceTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcceptanceSeederContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_v1_seeder_creates_the_six_official_acceptance_accounts(): void
    {
        $this->seed(V1AcceptanceTestSeeder::class);

        foreach ([
            'superadmin.v1@dth.local',
            'admin.v1@dth.local',
            'founder.v1@dth.local',
            'commercial.v1@dth.local',
            'support.v1@dth.local',
            'finance.v1@dth.local',
        ] as $email) {
            $this->assertDatabaseHas('users', ['email' => $email, 'is_active' => true]);
        }
    }

    public function test_operational_accounts_use_generic_user_role_and_business_functions(): void
    {
        $this->seed(V1AcceptanceTestSeeder::class);

        $commercial = User::query()->where('email', 'commercial.v1@dth.local')->firstOrFail();
        $support = User::query()->where('email', 'support.v1@dth.local')->firstOrFail();
        $finance = User::query()->where('email', 'finance.v1@dth.local')->firstOrFail();

        $this->assertSame(UserRole::User->value, $commercial->role);
        $this->assertTrue($commercial->hasBusinessFunction(DepartmentFunction::Marketing));
        $this->assertTrue($commercial->hasBusinessFunction(DepartmentFunction::Sales));

        $this->assertSame(UserRole::User->value, $support->role);
        $this->assertTrue($support->hasBusinessFunction(DepartmentFunction::CustomerService));
        $this->assertFalse($support->hasBusinessFunction(DepartmentFunction::Sales));

        $this->assertSame(UserRole::User->value, $finance->role);
        $this->assertTrue($finance->hasBusinessFunction(DepartmentFunction::Finance));
        $this->assertFalse($finance->hasBusinessFunction(DepartmentFunction::Marketing));
    }

    public function test_super_admin_and_system_admin_remain_separate_system_roles(): void
    {
        $this->seed(V1AcceptanceTestSeeder::class);

        $superAdmin = User::query()->where('email', 'superadmin.v1@dth.local')->firstOrFail();
        $admin = User::query()->where('email', 'admin.v1@dth.local')->firstOrFail();

        $this->assertSame(UserRole::SuperAdmin->value, $superAdmin->role);
        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertSame(UserRole::Admin->value, $admin->role);
        $this->assertFalse($admin->isSuperAdmin());
        $this->assertTrue($admin->isAdmin());
    }
}
