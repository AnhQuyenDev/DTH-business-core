<?php

namespace App\Services\Sales;

use App\Enums\Crm\CustomerLifecycleStage;
use App\Enums\Crm\CustomerStatus;
use App\Enums\Sales\PaymentStatus;
use App\Jobs\Sales\SendPaymentConfirmedNotificationJob;
use App\Models\Sales\Quotation;
use App\Models\User;
use App\Services\Marketing\AuditLogService;
use Illuminate\Support\Facades\DB;

class QuotationPaymentService
{
    private const VALID_TRANSITIONS = [
        'not_required' => ['unpaid'],
        'unpaid' => ['pending_verification', 'paid', 'cancelled'],
        'pending_verification' => ['paid', 'unpaid', 'cancelled'],
        'partially_paid' => ['paid', 'pending_verification', 'cancelled'],
        'paid' => ['refunded'],
        'refunded' => [],
        'cancelled' => [],
    ];

    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function updateStatus(Quotation $quotation, PaymentStatus $newStatus, User $user, ?string $note = null): Quotation
    {
        DB::transaction(function () use ($quotation, $newStatus, $user, $note) {
            $oldStatus = $quotation->payment_status;

            $this->validateTransition($oldStatus, $newStatus);

            $quotation->update([
                'payment_status' => $newStatus,
                'updated_by' => $user->id,
            ]);

            $this->auditLog->log('quotation.payment_updated', $quotation, [
                'old_status' => $oldStatus?->value,
            ], [
                'new_status' => $newStatus->value,
                'note' => $note,
            ]);

            if ($newStatus === PaymentStatus::Paid) {
                $this->handlePaid($quotation, $user);
            }
        });

        return $quotation->fresh();
    }

    private function validateTransition(?PaymentStatus $current, PaymentStatus $target): void
    {
        $currentValue = $current?->value ?? 'unpaid';
        $allowed = self::VALID_TRANSITIONS[$currentValue] ?? [];

        if (! in_array($target->value, $allowed, true)) {
            throw new \InvalidArgumentException(
                "Cannot transition payment from {$currentValue} to {$target->value}"
            );
        }
    }

    private function handlePaid(Quotation $quotation, User $user): void
    {
        $customer = $quotation->customer;
        if (! $customer) {
            return;
        }

        $customer->update([
            'status' => CustomerStatus::Active,
            'lifecycle_stage' => CustomerLifecycleStage::Purchasing,
            'first_purchase_at' => $customer->first_purchase_at ?? now(),
            'latest_purchase_at' => now(),
        ]);

        $this->auditLog->log('customer.lifecycle_updated', $customer, [
            'old_status' => $customer->getOriginal('status')?->value,
            'old_lifecycle' => $customer->getOriginal('lifecycle_stage')?->value,
        ], [
            'new_status' => CustomerStatus::Active->value,
            'new_lifecycle' => CustomerLifecycleStage::Purchasing->value,
            'source' => 'quotation_payment',
            'quotation_code' => $quotation->quotation_code,
        ]);

        SendPaymentConfirmedNotificationJob::dispatch($quotation);
    }
}
