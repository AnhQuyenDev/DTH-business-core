<?php

namespace App\Services\Sales;

use App\Actions\Sales\ConvertWonOpportunityToCustomerAction;
use App\Enums\Crm\CustomerLifecycleStage;
use App\Enums\Crm\CustomerStatus;
use App\Enums\Sales\PaymentNoticeStatus;
use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\QuotationStatus;
use App\Jobs\Sales\SendPaymentConfirmedNotificationJob;
use App\Models\Crm\Customer;
use App\Models\Finance\Payment;
use App\Models\Sales\Quotation;
use App\Models\User;
use App\Services\Finance\PaymentLedgerService;
use App\Services\Finance\PaymentReceiptService;
use App\Services\Business\WorkflowPolicyService;
use App\Services\Billing\ElectronicInvoiceService;
use App\Services\Marketing\AuditLogService;
use App\Services\Security\BusinessNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class QuotationPaymentService
{
    private const VALID_TRANSITIONS = [
        'not_required' => ['unpaid'],
        'unpaid' => [
            'pending_verification',
            'paid', // legacy; V2 still requires a customer payment notice before Paid.
            'cancelled',
        ],
        'pending_verification' => [
            'paid',
            'unpaid',
            'cancelled',
        ],
        'partially_paid' => [
            'paid',
            'pending_verification',
            'cancelled',
        ],
        'paid' => ['refunded'],
        'refunded' => [],
        'cancelled' => [],
    ];

    public function __construct(
        private readonly AuditLogService $auditLog,
        private readonly ConvertWonOpportunityToCustomerAction $converter,
        private readonly PaymentLedgerService $ledger,
        private readonly PaymentReceiptService $receiptService,
        private readonly WorkflowPolicyService $workflowPolicy,
        private readonly ElectronicInvoiceService $electronicInvoice,
        private readonly BusinessNotificationService $notifications,
    ) {}

    public function updateStatus(
        Quotation $quotation,
        PaymentStatus $newStatus,
        User $user,
        ?string $note = null,
    ): Quotation {
        if (! $user->can('verifyPayment', $quotation)) {
            throw ValidationException::withMessages([
                'payment_status' => 'Chỉ bộ phận Tài chính được xác minh trạng thái thanh toán.',
            ]);
        }

        if (
            in_array($newStatus, [PaymentStatus::PendingVerification, PaymentStatus::Paid], true)
            && $quotation->status !== QuotationStatus::Accepted
        ) {
            throw ValidationException::withMessages([
                'payment_status' => 'Chỉ báo giá đã được khách hàng chấp nhận mới được đối soát thanh toán.',
            ]);
        }

        $result = DB::transaction(function () use (
            $quotation,
            $newStatus,
            $user,
            $note,
        ): array {
            $locked = Quotation::query()
                ->with([
                    'opportunity',
                    'customer',
                    'payment',
                ])
                ->whereKey($quotation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldStatus = $locked->payment_status;

            if ($newStatus === PaymentStatus::Paid && $oldStatus === PaymentStatus::Paid) {
                return [
                    'quotation_id' => $locked->id,
                    'payment_id' => $locked->payment?->id,
                    'payment_became_paid' => false,
                ];
            }

            $this->validateTransition($oldStatus, $newStatus);

            $pendingNotice = $locked->paymentNotices()
                ->with('files')
                ->where('status', PaymentNoticeStatus::Pending->value)
                ->latest('id')
                ->first();

            if (
                $newStatus === PaymentStatus::Paid
                && config('business_flow.v2_enabled')
            ) {
                if ($pendingNotice === null) {
                    throw ValidationException::withMessages([
                        'payment_status' => 'Khách hàng chưa gửi thông báo chuyển khoản để Tài chính đối soát.',
                    ]);
                }

                if (
                    $this->workflowPolicy->paymentEvidenceRequired()
                    && $pendingNotice->files->isEmpty()
                ) {
                    throw ValidationException::withMessages([
                        'payment_status' => 'Chính sách hiện tại yêu cầu chứng từ chuyển khoản trước khi xác nhận Paid.',
                    ]);
                }
            }

            $locked->update([
                'payment_status' => $newStatus->value,
                'paid_at' => $newStatus === PaymentStatus::Paid
                    ? ($locked->paid_at ?? now())
                    : $locked->paid_at,
                'payment_verified_by_user_id' => $newStatus === PaymentStatus::Paid
                    ? $user->id
                    : $locked->payment_verified_by_user_id,
                'payment_note' => $note ?? $locked->payment_note,
                'updated_by' => $user->id,
            ]);

            $this->auditLog->log(
                'quotation.payment_updated',
                $locked,
                ['old_status' => $oldStatus?->value],
                [
                    'new_status' => $newStatus->value,
                    'note' => $note,
                    'verified_by_user_id' => $user->id,
                    'payment_notice_id' => $pendingNotice?->id,
                ],
            );

            if ($pendingNotice !== null && $newStatus === PaymentStatus::Paid) {
                $pendingNotice->update([
                    'status' => PaymentNoticeStatus::Verified->value,
                    'reviewed_at' => now(),
                    'reviewed_by_user_id' => $user->id,
                    'review_note' => $note,
                ]);
            } elseif ($pendingNotice !== null && $newStatus === PaymentStatus::Unpaid) {
                $pendingNotice->update([
                    'status' => PaymentNoticeStatus::Rejected->value,
                    'reviewed_at' => now(),
                    'reviewed_by_user_id' => $user->id,
                    'review_note' => $note,
                ]);
            }

            $payment = null;

            if ($newStatus === PaymentStatus::Paid) {
                $payment = $this->ledger->recordVerifiedPayment(
                    $locked,
                    $pendingNotice,
                    $user,
                );

                $customer = $this->handlePaid($locked, $user);
                $this->ledger->attachCustomer($payment, $customer?->id);
            }

            return [
                'quotation_id' => $locked->id,
                'payment_id' => $payment?->id,
                'payment_became_paid' => $newStatus === PaymentStatus::Paid,
            ];
        });

        if ($result['payment_became_paid'] && $result['payment_id'] !== null) {
            $payment = Payment::query()->find($result['payment_id']);

            if ($payment !== null) {
                try {
                    $this->receiptService->ensureGenerated($payment);
                } catch (\Throwable $e) {
                    // Payment verification is authoritative and must not be rolled back
                    // because document rendering failed. The receipt is idempotent and can
                    // be generated later by the backfill/repair command.
                    report($e);
                }

                try {
                    $this->electronicInvoice->issueIfEnabled($payment);
                } catch (\Throwable $e) {
                    // E-invoice is an optional provider extension in V1. A vendor outage
                    // must never roll back an already verified customer payment.
                    report($e);
                }
            }

            // Payment confirmation is a user-facing transactional email. Send it
            // immediately after the database commit so production does not depend on a
            // manually started queue worker. Failure is reported without rolling back Paid.
            try {
                SendPaymentConfirmedNotificationJob::dispatchSync($result['quotation_id']);
            } catch (\Throwable $e) {
                report($e);
            }

            $paidQuotation = Quotation::query()->with('assignedStaff.user')->find($result['quotation_id']);
            if ($paidQuotation) {
                $body = __('v1.notification.payment_paid_body', ['code' => $paidQuotation->quotation_code, 'amount' => number_format((float) $paidQuotation->grand_total, 0, ',', '.')]);
                $this->notifications->send($paidQuotation->assignedStaff?->user, __('v1.notification.payment_paid_title'), $body);
                $this->notifications->notifyPermission('customer-care.manage-assignments', __('v1.notification.customer_handover_title'), $body);
            }
        }

        return Quotation::query()
            ->with([
                'customer',
                'opportunity',
                'payment.receipt',
            ])
            ->findOrFail($result['quotation_id']);
    }

    private function validateTransition(
        ?PaymentStatus $current,
        PaymentStatus $target,
    ): void {
        $currentValue = $current?->value ?? PaymentStatus::Unpaid->value;
        $allowed = self::VALID_TRANSITIONS[$currentValue] ?? [];

        if (! in_array($target->value, $allowed, true)) {
            throw ValidationException::withMessages([
                'payment_status' => "Không thể chuyển trạng thái thanh toán từ {$currentValue} sang {$target->value}.",
            ]);
        }
    }

    private function handlePaid(
        Quotation $quotation,
        User $user,
    ): ?Customer {
        if ($quotation->opportunity_id !== null) {
            $opportunity = $quotation->opportunity;

            if ($opportunity === null) {
                throw new \RuntimeException('Opportunity quotation is missing its opportunity.');
            }

            return $this->converter->execute(
                opportunity: $opportunity,
                quotation: $quotation,
                verifiedBy: $user,
            );
        }

        $customer = $quotation->customer;

        if ($customer === null) {
            return null;
        }

        $customer->update([
            'status' => CustomerStatus::Active->value,
            'lifecycle_stage' => CustomerLifecycleStage::Purchasing->value,
            'first_purchase_at' => $customer->first_purchase_at ?? now(),
            'latest_purchase_at' => now(),
            'total_revenue' => (float) $customer->total_revenue
                + (float) $quotation->grand_total,
        ]);

        $this->auditLog->log(
            'customer.lifecycle_updated',
            $customer,
            [],
            [
                'new_status' => CustomerStatus::Active->value,
                'new_lifecycle' => CustomerLifecycleStage::Purchasing->value,
                'source' => 'legacy_quotation_payment',
                'quotation_code' => $quotation->quotation_code,
            ],
        );

        return $customer;
    }
}
