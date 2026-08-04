<?php

namespace Tests\Unit\Sales;

use App\Enums\Sales\QuotationStatus;
use App\Services\Sales\QuotationStateMachine;
use PHPUnit\Framework\TestCase;

class QuotationStateMachineTest extends TestCase
{
    private QuotationStateMachine $stateMachine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stateMachine = new QuotationStateMachine;
    }

    public function test_draft_can_transition_to_pending_approval(): void
    {
        $this->assertTrue(
            $this->stateMachine->canTransition(QuotationStatus::Draft, QuotationStatus::PendingApproval)
        );
    }

    public function test_draft_can_transition_to_approved(): void
    {
        $this->assertTrue(
            $this->stateMachine->canTransition(QuotationStatus::Draft, QuotationStatus::Approved)
        );
    }

    public function test_draft_can_transition_to_cancelled(): void
    {
        $this->assertTrue(
            $this->stateMachine->canTransition(QuotationStatus::Draft, QuotationStatus::Cancelled)
        );
    }

    public function test_draft_cannot_transition_to_sent(): void
    {
        $this->assertFalse(
            $this->stateMachine->canTransition(QuotationStatus::Draft, QuotationStatus::Sent)
        );
    }

    public function test_approved_can_transition_to_sent(): void
    {
        $this->assertTrue(
            $this->stateMachine->canTransition(QuotationStatus::Approved, QuotationStatus::Sent)
        );
    }

    public function test_sent_can_transition_to_viewed(): void
    {
        $this->assertTrue(
            $this->stateMachine->canTransition(QuotationStatus::Sent, QuotationStatus::Viewed)
        );
    }

    public function test_viewed_can_transition_to_accepted(): void
    {
        $this->assertTrue(
            $this->stateMachine->canTransition(QuotationStatus::Viewed, QuotationStatus::Accepted)
        );
    }

    public function test_accepted_is_terminal(): void
    {
        $this->assertTrue(QuotationStatus::Accepted->isTerminal());
    }

    public function test_draft_is_editable(): void
    {
        $this->assertTrue(QuotationStatus::Draft->isEditable());
    }

    public function test_sent_is_not_editable(): void
    {
        $this->assertFalse(QuotationStatus::Sent->isEditable());
    }

    public function test_approved_can_send(): void
    {
        $this->assertTrue(QuotationStatus::Approved->canSend());
    }

    public function test_sent_can_confirm(): void
    {
        $this->assertTrue(QuotationStatus::Sent->canConfirm());
    }

    public function test_viewed_can_confirm(): void
    {
        $this->assertTrue(QuotationStatus::Viewed->canConfirm());
    }

    public function test_invalid_transition_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->stateMachine->validateTransition(QuotationStatus::Draft, QuotationStatus::Sent);
    }

    public function test_draft_cannot_confirm(): void
    {
        $this->assertFalse(QuotationStatus::Draft->canConfirm());
    }

    public function test_all_terminal_statuses(): void
    {
        $this->assertTrue(QuotationStatus::Accepted->isTerminal());
        $this->assertTrue(QuotationStatus::Rejected->isTerminal());
        $this->assertTrue(QuotationStatus::Cancelled->isTerminal());
        $this->assertTrue(QuotationStatus::Superseded->isTerminal());
        $this->assertTrue(QuotationStatus::Expired->isTerminal());
    }

    public function test_non_terminal_statuses(): void
    {
        $this->assertFalse(QuotationStatus::Draft->isTerminal());
        $this->assertFalse(QuotationStatus::Sent->isTerminal());
        $this->assertFalse(QuotationStatus::Viewed->isTerminal());
    }
}
