<?php

namespace App\Jobs\Sales;

use App\Models\Sales\Quotation;
use App\Services\Sales\QuotationInteractionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\Sales\QuotationRevisionRequestedMail;

class SendQuotationRevisionRequestedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Quotation $quotation,
        public string $reason,
    ) {}

    public function handle(QuotationInteractionService $interaction): void
    {
        $notifyEmail = config('sales.quotation.revision_notification_email', 'sales@company.com');

        if (empty($notifyEmail)) {
            Log::info('SendQuotationRevisionRequestedNotificationJob: no notification email configured', [
                'quotation_id' => $this->quotation->id,
                'code' => $this->quotation->quotation_code,
            ]);
            return;
        }

        try {
            Mail::to($notifyEmail)->send(new QuotationRevisionRequestedMail(
                $this->quotation,
                $this->reason,
            ));

            $interaction->logRevisionRequested($this->quotation, $this->reason);

            Log::info('SendQuotationRevisionRequestedNotificationJob: notification sent', [
                'quotation_code' => $this->quotation->quotation_code,
                'email' => $notifyEmail,
            ]);
        } catch (\Throwable $e) {
            Log::error('SendQuotationRevisionRequestedNotificationJob: failed', [
                'quotation_code' => $this->quotation->quotation_code,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
