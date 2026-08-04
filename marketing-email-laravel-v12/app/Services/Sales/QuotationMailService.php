<?php

namespace App\Services\Sales;

use App\Enums\Sales\QuotationEmailStatus;
use App\Jobs\Sales\SendQuotationEmailJob;
use App\Models\Sales\Quotation;
use App\Models\Sales\QuotationEmailLog;
use App\Models\User;
use App\Services\Marketing\AuditLogService;

class QuotationMailService
{
    public function __construct(
        private readonly QuotationPdfService $pdfService,
        private readonly AuditLogService $auditLog,
    ) {}

    public function send(Quotation $quotation, User $user, string $recipientEmail, array $options = []): QuotationEmailLog
    {
        $this->validateSend($quotation, $recipientEmail);

        $log = QuotationEmailLog::query()->create([
            'quotation_id' => $quotation->id,
            'recipient_email' => $recipientEmail,
            'cc' => $options['cc'] ?? null,
            'bcc' => $options['bcc'] ?? null,
            'subject' => $options['subject'] ?? sprintf('[%s] %s', $quotation->quotation_code, $quotation->title),
            'body_snapshot' => $options['body'] ?? $this->buildEmailBody($quotation),
            'status' => QuotationEmailStatus::Queued,
            'queued_at' => now(),
            'created_by' => $user->id,
        ]);

        $pdfDoc = $this->ensurePdfExists($quotation);

        SendQuotationEmailJob::dispatch($log, $pdfDoc)
            ->onQueue('quotations');

        $quotation->update([
            'email_status' => QuotationEmailStatus::Queued->value,
        ]);

        $this->auditLog->log('quotation.email_queued', $quotation, [], [
            'recipient' => $recipientEmail,
            'email_log_id' => $log->id,
        ]);

        return $log;
    }

    public function resend(Quotation $quotation, User $user, string $recipientEmail, array $options = []): QuotationEmailLog
    {
        $options['subject'] ??= sprintf('[%s] %s (gửi lại)', $quotation->quotation_code, $quotation->title);
        $options['body'] ??= $this->buildResentEmailBody($quotation);
        return $this->send($quotation, $user, $recipientEmail, $options);
    }

    private function validateSend(Quotation $quotation, string $recipientEmail): void
    {
        if (!$quotation->status->canSend()) {
            throw new \InvalidArgumentException('Quotation cannot be sent in its current status.');
        }

        if (!filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid recipient email address.');
        }
    }

    private function ensurePdfExists(Quotation $quotation): ?\App\Models\Sales\QuotationDocument
    {
        $doc = $this->pdfService->getLatestPdf($quotation);
        if (!$doc) {
            return $this->pdfService->generate($quotation);
        }
        return $doc;
    }

    private function buildResentEmailBody(Quotation $quotation): string
    {
        $url = route('sales.quotation.public.show', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]);

        return view('sales.emails.quotation-resent', [
            'quotation' => $quotation,
            'publicUrl' => $url,
        ])->render();
    }

    private function buildEmailBody(Quotation $quotation): string
    {
        $url = route('sales.quotation.public.show', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]);

        return view('sales.emails.quotation-sent', [
            'quotation' => $quotation,
            'publicUrl' => $url,
        ])->render();
    }
}
