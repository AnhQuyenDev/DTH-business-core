<?php

namespace App\Jobs\Sales;

use App\Mail\Sales\QuotationAcceptedMail;
use App\Models\Sales\Quotation;
use App\Services\Sales\QuotationInteractionService;
use App\Services\Sales\QuotationNotificationMailerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendQuotationAcceptedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Quotation $quotation,
    ) {}

    public function handle(
        QuotationInteractionService $interaction,
        QuotationNotificationMailerService $notificationMailer,
    ): void
    {
        $notifyEmail = config('sales.quotation.accepted_notification_email', 'accounting@company.com');

        if (empty($notifyEmail)) {
            Log::info('SendQuotationAcceptedNotificationJob: no notification email configured', [
                'quotation_id' => $this->quotation->id,
                'code' => $this->quotation->quotation_code,
            ]);

            return;
        }

        try {
            $notificationMailer->send(
                $this->quotation,
                $notifyEmail,
                new QuotationAcceptedMail($this->quotation),
            );

            $interaction->logAcceptedNotificationSent($this->quotation, $notifyEmail);

            Log::info('SendQuotationAcceptedNotificationJob: notification sent', [
                'quotation_code' => $this->quotation->quotation_code,
                'email' => $notifyEmail,
            ]);
        } catch (\Throwable $e) {
            Log::error('SendQuotationAcceptedNotificationJob: failed', [
                'quotation_code' => $this->quotation->quotation_code,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
