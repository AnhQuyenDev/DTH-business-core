<?php

namespace Tests\Feature\Sales;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\LeadIntakeStatus;
use App\Models\Crm\Company;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Lead;
use App\Models\Crm\Staff;
use App\Models\Sales\Opportunity;
use App\Models\User;
use App\Services\Sales\OpportunityCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OpportunityCreationTest extends TestCase
{
    use RefreshDatabase;

    private function makeLeadWithStatus(string $status, array $overrides = []): Lead
    {
        $lead = Lead::factory()->create($overrides);

        ContactQualification::factory()->create([
            'lead_id' => $lead->id,
            'contact_id' => $lead->contact_id,
            'status' => $status,
        ]);

        return $lead->fresh('qualification');
    }

    private function makeQualifiedLead(array $overrides = []): Lead
    {
        return $this->makeLeadWithStatus(
            ContactQualificationStatus::Qualified->value,
            $overrides,
        );
    }

    private function service(): OpportunityCreationService
    {
        return app(OpportunityCreationService::class);
    }

    public function test_unqualified_lead_cannot_create_opportunity(): void
    {
        $lead = $this->makeLeadWithStatus(
            ContactQualificationStatus::Contacting->value,
        );

        $user = User::factory()->create();

        $this->expectException(ValidationException::class);

        $this->service()->createFromQualifiedLead(
            $lead,
            [],
            $user->id,
        );
    }

    public function test_qualified_lead_creates_opportunity_in_qualified_stage(): void
    {
        $lead = $this->makeQualifiedLead();
        $user = User::factory()->create();

        $opportunity = $this->service()->createFromQualifiedLead(
            $lead,
            [],
            $user->id,
        );

        $this->assertDatabaseHas('sales_opportunities', [
            'id' => $opportunity->id,
            'lead_id' => $lead->id,
            'primary_contact_id' => $lead->contact_id,
            'stage' => 'qualified',
        ]);

        $this->assertStringStartsWith('OPP-2026-', $opportunity->opportunity_code);
        $this->assertSame(1, Opportunity::query()->count());

        $lead->refresh();
        $this->assertSame(
            LeadIntakeStatus::ConvertedToOpportunity,
            $lead->intake_status
        );
        $this->assertNotNull($lead->converted_to_opportunity_at);
    }

    public function test_same_lead_only_creates_one_opportunity(): void
    {
        $lead = $this->makeQualifiedLead();
        $user = User::factory()->create();

        $this->service()->createFromQualifiedLead($lead, [], $user->id);
        $this->service()->createFromQualifiedLead($lead, [], $user->id);

        $this->assertSame(1, Opportunity::query()->count());
        $this->assertSame(
            1,
            Opportunity::query()->where('lead_id', $lead->id)->count()
        );
    }

    public function test_primary_contact_is_synced_to_opportunity_contacts(): void
    {
        $lead = $this->makeQualifiedLead();
        $user = User::factory()->create();

        $opportunity = $this->service()->createFromQualifiedLead(
            $lead,
            [],
            $user->id,
        );

        $this->assertDatabaseHas('opportunity_contacts', [
            'opportunity_id' => $opportunity->id,
            'contact_id' => $lead->contact_id,
            'role' => 'primary_contact',
            'is_primary' => 1,
        ]);
    }

    public function test_company_is_carried_from_lead(): void
    {
        $company = Company::query()->create([
            'company_code' => 'COM-2026-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'legal_name' => 'Công ty TNHH ABC',
        ]);

        $lead = $this->makeQualifiedLead(['company_id' => $company->id]);
        $user = User::factory()->create();

        $opportunity = $this->service()->createFromQualifiedLead(
            $lead,
            [],
            $user->id,
        );

        $this->assertSame($company->id, $opportunity->company_id);
    }

    public function test_data_overrides_are_applied(): void
    {
        $lead = $this->makeQualifiedLead();
        $user = User::factory()->create();

        $opportunity = $this->service()->createFromQualifiedLead(
            $lead,
            [
                'title' => 'Cơ hội Email Marketing',
                'estimated_value' => 25000000,
                'probability' => 80,
            ],
            $user->id,
        );

        $this->assertSame('Cơ hội Email Marketing', $opportunity->title);
        $this->assertSame('25000000.00', $opportunity->estimated_value);
        $this->assertSame(80, $opportunity->probability);
    }

    public function test_opportunity_owner_defaults_to_lead_owner(): void
    {
        $staff = Staff::factory()->create();
        $lead = $this->makeQualifiedLead(['assigned_staff_id' => $staff->id]);
        $user = User::factory()->create();

        $opportunity = $this->service()->createFromQualifiedLead(
            $lead,
            [],
            $user->id,
        );

        $this->assertSame($staff->id, $opportunity->assigned_staff_id);
    }
}
