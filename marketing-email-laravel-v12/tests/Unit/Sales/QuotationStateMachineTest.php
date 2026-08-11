<?php

namespace Tests\Unit\Sales;

use App\Enums\Sales\QuotationStatus;
use App\Services\Sales\QuotationStateMachine;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuotationStateMachineTest extends TestCase
{
    private QuotationStateMachine $stateMachine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stateMachine = new QuotationStateMachine();
    }

    public static function allowedTransitionsProvider(): array
    {
        return [
            'draft -> pending approval' => [QuotationStatus::Draft, QuotationStatus::PendingApproval],
            'draft -> approved by policy' => [QuotationStatus::Draft, QuotationStatus::Approved],
            'draft -> cancelled' => [QuotationStatus::Draft, QuotationStatus::Cancelled],
            'pending -> approved' => [QuotationStatus::PendingApproval, QuotationStatus::Approved],
            'pending -> draft after rejection' => [QuotationStatus::PendingApproval, QuotationStatus::Draft],
            'pending -> cancelled' => [QuotationStatus::PendingApproval, QuotationStatus::Cancelled],
            'approved -> sent' => [QuotationStatus::Approved, QuotationStatus::Sent],
            'approved -> cancelled' => [QuotationStatus::Approved, QuotationStatus::Cancelled],
            'sent -> viewed' => [QuotationStatus::Sent, QuotationStatus::Viewed],
            'sent -> accepted' => [QuotationStatus::Sent, QuotationStatus::Accepted],
            'sent -> rejected' => [QuotationStatus::Sent, QuotationStatus::Rejected],
            'sent -> revision' => [QuotationStatus::Sent, QuotationStatus::RevisionRequested],
            'viewed -> accepted' => [QuotationStatus::Viewed, QuotationStatus::Accepted],
            'viewed -> rejected' => [QuotationStatus::Viewed, QuotationStatus::Rejected],
            'viewed -> revision' => [QuotationStatus::Viewed, QuotationStatus::RevisionRequested],
            'revision -> superseded' => [QuotationStatus::RevisionRequested, QuotationStatus::Superseded],
        ];
    }

    #[DataProvider('allowedTransitionsProvider')]
    public function test_allowed_transition_matrix(QuotationStatus $from, QuotationStatus $to): void
    {
        $this->assertTrue($this->stateMachine->canTransition($from, $to));
        $this->stateMachine->validateTransition($from, $to);
        $this->addToAssertionCount(1);
    }

    public static function forbiddenTransitionsProvider(): array
    {
        return [
            'draft cannot send directly' => [QuotationStatus::Draft, QuotationStatus::Sent],
            'approved cannot become accepted directly' => [QuotationStatus::Approved, QuotationStatus::Accepted],
            'accepted is terminal' => [QuotationStatus::Accepted, QuotationStatus::Sent],
            'rejected is terminal' => [QuotationStatus::Rejected, QuotationStatus::Draft],
            'cancelled is terminal' => [QuotationStatus::Cancelled, QuotationStatus::Draft],
            'superseded is terminal' => [QuotationStatus::Superseded, QuotationStatus::Draft],
            'expired is terminal' => [QuotationStatus::Expired, QuotationStatus::Sent],
        ];
    }

    #[DataProvider('forbiddenTransitionsProvider')]
    public function test_forbidden_transition_matrix(QuotationStatus $from, QuotationStatus $to): void
    {
        $this->assertFalse($this->stateMachine->canTransition($from, $to));
        $this->expectException(ValidationException::class);
        $this->stateMachine->validateTransition($from, $to);
    }

    public function test_status_capabilities_match_v1_contract(): void
    {
        $this->assertTrue(QuotationStatus::Draft->isEditable());
        $this->assertTrue(QuotationStatus::PendingApproval->isEditable());
        $this->assertTrue(QuotationStatus::RevisionRequested->isEditable());
        $this->assertFalse(QuotationStatus::Sent->isEditable());
        $this->assertTrue(QuotationStatus::Approved->canSend());
        $this->assertTrue(QuotationStatus::Sent->canConfirm());
        $this->assertTrue(QuotationStatus::Viewed->canConfirm());
        $this->assertFalse(QuotationStatus::Draft->canConfirm());
    }
}
