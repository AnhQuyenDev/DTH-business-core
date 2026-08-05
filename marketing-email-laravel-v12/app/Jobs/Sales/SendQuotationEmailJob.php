<?php

namespace App\Jobs\Sales;

use App\Enums\Sales\QuotationEmailStatus;
use App\Mail\Sales\QuotationMail;
use App\Models\Sales\QuotationDocument;
use App\Models\Sales\QuotationEmailLog;
use App\Services\Sales\QuotationEmailCrmSyncer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SendQuotationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(
        public QuotationEmailLog $emailLog,
        public ?QuotationDocument $pdfDoc = null,
    ) {}

    public function handle(): void
    {
        if ($this->emailLog->status !== QuotationEmailStatus::Queued) {
            Log::info('SendQuotationEmailJob: skipping already processed email', [
                'email_log_id' => $this->emailLog->id,
                'status' => $this->emailLog->status,
            ]);

            return;
        }

        $this->emailLog->update(['status' => QuotationEmailStatus::Sending]);

        try {
            $pdfPath = $this->pdfDoc ? Storage::disk('local')->path($this->pdfDoc->file_path) : null;

            Mail::to($this->emailLog->recipient_email)
                ->send(new QuotationMail(
                    subjectText: $this->emailLog->subject,
                    body: $this->emailLog->body_snapshot,
                    pdfPath: $pdfPath,
                ));

            $this->emailLog->quotation->update([
                'email_status' => QuotationEmailStatus::Sent->value,
                'sent_at' => now(),
            ]);

            $this->emailLog->update([
                'status' => QuotationEmailStatus::Sent,
                'sent_at' => now(),
            ]);

            $this->syncToCrm();
        } catch (Throwable $e) {
            Log::error('SendQuotationEmailJob: failed', [
                'email_log_id' => $this->emailLog->id,
                'quotation_code' => $this->emailLog->quotation->quotation_code ?? null,
                'error' => $e->getMessage(),
            ]);

            $this->emailLog->update([
                'status' => QuotationEmailStatus::Failed,
                'error_message' => $e->getMessage(),
                'failed_at' => now(),
            ]);

            if ($this->attempts() >= $this->tries) {
                $this->emailLog->quotation->update([
                    'email_status' => QuotationEmailStatus::Failed->value,
                ]);
            }

            throw $e;
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('SendQuotationEmailJob: permanently failed', [
            'email_log_id' => $this->emailLog->id,
            'error' => $e->getMessage(),
        ]);
    }

    /**
     * Push the quotation email into the CRM so it shows up in customer care
     * timeline / email history and the customer's interaction tab.
     */
    private function syncToCrm(): void
    {
        $quotation = $this->emailLog->quotation;

        if (! $quotation->customer) {
            return;
        }

        app(QuotationEmailCrmSyncer::class)->recordEmail(
            $quotation,
            $this->emailLog->subject,
            $this->emailLog->body_snapshot,
            'quotation',
            $this->emailLog->id,
        );
    }
}
