<?php

namespace Tests\Feature\Sales;

use App\Enums\Sales\OpportunityStage;
use App\Filament\Resources\Sales\OpportunityResource;
use App\Models\Crm\Staff;
use App\Models\Marketing\Contact;
use App\Models\Sales\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityAuthorizationTest extends TestCase
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

    private function makeOpportunity(array $overrides = []): Opportunity
    {
        $contact = Contact::factory()->create();

        return Opportunity::query()->create(array_merge([
            'opportunity_code' => 'OPP-2026-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'primary_contact_id' => $contact->id,
            'title' => 'Cơ hội VPS',
            'stage' => OpportunityStage::Qualified->value,
        ], $overrides));
    }

    public function test_admin_can_view_opportunities(): void
    {
        $this->actingAs($this->makeUser('admin'));

        $this->assertTrue(OpportunityResource::canViewAny());
        $this->assertTrue(OpportunityResource::shouldRegisterNavigation());
    }

    public function test_customer_service_manager_can_view_opportunities(): void
    {
        $this->actingAs($this->makeUser('customer_service_manager'));

        $this->assertTrue(OpportunityResource::canViewAny());
    }

    public function test_customer_service_staff_can_view_opportunities(): void
    {
        $this->actingAs($this->makeUser('customer_service_staff'));

        $this->assertTrue(OpportunityResource::canViewAny());
    }

    public function test_marketing_staff_cannot_access_opportunities(): void
    {
        $this->actingAs($this->makeUser('marketing_staff'));

        $this->assertFalse(OpportunityResource::canViewAny());
        $this->assertFalse(OpportunityResource::shouldRegisterNavigation());
    }

    public function test_guest_cannot_access_opportunities(): void
    {
        $this->assertFalse(OpportunityResource::canViewAny());
        $this->assertFalse(OpportunityResource::canCreate());
    }

    public function test_resource_hidden_when_v2_flag_disabled(): void
    {
        config()->set('business_flow.v2_enabled', false);

        $this->actingAs($this->makeUser('admin'));

        $this->assertFalse(OpportunityResource::canViewAny());
        $this->assertFalse(OpportunityResource::shouldRegisterNavigation());
    }

    public function test_customer_service_staff_only_sees_assigned_opportunities(): void
    {
        $user = $this->makeUser('customer_service_staff');
        $staff = Staff::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        $assigned = $this->makeOpportunity([
            'assigned_staff_id' => $staff->id,
        ]);
        $this->makeOpportunity([
            'assigned_staff_id' => Staff::factory()->create()->id,
        ]);

        $ids = OpportunityResource::scopeForUser(
            Opportunity::query()
        )->pluck('id')->all();

        $this->assertSame([$assigned->id], $ids);
    }

    public function test_admin_sees_all_opportunities(): void
    {
        $this->actingAs($this->makeUser('admin'));

        $first = $this->makeOpportunity();
        $second = $this->makeOpportunity();

        $ids = OpportunityResource::scopeForUser(
            Opportunity::query()
        )->orderBy('id')->pluck('id')->all();

        $this->assertSame([$first->id, $second->id], $ids);
    }
}
