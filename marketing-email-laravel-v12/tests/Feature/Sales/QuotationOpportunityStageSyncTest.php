<?php

namespace Tests\Feature\Sales;

use App\Enums\Sales\OpportunityStage;
use App\Models\Sales\Opportunity;
use App\Models\Sales\Quotation;
use App\Models\User;
use App\Services\Sales\QuotationOpportunitySyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationOpportunityStageSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private QuotationOpportunitySyncService $sync;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin-'.fake()->unique()->numberBetween(1, 999999).'@example.test',
            'password' => 'secret',
            'role' => 'admin',
        ]);

        $this->sync = app(QuotationOpportunitySyncService::class);
    }

    private function makeQuotationFor(OpportunityStage $stage): array
    {
        $opportunity = Opportunity::factory()
            ->state(['stage' => $stage->value])
            ->create();

        $quotation = Quotation::factory()->forOpportunity($opportunity)->create([
            'created_by' => $this->admin->id,
        ]);

        return [$opportunity, $quotation];
    }

    public function test_sent_moves_qualified_opportunity_to_proposal(): void
    {
        [$opportunity, $quotation] = $this->makeQuotationFor(OpportunityStage::Qualified);

        $this->sync->onSent($quotation);

        $this->assertSame(
            OpportunityStage::Proposal,
            $opportunity->fresh()->stage
        );
    }

    public function test_viewed_moves_qualified_opportunity_to_proposal(): void
    {
        [$opportunity, $quotation] = $this->makeQuotationFor(OpportunityStage::Qualified);

        $this->sync->onViewed($quotation);

        $this->assertSame(
            OpportunityStage::Proposal,
            $opportunity->fresh()->stage
        );
    }

    public function test_accepted_moves_proposal_opportunity_to_negotiation(): void
    {
        [$opportunity, $quotation] = $this->makeQuotationFor(OpportunityStage::Proposal);

        $this->sync->onAccepted($quotation);

        $this->assertSame(
            OpportunityStage::Negotiation,
            $opportunity->fresh()->stage
        );
    }

    public function test_accepted_moves_qualified_opportunity_to_negotiation(): void
    {
        [$opportunity, $quotation] = $this->makeQuotationFor(OpportunityStage::Qualified);

        $this->sync->onAccepted($quotation);

        $this->assertSame(
            OpportunityStage::Negotiation,
            $opportunity->fresh()->stage
        );
    }

    public function test_revision_requested_moves_proposal_opportunity_to_negotiation(): void
    {
        [$opportunity, $quotation] = $this->makeQuotationFor(OpportunityStage::Proposal);

        $this->sync->onRevisionRequested($quotation);

        $this->assertSame(
            OpportunityStage::Negotiation,
            $opportunity->fresh()->stage
        );
    }

    public function test_rejected_does_not_lose_opportunity(): void
    {
        [$opportunity, $quotation] = $this->makeQuotationFor(OpportunityStage::Qualified);

        $this->sync->onRejected($quotation);

        $this->assertSame(
            OpportunityStage::Qualified,
            $opportunity->fresh()->stage
        );
    }

    public function test_accepted_never_moves_opportunity_to_won(): void
    {
        [$opportunity, $quotation] = $this->makeQuotationFor(OpportunityStage::Proposal);

        $this->sync->onAccepted($quotation);

        $fresh = $opportunity->fresh();
        $this->assertNotSame(OpportunityStage::Won, $fresh->stage);
        $this->assertSame(OpportunityStage::Negotiation, $fresh->stage);
        $this->assertNull($fresh->won_at);
    }
}
