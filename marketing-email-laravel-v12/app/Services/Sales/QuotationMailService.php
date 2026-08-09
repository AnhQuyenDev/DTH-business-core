<?php

namespace App\Services\Sales;

use App\Enums\Sales\EmailStatus;
use App\Enums\Sales\QuotationEmailStatus;
use App\Jobs\Sales\SendQuotationEmailJob;
use App\Models\Sales\Quotation;
use App\Models\Sales\QuotationDocument;
use App\Models\Sales\QuotationEmailLog;
use App\Models\User;
use App\Services\Marketing\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;
use App\Models\Marketing\Contact;
use Illuminate\Support\Collection;

class QuotationMailService
{
    public function __construct(
        private readonly QuotationPdfService $pdfService,
        private readonly AuditLogService $auditLog,
        private readonly QuotationSendingAccountResolver $sendingAccountResolver,
    ) {}

    public function send(
        Quotation $quotation,
        User $user,
        string $recipientEmail,
        array $options = [],
    ): QuotationEmailLog {
        $this->validateSend($quotation, $recipientEmail);

        $authorizedSigner = $this->resolveAuthorizedSigner(
            $quotation,
            $options['authorized_signer_email'] ?? null,
        );

        $quotation->update([
            'metadata' => array_replace_recursive(
                $quotation->metadata ?? [],
                ['authorized_signer' => $authorizedSigner],
            ),
        ]);
        $quotation->refresh();

        $sendingAccount = $this->sendingAccountResolver->resolve(
            quotation: $quotation,
            actor: $user,
            preferredSendingAccountId: filled($options['sending_account_id'] ?? null)
                ? (int) $options['sending_account_id']
                : null,
        );

        $pdfDoc = $this->ensurePdfExists($quotation);

        $log = DB::transaction(function () use (
            $quotation,
            $user,
            $recipientEmail,
            $options,
            $sendingAccount,
        ): QuotationEmailLog {
            $log = QuotationEmailLog::query()->create([
                'quotation_id' => $quotation->id,
                'sending_account_id' => $sendingAccount->id,
                'sender_email' => $sendingAccount->from_email,
                'sender_name' => $sendingAccount->from_name,
                'recipient_email' => $recipientEmail,
                'cc' => $options['cc'] ?? null,
                'bcc' => $options['bcc'] ?? null,
                'subject' => $options['subject']
                    ?? sprintf('[%s] %s', $quotation->quotation_code, $quotation->title),
                'body_snapshot' => $options['body'] ?? $this->buildEmailBody($quotation),
                'status' => QuotationEmailStatus::Queued,
                'queued_at' => now(),
                'created_by' => $user->id,
            ]);

            $quotation->update([
                'email_status' => EmailStatus::Queued->value,
            ]);

            return $log;
        });

        $this->auditLog->log('quotation.email_queued', $quotation, [], [
            'recipient' => $recipientEmail,
            'sender' => $sendingAccount->from_email,
            'sending_account_id' => $sendingAccount->id,
            'email_log_id' => $log->id,
        ]);

        if (config('business_flow.quotation_email_queue_enabled', false)) {
            SendQuotationEmailJob::dispatch($log, $pdfDoc)
                ->onQueue((string) config(
                    'business_flow.quotation_email_queue',
                    'quotations'
                ));

            return $log->fresh();
        }

        try {
            SendQuotationEmailJob::dispatchSync(
                $log,
                $pdfDoc,
                true,
            );
        } catch (Throwable) {
            // The job already snapshots the error into quotation_email_logs.
            // Returning the log keeps Filament on the same page and lets the
            // user inspect the real failure instead of getting a 500 page.
        }

        return $log->fresh();
    }

    public function resend(
        Quotation $quotation,
        User $user,
        string $recipientEmail,
        array $options = [],
    ): QuotationEmailLog {
        $options['subject'] ??= sprintf(
            '[%s] %s (gửi lại)',
            $quotation->quotation_code,
            $quotation->title
        );
        $options['body'] ??= $this->buildResentEmailBody($quotation);

        return $this->send($quotation, $user, $recipientEmail, $options);
    }

    public function authorizedSignerContacts(
        Quotation $quotation,
    ): Collection {
        $quotation->loadMissing([
            'opportunity.primaryContact',
            'opportunity.contacts',
        ]);

        $opportunity = $quotation->opportunity;

        if (! $opportunity) {
            return collect();
        }

        $contacts = collect();

        if ($opportunity->primaryContact) {
            $contacts->push($opportunity->primaryContact);
        }

        foreach ($opportunity->contacts ?? collect() as $contact) {
            $contacts->push($contact);
        }

        return $contacts
            ->filter(
                fn (Contact $contact): bool =>
                    filled($contact->email)
            )
            ->unique('id')
            ->values();
    }

    private function resolveAuthorizedSigner(
        Quotation $quotation,
        mixed $preferredEmail,
    ): array {
        $email = mb_strtolower(trim((string) (
            $preferredEmail
            ?: $quotation->authorized_signer_email
            ?: $quotation->party_email
        )));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'authorized_signer_email' => 'Phải chọn email người được phép xác nhận báo giá.',
            ]);
        }

        $contacts = $this->authorizedSignerContacts($quotation);
        $contact = $contacts->first(
            fn (Contact $candidate): bool => mb_strtolower(
                trim((string) $candidate->email)
            ) === $email
        );

        // If the Opportunity has known contacts, a newly selected signer must
        // come from that CRM contact set. This prevents free-form impersonation.
        if (
            filled($preferredEmail)
            && $contacts->isNotEmpty()
            && $contact === null
        ) {
            throw ValidationException::withMessages([
                'authorized_signer_email' => 'Người xác nhận phải là một liên hệ đã có trong Cơ hội kinh doanh.',
            ]);
        }

        return [
            'contact_id' => $contact?->id ?? $quotation->contact_id,
            'name' => $contact?->full_name
                ?? $quotation->authorized_signer_name
                ?? $quotation->party_contact_name,
            'email' => $email,
            'phone' => $contact?->phone ?? $quotation->party_phone,
        ];
    }

    private function validateSend(
        Quotation $quotation,
        string $recipientEmail,
    ): void {
        if (! $quotation->status->canSend()) {
            throw ValidationException::withMessages([
                'recipient_email' => 'Báo giá chưa ở trạng thái cho phép gửi.',
            ]);
        }

        if (! filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'recipient_email' => 'Email người nhận không hợp lệ.',
            ]);
        }

        if (
            $quotation->bank_account_id === null
            && blank(data_get($quotation->payment_snapshot, 'bank_account_id'))
        ) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'Báo giá phải có tài khoản ngân hàng trước khi gửi cho khách.',
            ]);
        }
    }

    private function ensurePdfExists(
        Quotation $quotation,
    ): ?QuotationDocument {
        $doc = $this->pdfService->getLatestPdf($quotation);

        if (! $doc) {
            return $this->pdfService->generate($quotation);
        }

        return $doc;
    }

    private function buildResentEmailBody(
        Quotation $quotation,
    ): string {
        $url = route('sales.quotation.public.show', [
            'quotationCode' => $quotation->quotation_code,
            'token' => $quotation->public_token,
        ]);

        return view('sales.emails.quotation-resent', [
            'quotation' => $quotation,
            'publicUrl' => $url,
        ])->render();
    }

    private function buildEmailBody(
        Quotation $quotation,
    ): string {
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
