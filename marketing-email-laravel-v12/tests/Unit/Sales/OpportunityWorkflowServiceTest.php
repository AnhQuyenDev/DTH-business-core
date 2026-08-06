<?php

namespace Tests\Unit\Sales;

use App\Enums\Sales\OpportunityStage;
use App\Models\Marketing\Contact;
use App\Models\Sales\Opportunity;
use App\Services\Sales\OpportunityWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OpportunityWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    private function makeOpportunity(string $stage): Opportunity
    {
        $contact = Contact::factory()->create();

        return Opportunity::query()->create([
            'opportunity_code' => 'OPP-'.now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'primary_contact_id' => $contact->id,
            'title' => 'Cơ hội VPS',
            'stage' => $stage,
        ]);
    }

    private function service(): OpportunityWorkflowService
    {
        return app(OpportunityWorkflowService::class);
    }

    public function test_qualified_can_advance_to_proposal(): void
    {
        $opportunity = $this->makeOpportunity(OpportunityStage::Qualified->value);

        $result = $this->service()->transition(
            $opportunity,
            OpportunityStage::Proposal,
        );

        $this->assertSame(OpportunityStage::Proposal, $result->stage);
        $this->assertSame(70, $result->probability);
    }

    public function test_cannot_revert_from_qualified_to_discovery(): void
    {
        $opportunity = $this->makeOpportunity(OpportunityStage::Qualified->value);

        $this->expectException(ValidationException::class);

        $this->service()->transition(
            $opportunity,
            OpportunityStage::Discovery,
        );
    }

    public function test_negotiation_can_move_back_to_proposal(): void
    {
        $opportunity = $this->makeOpportunity(OpportunityStage::Negotiation->value);

        $result = $this->service()->transition(
            $opportunity,
            OpportunityStage::Proposal,
        );

        $this->assertSame(OpportunityStage::Proposal, $result->stage);
    }

    public function test_terminal_stage_cannot_transition(): void
    {
        $opportunity = $this->makeOpportunity(OpportunityStage::Won->value);

        $this->expectException(ValidationException::class);

        $this->service()->transition(
            $opportunity,
            OpportunityStage::Lost,
        );
    }

    public function test_won_cannot_be_selected_manually_regardless_of_feature_flag(): void
    {
        foreach ([true, false] as $flagValue) {
            config()->set(
                'business_flow.customer_on_paid_only',
                $flagValue
            );

            $opportunity = $this->makeOpportunity(
                OpportunityStage::Negotiation->value
            );

            try {
                $this->service()->transition(
                    $opportunity,
                    OpportunityStage::Won,
                );

                $this->fail(
                    'Won không được phép chuyển thủ công.'
                );
            } catch (ValidationException) {
                $this->assertDatabaseHas('sales_opportunities', [
                    'id' => $opportunity->id,
                    'stage' => OpportunityStage::Negotiation->value,
                ]);
            }
        }
    }

    public function test_won_is_allowed_only_from_payment_service(): void
    {
        $opportunity = $this->makeOpportunity(
            OpportunityStage::Negotiation->value
        );

        $result = $this->service()->transition(
            opportunity: $opportunity,
            to: OpportunityStage::Won,
            fromPaymentService: true,
        );

        $this->assertSame(
            OpportunityStage::Won,
            $result->stage
        );

        $this->assertNotNull($result->won_at);
    }

    public function test_lost_requires_reason(): void
    {
        $opportunity = $this->makeOpportunity(OpportunityStage::Qualified->value);

        $this->expectException(ValidationException::class);

        $this->service()->transition(
            $opportunity,
            OpportunityStage::Lost,
        );
    }

    public function test_lost_with_reason_records_timestamps_and_resets_probability(): void
    {
        $opportunity = $this->makeOpportunity(OpportunityStage::Qualified->value);

        $result = $this->service()->transition(
            $opportunity,
            OpportunityStage::Lost,
            ['lost_reason' => 'Khách chọn đối thủ'],
        );

        $this->assertSame(OpportunityStage::Lost, $result->stage);
        $this->assertNotNull($result->lost_at);
        $this->assertSame('Khách chọn đối thủ', $result->lost_reason);
        $this->assertSame(0, $result->probability);
    }

    public function test_discovery_can_be_cancelled(): void
    {
        $opportunity = $this->makeOpportunity(OpportunityStage::Discovery->value);

        $result = $this->service()->transition(
            $opportunity,
            OpportunityStage::Cancelled,
        );

        $this->assertSame(OpportunityStage::Cancelled, $result->stage);
        $this->assertSame(0, $result->probability);
    }
}
