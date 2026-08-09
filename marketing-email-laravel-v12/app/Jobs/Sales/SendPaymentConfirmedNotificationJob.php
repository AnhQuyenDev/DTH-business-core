<?php

namespace App\Jobs\Sales;

use App\Mail\Sales\PaymentConfirmedMail;
use App\Models\Sales\Quotation;
use App\Services\Finance\PaymentReceiptService;
use App\Services\Sales\QuotationEmailCrmSyncer;
use App\Services\Sales\QuotationInteractionService;
use App\Services\Sales\QuotationNotificationMailerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPaymentConfirmedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $quotationId,
    ) {}

    public function handle(
        QuotationInteractionService $interaction,
        QuotationEmailCrmSyncer $syncer,
        QuotationNotificationMailerService $notificationMailer,
        PaymentReceiptService $receiptService,
    ): void {
        $quotation = Quotation::query()
            ->with([
                'customer',
                'opportunity',
                'company',
                'contact.personalProfile',
                'contact.businessProfile',
                'payment.receipt',
            ])
            ->find($this->quotationId);

        if ($quotation === null) {
            return;
        }

        $payment = $quotation->payment;

        if ($payment === null) {
            Log::warning('SendPaymentConfirmedNotificationJob: payment ledger missing', [
                'quotation_id' => $quotation->id,
            ]);

            return;
        }

        // A payment-confirmation email is only useful as an accounting record
        // when the immutable receipt exists. Repair/generate it idempotently
        // before composing the email so the attachment is never silently lost.
        $receiptService->ensureGenerated($payment);
        $payment->load('receipt');

        $recipientEmail = $quotation->customer?->email
            ?? $quotation->party_email;

        if (empty($recipientEmail)) {
            Log::info('SendPaymentConfirmedNotificationJob: no recipient email', [
                'quotation_id' => $quotation->id,
                'code' => $quotation->quotation_code,
            ]);

            return;
        }

        try {
            $notificationMailer->send(
                $quotation,
                $recipientEmail,
                new PaymentConfirmedMail($quotation, $payment),
            );

            $interaction->logPaymentUpdated($quotation, 'Đã gửi xác nhận thanh toán đến khách hàng');

            $syncer->recordEmailEvent(
                $quotation,
                "Xác nhận thanh toán báo giá {$quotation->quotation_code}",
                'payment_confirmed',
            );

            Log::info('SendPaymentConfirmedNotificationJob: notification sent', [
                'quotation_code' => $quotation->quotation_code,
                'email' => $recipientEmail,
            ]);
        } catch (\Throwable $e) {
            Log::error('SendPaymentConfirmedNotificationJob: failed', [
                'quotation_code' => $quotation->quotation_code,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
