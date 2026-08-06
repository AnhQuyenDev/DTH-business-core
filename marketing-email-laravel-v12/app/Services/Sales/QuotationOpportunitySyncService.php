<?php

namespace App\Services\Sales;

use App\Enums\Sales\OpportunityStage;
use App\Models\Sales\Quotation;

final class QuotationOpportunitySyncService
{
    public function __construct(
        private readonly OpportunityWorkflowService $workflow,
    ) {}

    public function onSent(Quotation $quotation): void
    {
        $this->moveToProposal($quotation);
    }

    public function onViewed(Quotation $quotation): void
    {
        $this->moveToProposal($quotation);
    }

    public function onAccepted(Quotation $quotation): void
    {
        $opportunity = $quotation->opportunity;

        if (! $opportunity) {
            return;
        }

        $stage = $opportunity->stage instanceof OpportunityStage
            ? $opportunity->stage
            : OpportunityStage::from((string) $opportunity->stage);

        if ($stage === OpportunityStage::Qualified) {
            $this->workflow->transition(
                $opportunity,
                OpportunityStage::Proposal,
                actorUserId: $quotation->updated_by
                    ?? $quotation->created_by,
            );

            $opportunity->refresh();
            $stage = $opportunity->stage;
        }

        if ($stage === OpportunityStage::Proposal) {
            $this->workflow->transition(
                $opportunity,
                OpportunityStage::Negotiation,
                actorUserId: $quotation->updated_by
                    ?? $quotation->created_by,
            );
        }
    }

    public function onRevisionRequested(
        Quotation $quotation
    ): void {
        $this->onAccepted($quotation);
    }

    public function onRejected(Quotation $quotation): void
    {
        /*
         * Không tự Lost. Từ chối một phiên bản báo giá chưa chắc
         * đồng nghĩa thất bại cả Opportunity.
         */
    }

    private function moveToProposal(
        Quotation $quotation
    ): void {
        $opportunity = $quotation->opportunity;

        if (! $opportunity) {
            return;
        }

        $stage = $opportunity->stage instanceof OpportunityStage
            ? $opportunity->stage
            : OpportunityStage::from((string) $opportunity->stage);

        if ($stage !== OpportunityStage::Qualified) {
            return;
        }

        $this->workflow->transition(
            $opportunity,
            OpportunityStage::Proposal,
            actorUserId: $quotation->updated_by
                ?? $quotation->created_by,
        );
    }
}
