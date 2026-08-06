<?php

namespace Tests\Feature\Sales;

use App\Enums\Sales\OpportunityStage;
use App\Models\Marketing\Contact;
use App\Models\Sales\Opportunity;
use App\Services\Sales\OpportunityContactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OpportunityContactServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeOpportunity(): Opportunity
    {
        $contact = Contact::factory()->create();

        $opportunity = Opportunity::query()->create([
            'opportunity_code' => 'OPP-TEST-000001',
            'primary_contact_id' => $contact->id,
            'title' => 'Test Opportunity',
            'stage' => OpportunityStage::Qualified->value,
        ]);

        $opportunity->contacts()->attach($contact->id, [
            'role' => 'primary_contact',
            'is_primary' => true,
        ]);

        return $opportunity;
    }

    public function test_setting_new_primary_updates_both_sources(): void
    {
        $opportunity = $this->makeOpportunity();
        $newContact = Contact::factory()->create();

        app(OpportunityContactService::class)->upsert(
            opportunity: $opportunity,
            contactId: $newContact->id,
            role: 'decision_maker',
            isPrimary: true,
        );

        $this->assertDatabaseHas('sales_opportunities', [
            'id' => $opportunity->id,
            'primary_contact_id' => $newContact->id,
        ]);

        $this->assertDatabaseHas('opportunity_contacts', [
            'opportunity_id' => $opportunity->id,
            'contact_id' => $newContact->id,
            'role' => 'primary_contact',
            'is_primary' => 1,
        ]);

        $this->assertDatabaseHas('opportunity_contacts', [
            'opportunity_id' => $opportunity->id,
            'contact_id' => $opportunity->primary_contact_id,
            'is_primary' => 0,
        ]);
    }

    public function test_primary_contact_cannot_be_detached(): void
    {
        $opportunity = $this->makeOpportunity();

        $this->expectException(
            ValidationException::class
        );

        app(OpportunityContactService::class)->detach(
            opportunity: $opportunity,
            contactId: $opportunity->primary_contact_id,
        );
    }
}
