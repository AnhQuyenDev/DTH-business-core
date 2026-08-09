<?php

namespace App\Jobs\Sales;

use App\Mail\Sales\QuotationExpiringMail;
use App\Models\Sales\Quotation;
use App\Services\Sales\QuotationNotificationMailerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendQuotationExpiringNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Quotation $quotation,
    ) {}

    public function handle(QuotationNotificationMailerService $notificationMailer): void
    {
        $notifyEmail = config('sales.quotation.expiring_notification_email', 'sales@company.com');

        if (empty($notifyEmail)) {
            Log::info('SendQuotationExpiringNotificationJob: no notification email configured', [
                'quotation_id' => $this->quotation->id,
                'code' => $this->quotation->quotation_code,
            ]);

            return;
        }

        try {
            $notificationMailer->send(
                $this->quotation,
                $notifyEmail,
                new QuotationExpiringMail($this->quotation),
            );

            Log::info('SendQuotationExpiringNotificationJob: notification sent', [
                'quotation_code' => $this->quotation->quotation_code,
                'email' => $notifyEmail,
            ]);
        } catch (\Throwable $e) {
            Log::error('SendQuotationExpiringNotificationJob: failed', [
                'quotation_code' => $this->quotation->quotation_code,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
