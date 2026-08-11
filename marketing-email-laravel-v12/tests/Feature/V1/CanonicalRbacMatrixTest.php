<?php

namespace Tests\Feature\V1;

use App\Models\User;
use App\Services\Security\RbacAuthorizationService;
use App\Services\Security\RbacDefinition;
use Database\Seeders\V1AcceptanceTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CanonicalRbacMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(V1AcceptanceTestSeeder::class);
    }

    private function user(string $email): User
    {
        return User::query()->where('email', $email)->firstOrFail();
    }

    public function test_super_admin_has_every_defined_permission(): void
    {
        $user = $this->user('superadmin.v1@dth.local');
        $authorization = app(RbacAuthorizationService::class);

        foreach (array_keys(RbacDefinition::permissions()) as $permission) {
            $this->assertTrue(
                $authorization->allows($user, $permission),
                "Super Admin must have [{$permission}]",
            );
        }
    }

    public static function permissionMatrixProvider(): array
    {
        return [
            'system admin manages system' => ['admin.v1@dth.local', 'system.manage-users', true],
            'system admin can audit' => ['admin.v1@dth.local', 'system.view-audit', true],
            'system admin cannot approve sales quotation' => ['admin.v1@dth.local', 'sales.approve-quotations', false],
            'system admin cannot verify payment' => ['admin.v1@dth.local', 'sales.verify-payments', false],

            'founder manages marketing' => ['founder.v1@dth.local', 'marketing.send-campaigns', true],
            'founder manages sales' => ['founder.v1@dth.local', 'sales.approve-quotations', true],
            'founder manages customer care' => ['founder.v1@dth.local', 'customer-care.distribute', true],
            'founder has finance capability' => ['founder.v1@dth.local', 'sales.verify-payments', true],

            'commercial operates marketing' => ['commercial.v1@dth.local', 'marketing.manage-campaigns', true],
            'commercial creates quotations' => ['commercial.v1@dth.local', 'sales.create-quotations', true],
            'commercial cannot approve own business flow' => ['commercial.v1@dth.local', 'sales.approve-quotations', false],
            'commercial cannot reconcile payment' => ['commercial.v1@dth.local', 'sales.verify-payments', false],
            'commercial cannot operate support' => ['commercial.v1@dth.local', 'customer-care.interact', false],

            'support can interact with customers' => ['support.v1@dth.local', 'customer-care.interact', true],
            'support can manage tickets' => ['support.v1@dth.local', 'customer-care.manage-tickets', true],
            'support cannot create quotation' => ['support.v1@dth.local', 'sales.create-quotations', false],

            'finance can view payments' => ['finance.v1@dth.local', 'sales.view-payments', true],
            'finance can reconcile payments' => ['finance.v1@dth.local', 'sales.verify-payments', true],
            'finance can view revenue' => ['finance.v1@dth.local', 'sales.view-revenue-reports', true],
            'finance cannot create quotation' => ['finance.v1@dth.local', 'sales.create-quotations', false],
            'finance cannot manage marketing' => ['finance.v1@dth.local', 'marketing.manage-campaigns', false],
        ];
    }

    #[DataProvider('permissionMatrixProvider')]
    public function test_v1_permission_matrix(string $email, string $permission, bool $expected): void
    {
        $actual = app(RbacAuthorizationService::class)->allows(
            $this->user($email),
            $permission,
        );

        $this->assertSame($expected, $actual, "Unexpected [{$permission}] for {$email}");
    }
}
