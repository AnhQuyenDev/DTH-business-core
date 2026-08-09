<?php

namespace App\Jobs\Sales;

use App\Mail\Sales\QuotationExpiredMail;
use App\Models\Sales\Quotation;
use App\Services\Sales\QuotationNotificationMailerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendQuotationExpiredNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Quotation $quotation,
    ) {}

    public function handle(QuotationNotificationMailerService $notificationMailer): void
    {
        $notifyEmail = config('sales.quotation.expired_notification_email', 'sales@company.com');

        if (empty($notifyEmail)) {
            Log::info('SendQuotationExpiredNotificationJob: no notification email configured', [
                'quotation_id' => $this->quotation->id,
                'code' => $this->quotation->quotation_code,
            ]);

            return;
        }

        try {
            $notificationMailer->send(
                $this->quotation,
                $notifyEmail,
                new QuotationExpiredMail($this->quotation),
            );

            Log::info('SendQuotationExpiredNotificationJob: notification sent', [
                'quotation_code' => $this->quotation->quotation_code,
                'email' => $notifyEmail,
            ]);
        } catch (\Throwable $e) {
            Log::error('SendQuotationExpiredNotificationJob: failed', [
                'quotation_code' => $this->quotation->quotation_code,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
