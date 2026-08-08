<?php

namespace App\Services\Sales;

use App\Actions\Sales\ConvertWonOpportunityToCustomerAction;
use App\Enums\Crm\CustomerLifecycleStage;
use App\Enums\Crm\CustomerStatus;
use App\Enums\Sales\PaymentNoticeStatus;
use App\Enums\Sales\PaymentStatus;
use App\Enums\Sales\QuotationStatus;
use App\Jobs\Sales\SendPaymentConfirmedNotificationJob;
use App\Models\Sales\Quotation;
use App\Models\User;
use App\Services\Marketing\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class QuotationPaymentService
{
    private const VALID_TRANSITIONS = [
        'not_required' => ['unpaid'],
        'unpaid' => [
            'pending_verification',
            'paid',
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
                ])
                ->whereKey($quotation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldStatus = $locked->payment_status;

            /*
             * Idempotency: nếu đã paid thì trả về,
             * không cộng doanh thu hoặc tạo Customer lần nữa.
             */
            if (
                $newStatus === PaymentStatus::Paid
                && $oldStatus === PaymentStatus::Paid
            ) {
                return [
                    'quotation_id' => $locked->id,
                    'payment_became_paid' => false,
                ];
            }

            $this->validateTransition(
                $oldStatus,
                $newStatus,
            );

            $locked->update([
                'payment_status' => $newStatus->value,
                'paid_at' => $newStatus === PaymentStatus::Paid
                    ? ($locked->paid_at ?? now())
                    : $locked->paid_at,
                'payment_verified_by_user_id' => $newStatus === PaymentStatus::Paid
                        ? $user->id
                        : $locked->payment_verified_by_user_id,
                'payment_note' => $note
                    ?? $locked->payment_note,
                'updated_by' => $user->id,
            ]);

            $this->auditLog->log(
                'quotation.payment_updated',
                $locked,
                [
                    'old_status' => $oldStatus?->value,
                ],
                [
                    'new_status' => $newStatus->value,
                    'note' => $note,
                    'verified_by_user_id' => $user->id,
                ],
            );

            $pendingNotice = $locked->paymentNotices()
                ->where('status', PaymentNoticeStatus::Pending->value)
                ->latest('id')
                ->first();

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

            if ($newStatus === PaymentStatus::Paid) {
                $this->handlePaid($locked, $user);
            }

            return [
                'quotation_id' => $locked->id,
                'payment_became_paid' => $newStatus === PaymentStatus::Paid,
            ];
        });

        if ($result['payment_became_paid']) {
            SendPaymentConfirmedNotificationJob::dispatch(
                $result['quotation_id']
            );
        }

        return Quotation::query()
            ->with([
                'customer',
                'opportunity',
            ])
            ->findOrFail($result['quotation_id']);
    }

    private function validateTransition(
        ?PaymentStatus $current,
        PaymentStatus $target,
    ): void {
        $currentValue = $current?->value
            ?? PaymentStatus::Unpaid->value;

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
    ): void {
        if ($quotation->opportunity_id !== null) {
            $opportunity = $quotation->opportunity;

            if ($opportunity === null) {
                throw new \RuntimeException(
                    'Opportunity quotation is missing its opportunity.'
                );
            }

            $this->converter->execute(
                opportunity: $opportunity,
                quotation: $quotation,
                verifiedBy: $user,
            );

            return;
        }

        /*
         * Luồng legacy: Quotation cũ đã có Customer.
         */
        $customer = $quotation->customer;

        if ($customer === null) {
            return;
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
    }
}
