<?php

namespace App\Jobs\Sales;

use App\Enums\Sales\EmailStatus;
use App\Enums\Sales\QuotationEmailStatus;
use App\Enums\Sales\QuotationStatus;
use App\Mail\Sales\QuotationMail;
use App\Models\Sales\QuotationDocument;
use App\Models\Sales\QuotationEmailLog;
use App\Services\Marketing\SendingAccountMailerService;
use App\Services\Sales\QuotationEmailCrmSyncer;
use App\Services\Sales\QuotationOpportunitySyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class SendQuotationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(
        public QuotationEmailLog $emailLog,
        public ?QuotationDocument $pdfDoc = null,
        public bool $failImmediately = false,
    ) {}

    public function handle(
        SendingAccountMailerService $mailer,
    ): void {
        $this->emailLog->refresh();

        if (in_array(
            $this->emailLog->status,
            [QuotationEmailStatus::Sent, QuotationEmailStatus::Cancelled],
            true,
        )) {
            Log::info('SendQuotationEmailJob: skipping already processed email', [
                'email_log_id' => $this->emailLog->id,
                'status' => $this->emailLog->status->value,
            ]);

            return;
        }

        $sendingAccount = $this->emailLog->sendingAccount;

        if (! $sendingAccount) {
            throw new RuntimeException(
                'Sending account snapshot is missing for quotation email log #'.$this->emailLog->id
            );
        }

        $this->emailLog->update([
            'status' => QuotationEmailStatus::Sending,
        ]);

        try {
            $pdfPath = $this->pdfDoc
                ? Storage::disk('local')->path($this->pdfDoc->file_path)
                : null;

            $mailer->send(
                account: $sendingAccount,
                recipientEmail: $this->emailLog->recipient_email,
                cc: (array) ($this->emailLog->cc ?? []),
                bcc: (array) ($this->emailLog->bcc ?? []),
                mailable: new QuotationMail(
                    subjectText: $this->emailLog->subject,
                    body: $this->emailLog->body_snapshot,
                    pdfPath: $pdfPath,
                ),
            );

            $now = now();
            $quotation = $this->emailLog->quotation->fresh();

            $quotationUpdates = [
                'email_status' => EmailStatus::Sent->value,
                'sent_at' => $now,
            ];

            if ($quotation->status === QuotationStatus::Approved) {
                $quotationUpdates['status'] = QuotationStatus::Sent->value;
            }

            $quotation->update($quotationUpdates);

            $this->emailLog->update([
                'status' => QuotationEmailStatus::Sent,
                'sent_at' => $now,
                'failed_at' => null,
                'error_message' => null,
            ]);

            $this->syncToCrm();

            app(QuotationOpportunitySyncService::class)
                ->onSent($quotation->fresh('opportunity'));
        } catch (Throwable $e) {
            $isFinalFailure = $this->failImmediately
                || $this->attempts() >= $this->tries;

            Log::error('SendQuotationEmailJob: failed', [
                'email_log_id' => $this->emailLog->id,
                'quotation_code' => $this->emailLog->quotation->quotation_code ?? null,
                'sending_account_id' => $this->emailLog->sending_account_id,
                'sender_email' => $this->emailLog->sender_email,
                'recipient_email' => $this->emailLog->recipient_email,
                'attempt' => $this->attempts(),
                'final' => $isFinalFailure,
                'error' => $e->getMessage(),
            ]);

            $this->emailLog->update([
                'status' => $isFinalFailure
                    ? QuotationEmailStatus::Failed
                    : QuotationEmailStatus::Queued,
                'error_message' => $e->getMessage(),
                'failed_at' => $isFinalFailure ? now() : null,
            ]);

            if ($isFinalFailure) {
                $this->emailLog->quotation->update([
                    'email_status' => EmailStatus::Failed->value,
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

        $this->emailLog->refresh();

        if ($this->emailLog->status !== QuotationEmailStatus::Sent) {
            $this->emailLog->update([
                'status' => QuotationEmailStatus::Failed,
                'error_message' => $e->getMessage(),
                'failed_at' => now(),
            ]);

            $this->emailLog->quotation?->update([
                'email_status' => EmailStatus::Failed->value,
            ]);
        }
    }

    private function syncToCrm(): void
    {
        $quotation = $this->emailLog->quotation->fresh([
            'customer',
            'opportunity',
            'contact',
        ]);

        app(QuotationEmailCrmSyncer::class)->recordEmail(
            $quotation,
            $this->emailLog->subject,
            $this->emailLog->body_snapshot,
            'quotation',
            $this->emailLog->id,
        );
    }
}
