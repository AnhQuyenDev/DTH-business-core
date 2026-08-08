<?php

namespace App\Jobs\Sales;

use App\Enums\Sales\QuotationEmailStatus;
use App\Enums\Sales\QuotationStatus;
use App\Mail\Sales\QuotationMail;
use App\Models\Sales\QuotationDocument;
use App\Models\Sales\QuotationEmailLog;
use App\Services\Sales\QuotationEmailCrmSyncer;
use App\Services\Sales\QuotationInteractionService;
use App\Services\Sales\QuotationOpportunitySyncService;
use App\Services\Sales\QuotationSendingAccountService;
use App\Services\Sales\QuotationStateMachine;
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

    // Email delivery is not safely idempotent at SMTP level. Do not auto-retry
    // a commercial quotation because a retry after an uncertain SMTP response
    // can send duplicate quotations. The user can retry manually while the
    // quotation remains Approved.
    public int $tries = 1;

    public function __construct(
        public QuotationEmailLog $emailLog,
        public ?QuotationDocument $pdfDoc = null,
    ) {}

    public function handle(
        QuotationSendingAccountService $sendingAccounts,
        QuotationStateMachine $stateMachine,
        QuotationInteractionService $interactions,
    ): void {
        $this->emailLog->refresh();

        if ($this->emailLog->status !== QuotationEmailStatus::Queued) {
            Log::info('SendQuotationEmailJob: skipping already processed email', [
                'email_log_id' => $this->emailLog->id,
                'status' => $this->emailLog->status,
            ]);

            return;
        }

        $quotation = $this->emailLog->quotation()->with([
            'assignedStaff.department',
            'opportunity',
        ])->firstOrFail();

        // A queued email must never escape after somebody cancelled or revised
        // the commercial document while the queue was waiting.
        if ($quotation->status !== QuotationStatus::Approved) {
            $this->emailLog->update([
                'status' => QuotationEmailStatus::Failed,
                'error_message' => 'Báo giá không còn ở trạng thái Đã duyệt tại thời điểm gửi.',
                'failed_at' => now(),
            ]);

            return;
        }

        $this->emailLog->update(['status' => QuotationEmailStatus::Sending]);

        try {
            $sendingAccount = $sendingAccounts->resolve($quotation);
            $mailer = $sendingAccounts->configureMailer($sendingAccount);
            $pdfPath = $this->pdfDoc
                ? Storage::disk('local')->path($this->pdfDoc->file_path)
                : null;

            Mail::mailer($mailer)
                ->to($this->emailLog->recipient_email)
                ->send(new QuotationMail(
                    subjectText: $this->emailLog->subject,
                    body: $this->emailLog->body_snapshot,
                    pdfPath: $pdfPath,
                ));

            $stateMachine->validateTransition(
                $quotation->status,
                QuotationStatus::Sent,
            );

            $quotation->update([
                'status' => QuotationStatus::Sent->value,
                'email_status' => QuotationEmailStatus::Sent->value,
                'sent_at' => $quotation->sent_at ?? now(),
                'updated_by' => $this->emailLog->created_by,
            ]);

            $this->emailLog->update([
                'status' => QuotationEmailStatus::Sent,
                'sent_at' => now(),
            ]);

            $fresh = $quotation->fresh('opportunity');
            $interactions->logSent($fresh, $this->emailLog->recipient_email);

            $this->syncToCrm();
            app(QuotationOpportunitySyncService::class)->onSent($fresh);
        } catch (Throwable $e) {
            Log::error('SendQuotationEmailJob: failed', [
                'email_log_id' => $this->emailLog->id,
                'quotation_code' => $quotation->quotation_code,
                'error' => $e->getMessage(),
            ]);

            $this->emailLog->update([
                'status' => QuotationEmailStatus::Failed,
                'error_message' => $e->getMessage(),
                'failed_at' => now(),
            ]);

            if ($this->attempts() >= $this->tries) {
                $quotation->update([
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
