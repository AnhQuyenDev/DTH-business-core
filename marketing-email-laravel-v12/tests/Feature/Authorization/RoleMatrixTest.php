<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_business_role_can_access_panel(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = User::factory()->create([
                'role' => $role->value,
            ]);

            $this->assertTrue(
                $user->canAccessBusinessPanel(),
                "Role {$role->value} cannot access panel"
            );
        }
    }

    public function test_sales_and_finance_roles_are_detected(): void
    {
        $salesManager = User::factory()->create([
            'role' => UserRole::SalesManager->value,
        ]);
        $salesStaff = User::factory()->create([
            'role' => UserRole::SalesStaff->value,
        ]);
        $finance = User::factory()->create([
            'role' => UserRole::FinanceStaff->value,
        ]);

        $this->assertTrue($salesManager->isSalesManager());
        $this->assertTrue($salesStaff->isSalesStaff());
        $this->assertTrue($finance->isFinanceStaff());

        $this->assertFalse($salesStaff->isCustomerServiceStaff());
        $this->assertFalse($finance->isSalesStaff());
    }
}
