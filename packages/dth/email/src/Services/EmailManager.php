<?php

namespace Dth\Email\Services;

use Dth\Email\DTO\OutgoingEmail;
use Dth\Email\Enums\EmailEventType;
use Dth\Email\Enums\EmailMessageStatus;
use Dth\Email\Jobs\SendEmailMessageJob;
use Dth\Email\Models\EmailMessage;
use Dth\Email\Models\SendingAccount;
use Illuminate\Support\Str;

class EmailManager
{
    public function __construct(private readonly EmailDispatchService $dispatch) {}

    public function queue(OutgoingEmail $email): EmailMessage
    {
        $account = SendingAccount::query()->findOrFail($email->sendingAccountId);

        $attributes = $email->idempotencyKey
            ? ['idempotency_key' => $email->idempotencyKey]
            : ['uuid' => (string) Str::uuid()];

        $message = EmailMessage::query()->firstOrCreate($attributes, [
            'uuid' => (string) Str::uuid(),
            'sending_account_id' => $account->id,
            'template_id' => $email->templateId,
            'related_type' => $email->relatedType,
            'related_id' => $email->relatedId,
            'from_name' => $account->from_name,
            'from_email' => $account->from_email,
            'reply_to' => $account->reply_to,
            'recipient_email' => $email->toEmail,
            'recipient_name' => $email->toName,
            'subject' => $email->subject,
            'html_body' => $email->htmlBody,
            'text_body' => $email->textBody,
            'status' => EmailMessageStatus::Queued,
            'tracking_token' => (string) Str::uuid(),
            'unsubscribe_token' => (string) Str::uuid(),
            'queued_at' => now(),
            'metadata' => $email->metadata ?: null,
        ]);

        if ($message->wasRecentlyCreated) {
            $this->dispatch->event($message, EmailEventType::Queued);
            SendEmailMessageJob::dispatch($message->id)->onQueue(config('dth-email.queue', 'emails'));
        }

        return $message;
    }
}
