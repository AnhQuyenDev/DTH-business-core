<?php

namespace Tests\Feature\Crm;

use App\Filament\Resources\CompanyResource;
use App\Models\Crm\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyResourceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('business_flow.v2_enabled', true);
    }

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function makeCompany(): Company
    {
        return Company::query()->create([
            'company_code' => 'COM-2026-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'legal_name' => 'Công ty TNHH ABC',
        ]);
    }

    public function test_admin_can_view_create_edit_and_delete(): void
    {
        $this->actingAs($this->makeUser('admin'));

        $this->assertTrue(CompanyResource::canViewAny());
        $this->assertTrue(CompanyResource::canCreate());
        $this->assertTrue(CompanyResource::canEdit($this->makeCompany()));
        $this->assertTrue(CompanyResource::canDelete($this->makeCompany()));
    }

    public function test_customer_service_staff_can_view_but_not_modify(): void
    {
        $this->actingAs($this->makeUser('customer_service_staff'));

        $this->assertTrue(CompanyResource::canViewAny());
        $this->assertFalse(CompanyResource::canCreate());
        $this->assertFalse(CompanyResource::canEdit($this->makeCompany()));
        $this->assertFalse(CompanyResource::canDelete($this->makeCompany()));
    }

    public function test_marketing_staff_cannot_access_at_all(): void
    {
        $this->actingAs($this->makeUser('marketing_staff'));

        $this->assertFalse(CompanyResource::canViewAny());
        $this->assertFalse(CompanyResource::canCreate());
    }

    public function test_guest_cannot_access(): void
    {
        $this->assertFalse(CompanyResource::canViewAny());
        $this->assertFalse(CompanyResource::canCreate());
    }

    public function test_resource_hidden_when_v2_flag_disabled(): void
    {
        config()->set('business_flow.v2_enabled', false);

        $this->actingAs($this->makeUser('admin'));

        $this->assertFalse(CompanyResource::shouldRegisterNavigation());
    }
}
