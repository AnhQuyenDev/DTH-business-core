<?php

namespace App\Services\Sales;

use App\Enums\Sales\ApprovalStatus;
use App\Enums\Sales\QuotationStatus;
use App\Models\Sales\Quotation;
use App\Models\Sales\QuotationApproval;
use App\Models\User;
use App\Services\Marketing\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuotationApprovalService
{
    public function __construct(
        private readonly QuotationStateMachine $stateMachine,
        private readonly AuditLogService $auditLog,
    ) {}

    public function submitForApproval(Quotation $quotation, User $user): Quotation
    {
        if (! $user->can('update', $quotation)) {
            throw ValidationException::withMessages([
                'quotation' => 'Bạn không có quyền gửi báo giá này để phê duyệt.',
            ]);
        }

        $this->assertCommercialDocumentComplete($quotation);
        $this->stateMachine->validateTransition(
            $quotation->status,
            QuotationStatus::PendingApproval
        );

        DB::transaction(function () use ($quotation, $user): void {
            $quotation->update([
                'status' => QuotationStatus::PendingApproval,
                'updated_by' => $user->id,
            ]);

            // Do not create duplicate pending requests when a user double-clicks.
            $quotation->approvals()
                ->where('status', ApprovalStatus::Pending->value)
                ->update([
                    'status' => ApprovalStatus::Cancelled->value,
                    'reason' => 'Superseded by a newer approval request.',
                    'reviewed_at' => now(),
                ]);

            QuotationApproval::query()->create([
                'quotation_id' => $quotation->id,
                'step' => ((int) $quotation->approvals()->max('step')) + 1,
                'approver_user_id' => null,
                'approver_role' => 'sales_department_manager',
                'status' => ApprovalStatus::Pending,
                'requested_at' => now(),
            ]);

            $this->auditLog->log(
                'quotation.submitted_for_approval',
                $quotation,
                [],
                [
                    'status' => QuotationStatus::PendingApproval->value,
                    'requested_by_user_id' => $user->id,
                ],
            );
        });

        return $quotation->fresh();
    }

    public function approve(
        Quotation $quotation,
        User $user,
        ?string $reason = null,
    ): Quotation {
        if (! $user->can('approve', $quotation)) {
            throw ValidationException::withMessages([
                'quotation' => $quotation->created_by === $user->id
                    ? 'Người lập báo giá không được tự phê duyệt báo giá của mình.'
                    : 'Chỉ quản lý bộ phận Kinh doanh được duyệt báo giá.',
            ]);
        }

        $this->assertCommercialDocumentComplete($quotation);
        $this->stateMachine->validateTransition(
            $quotation->status,
            QuotationStatus::Approved
        );

        DB::transaction(function () use ($quotation, $user, $reason): void {
            $quotation->update([
                'status' => QuotationStatus::Approved,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'updated_by' => $user->id,
            ]);

            $quotation->approvals()
                ->where('status', ApprovalStatus::Pending->value)
                ->update([
                    'status' => ApprovalStatus::Approved->value,
                    'approver_user_id' => $user->id,
                    'approver_role' => 'sales_department_manager',
                    'reason' => $reason,
                    'reviewed_at' => now(),
                ]);

            $this->auditLog->log('quotation.approved', $quotation, [], [
                'approved_by' => $user->id,
            ]);
        });

        return $quotation->fresh();
    }

    public function logCancellation(
        Quotation $quotation,
        User $user,
        ?string $reason = null,
    ): Quotation {
        if (! $user->can('cancel', $quotation)) {
            throw ValidationException::withMessages([
                'quotation' => 'Bạn không có quyền hủy báo giá này.',
            ]);
        }

        $this->stateMachine->validateTransition(
            $quotation->status,
            QuotationStatus::Cancelled,
        );

        DB::transaction(function () use ($quotation, $user, $reason): void {
            QuotationApproval::query()->create([
                'quotation_id' => $quotation->id,
                'step' => ((int) $quotation->approvals()->max('step')) + 1,
                'approver_user_id' => $user->id,
                'approver_role' => 'sales_department_manager',
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

    public function reject(
        Quotation $quotation,
        User $user,
        string $reason,
    ): Quotation {
        if (! $user->can('approve', $quotation)) {
            throw ValidationException::withMessages([
                'quotation' => 'Chỉ quản lý bộ phận Kinh doanh được từ chối phê duyệt.',
            ]);
        }

        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'Vui lòng nhập lý do từ chối phê duyệt.',
            ]);
        }

        $this->stateMachine->validateTransition(
            $quotation->status,
            QuotationStatus::Draft
        );

        DB::transaction(function () use ($quotation, $user, $reason): void {
            $quotation->approvals()
                ->where('status', ApprovalStatus::Pending->value)
                ->update([
                    'status' => ApprovalStatus::Rejected->value,
                    'approver_user_id' => $user->id,
                    'approver_role' => 'sales_department_manager',
                    'reason' => $reason,
                    'reviewed_at' => now(),
                ]);

            $quotation->update([
                'status' => QuotationStatus::Draft,
                'approved_by' => null,
                'approved_at' => null,
                'updated_by' => $user->id,
            ]);

            $this->auditLog->log('quotation.approval_rejected', $quotation, [], [
                'reason' => $reason,
            ]);
        });

        return $quotation->fresh();
    }

    private function assertCommercialDocumentComplete(Quotation $quotation): void
    {
        $quotation->loadMissing(['items', 'bankAccount']);

        $errors = [];

        if ($quotation->items->isEmpty()) {
            $errors['items'] = 'Báo giá phải có ít nhất một dòng dịch vụ.';
        }

        if ((float) $quotation->grand_total <= 0) {
            $errors['grand_total'] = 'Tổng giá trị báo giá phải lớn hơn 0.';
        }

        if ($quotation->bank_account_id === null || $quotation->bankAccount === null) {
            $errors['bank_account_id'] = 'Hãy chọn tài khoản ngân hàng trước khi gửi phê duyệt.';
        } elseif ($quotation->bankAccount->status !== 'active') {
            $errors['bank_account_id'] = 'Tài khoản ngân hàng của báo giá không còn hoạt động.';
        }

        if (blank($quotation->party_email)) {
            $errors['recipient_email'] = 'Báo giá chưa có email người liên hệ.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
