<?php

namespace App\Jobs\Sales;

use App\Mail\Sales\PaymentConfirmedMail;
use App\Models\Sales\Quotation;
use App\Services\Sales\QuotationEmailCrmSyncer;
use App\Services\Sales\QuotationInteractionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
    ): void {
        $quotation = Quotation::query()
            ->with([
                'customer',
                'opportunity',
                'company',
                'contact.personalProfile',
                'contact.businessProfile',
            ])
            ->find($this->quotationId);

        if ($quotation === null) {
            return;
        }

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
            Mail::to($recipientEmail)->send(new PaymentConfirmedMail($quotation));

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
