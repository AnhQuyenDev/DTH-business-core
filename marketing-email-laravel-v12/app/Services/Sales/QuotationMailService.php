<?php

namespace App\Services\Sales;

use App\Enums\Sales\QuotationEmailStatus;
use App\Enums\Sales\QuotationStatus;
use App\Jobs\Sales\SendQuotationEmailJob;
use App\Models\Marketing\Contact;
use App\Models\Sales\Quotation;
use App\Models\Sales\QuotationEmailLog;
use App\Models\User;
use App\Services\Marketing\AuditLogService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QuotationMailService
{
    public function __construct(
        private readonly QuotationPdfService $pdfService,
        private readonly QuotationSendingAccountService $sendingAccounts,
        private readonly AuditLogService $auditLog,
    ) {}

    public function send(
        Quotation $quotation,
        User $user,
        string $recipientEmail,
        array $options = [],
    ): QuotationEmailLog {
        $quotation->loadMissing([
            'opportunity.primaryContact.personalProfile',
            'opportunity.primaryContact.businessProfile',
            'opportunity.contacts.personalProfile',
            'opportunity.contacts.businessProfile',
            'bankAccount',
        ]);

        $recipientEmail = $this->normalizeEmail($recipientEmail);
        $authorizedSignerEmail = $this->normalizeEmail(
            (string) ($options['authorized_signer_email'] ?? $recipientEmail)
        );

        $this->validateSend($quotation, $user, $recipientEmail);

        // Fail on the form before queueing if the Sales department has no
        // usable SMTP account. Otherwise the UI would say "queued" and only
        // the background worker would discover the missing configuration.
        $this->sendingAccounts->resolve($quotation);

        $authorizedSigner = $this->resolveAuthorizedSigner(
            $quotation,
            $authorizedSignerEmail,
        );

        $metadata = $quotation->metadata ?? [];
        $metadata['authorized_signer'] = [
            'contact_id' => $authorizedSigner?->id,
            'name' => $authorizedSigner?->full_name
                ?? $quotation->party_contact_name,
            'email' => $authorizedSignerEmail,
            'selected_by_user_id' => $user->id,
            'selected_at' => now()->toIso8601String(),
        ];

        $quotation->update([
            'metadata' => $metadata,
            'updated_by' => $user->id,
        ]);

        // The PDF sent to the customer is an official snapshot of the approved
        // quotation. Always regenerate it immediately before queueing the email.
        $pdfDoc = $this->pdfService->regenerate($quotation->fresh());

        $log = QuotationEmailLog::query()->create([
            'quotation_id' => $quotation->id,
            'recipient_email' => $recipientEmail,
            'cc' => $options['cc'] ?? null,
            'bcc' => $options['bcc'] ?? null,
            'subject' => trim((string) ($options['subject']
                ?? sprintf('[%s] %s', $quotation->quotation_code, $quotation->title))),
            'body_snapshot' => filled($options['body'] ?? null)
                ? (string) $options['body']
                : $this->buildEmailBody($quotation),
            'status' => QuotationEmailStatus::Queued,
            'queued_at' => now(),
            'created_by' => $user->id,
        ]);

        SendQuotationEmailJob::dispatch($log, $pdfDoc)
            ->onQueue('quotations');

        $quotation->update([
            'email_status' => QuotationEmailStatus::Queued->value,
        ]);

        $this->auditLog->log('quotation.email_queued', $quotation, [], [
            'recipient' => $recipientEmail,
            'authorized_signer_email' => $authorizedSignerEmail,
            'email_log_id' => $log->id,
            'pdf_document_id' => $pdfDoc->id,
        ]);

        return $log;
    }

    /**
     * @return Collection<int, Contact>
     */
    public function authorizedSignerContacts(Quotation $quotation): Collection
    {
        $quotation->loadMissing([
            'opportunity.primaryContact.personalProfile',
            'opportunity.primaryContact.businessProfile',
            'opportunity.contacts.personalProfile',
            'opportunity.contacts.businessProfile',
        ]);

        if ($quotation->opportunity === null) {
            return collect();
        }

        return collect([$quotation->opportunity->primaryContact])
            ->merge($quotation->opportunity->contacts)
            ->filter(fn (?Contact $contact): bool => $contact !== null && filled($contact->email))
            ->unique('id')
            ->values();
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
            $quotation->title,
        );
        $options['body'] ??= $this->buildResentEmailBody($quotation);

        return $this->send($quotation, $user, $recipientEmail, $options);
    }

    private function validateSend(
        Quotation $quotation,
        User $user,
        string $recipientEmail,
    ): void {
        $errors = [];

        if (! $user->can('send', $quotation)) {
            $errors['quotation'] = 'Bạn không có quyền gửi báo giá này.';
        }

        if ($quotation->status !== QuotationStatus::Approved) {
            $errors['quotation'] = 'Chỉ báo giá đã được phê duyệt mới được gửi cho khách hàng.';
        }

        if (! $quotation->isCurrentVersion()) {
            $errors['quotation'] = 'Không thể gửi một phiên bản báo giá đã bị thay thế.';
        }

        if ($quotation->valid_until?->isPast()) {
            $errors['valid_until'] = 'Báo giá đã hết hiệu lực. Hãy tạo phiên bản mới trước khi gửi.';
        }

        $partyEmail = $this->normalizeEmail((string) $quotation->party_email);
        if ($partyEmail === '' || ! filter_var($partyEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['recipient_email'] = 'Báo giá chưa có email liên hệ hợp lệ.';
        } elseif ($recipientEmail !== $partyEmail) {
            $errors['recipient_email'] = 'Email nhận báo giá phải đúng email liên hệ đã được chốt trên báo giá.';
        }

        if ($quotation->bank_account_id === null || $quotation->bankAccount === null) {
            $errors['bank_account_id'] = 'Hãy chọn tài khoản ngân hàng trước khi gửi báo giá.';
        } elseif ($quotation->bankAccount->status !== 'active') {
            $errors['bank_account_id'] = 'Tài khoản ngân hàng của báo giá không còn hoạt động.';
        }

        foreach (['bank_code', 'account_number', 'account_name', 'transfer_content'] as $key) {
            if (blank(data_get($quotation->payment_snapshot, $key))) {
                $errors['payment_snapshot'] = 'Thông tin thanh toán của báo giá chưa đầy đủ. Hãy lưu lại báo giá trước khi gửi.';
                break;
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function resolveAuthorizedSigner(
        Quotation $quotation,
        string $email,
    ): ?Contact {
        $contacts = $this->authorizedSignerContacts($quotation);

        if ($contacts->isEmpty()) {
            if ($email === $this->normalizeEmail((string) $quotation->party_email)) {
                return null;
            }

            throw ValidationException::withMessages([
                'authorized_signer_email' => 'Email người xác nhận không thuộc liên hệ của cơ hội.',
            ]);
        }

        $contact = $contacts->first(
            fn (Contact $contact): bool => $this->normalizeEmail((string) $contact->email) === $email
        );

        if ($contact === null) {
            throw ValidationException::withMessages([
                'authorized_signer_email' => 'Chỉ được chọn người xác nhận thuộc danh sách liên hệ của cơ hội.',
            ]);
        }

        return $contact;
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
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
