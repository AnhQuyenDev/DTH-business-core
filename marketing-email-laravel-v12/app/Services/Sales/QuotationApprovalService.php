<?php

namespace App\Services\Sales;

use App\Enums\Sales\ApprovalStatus;
use App\Enums\Sales\QuotationStatus;
use App\Models\Sales\Quotation;
use App\Models\Sales\QuotationApproval;
use App\Models\User;
use App\Services\Marketing\AuditLogService;
use Illuminate\Support\Facades\DB;

class QuotationApprovalService
{
    public function __construct(
        private readonly QuotationStateMachine $stateMachine,
        private readonly AuditLogService $auditLog,
    ) {}

    public function submitForApproval(Quotation $quotation, User $user): Quotation
    {
        $this->stateMachine->validateTransition($quotation->status, QuotationStatus::PendingApproval);

        DB::transaction(function () use ($quotation, $user) {
            $quotation->update([
                'status' => QuotationStatus::PendingApproval,
                'updated_by' => $user->id,
            ]);

            QuotationApproval::query()->create([
                'quotation_id' => $quotation->id,
                'step' => 1,
                'approver_user_id' => null,
                'approver_role' => 'customer_service_manager',
                'status' => ApprovalStatus::Pending,
                'requested_at' => now(),
            ]);

            $this->auditLog->log('quotation.submitted_for_approval', $quotation, [], [
                'status' => QuotationStatus::PendingApproval->value,
            ]);
        });

        return $quotation->fresh();
    }

    public function approve(Quotation $quotation, User $user, ?string $reason = null): Quotation
    {
        $this->stateMachine->validateTransition($quotation->status, QuotationStatus::Approved);

        DB::transaction(function () use ($quotation, $user, $reason) {
            $quotation->update([
                'status' => QuotationStatus::Approved,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'updated_by' => $user->id,
            ]);

            $quotation->approvals()
                ->where('status', ApprovalStatus::Pending)
                ->update([
                    'status' => ApprovalStatus::Approved,
                    'approver_user_id' => $user->id,
                    'reason' => $reason,
                    'reviewed_at' => now(),
                ]);

            $this->auditLog->log('quotation.approved', $quotation, [], [
                'approved_by' => $user->id,
            ]);
        });

        return $quotation->fresh();
    }

    public function logCancellation(Quotation $quotation, User $user, ?string $reason = null): Quotation
    {
        DB::transaction(function () use ($quotation, $user, $reason) {
            QuotationApproval::query()->create([
                'quotation_id' => $quotation->id,
                'step' => $quotation->approvals()->max('step') + 1,
                'approver_user_id' => $user->id,
                'approver_role' => 'customer_service_manager',
                'status' => ApprovalStatus::Cancelled,
                'reason' => $reason ?: __('note.cancel_from_list'),
                'requested_at' => now(),
                'reviewed_at' => now(),
            ]);

            $quotation->update([
                'status' => QuotationStatus::Cancelled,
                'cancelled_at' => now(),
                'updated_by' => $user->id,
            ]);

            $this->auditLog->log('quotation.cancelled', $quotation, [], [
                'reason' => $reason,
            ]);
        });

        return $quotation->fresh();
    }

    public function reject(Quotation $quotation, User $user, string $reason): Quotation
    {
        $this->stateMachine->validateTransition($quotation->status, QuotationStatus::Approved);

        DB::transaction(function () use ($quotation, $user, $reason) {
            $quotation->approvals()
                ->where('status', ApprovalStatus::Pending)
                ->update([
                    'status' => ApprovalStatus::Rejected,
                    'approver_user_id' => $user->id,
                    'reason' => $reason,
                    'reviewed_at' => now(),
                ]);

            $quotation->update([
                'status' => QuotationStatus::Draft,
                'updated_by' => $user->id,
            ]);

            $this->auditLog->log('quotation.approval_rejected', $quotation, [], [
                'reason' => $reason,
            ]);
        });

        return $quotation->fresh();
    }
}
