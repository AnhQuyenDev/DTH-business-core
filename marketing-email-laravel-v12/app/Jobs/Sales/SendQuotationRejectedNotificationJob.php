<?php

namespace App\Jobs\Sales;

use App\Mail\Sales\QuotationRejectedMail;
use App\Models\Sales\Quotation;
use App\Services\Sales\QuotationInteractionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendQuotationRejectedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Quotation $quotation,
        public string $reason = '',
    ) {}

    public function handle(QuotationInteractionService $interaction): void
    {
        $notifyEmail = config('sales.quotation.rejected_notification_email', 'sales@company.com');

        if (empty($notifyEmail)) {
            Log::info('SendQuotationRejectedNotificationJob: no notification email configured', [
                'quotation_id' => $this->quotation->id,
                'code' => $this->quotation->quotation_code,
            ]);
            return;
        }

        try {
            Mail::to($notifyEmail)->send(new QuotationRejectedMail(
                $this->quotation,
                $this->reason,
            ));

            $interaction->logRejected($this->quotation, $this->reason);

            Log::info('SendQuotationRejectedNotificationJob: notification sent', [
                'quotation_code' => $this->quotation->quotation_code,
                'email' => $notifyEmail,
            ]);
        } catch (\Throwable $e) {
            Log::error('SendQuotationRejectedNotificationJob: failed', [
                'quotation_code' => $this->quotation->quotation_code,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
