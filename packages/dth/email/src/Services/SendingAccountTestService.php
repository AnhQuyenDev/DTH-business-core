<?php

namespace Dth\Email\Services;

use Dth\Email\Contracts\EmailTransport;
use Dth\Email\Enums\EmailEventType;
use Dth\Email\Enums\EmailMessageStatus;
use Dth\Email\Enums\SendingAccountStatus;
use Dth\Email\Models\EmailEvent;
use Dth\Email\Models\EmailMessage;
use Dth\Email\Models\SendingAccount;
use Illuminate\Support\Str;
use Throwable;

class SendingAccountTestService
{
    public function __construct(private readonly EmailTransport $transport) {}

    public function sendTest(SendingAccount $account, string $recipientEmail, ?string $recipientName = null): EmailMessage
    {
        $recipientEmail = mb_strtolower(trim($recipientEmail));

        if (! filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('A valid test recipient email is required.');
        }

        $message = EmailMessage::query()->create([
            'uuid' => (string) Str::uuid(),
            'tracking_token' => (string) Str::uuid(),
            'unsubscribe_token' => (string) Str::uuid(),
            'sending_account_id' => $account->id,
            'related_type' => 'email.sending-account-test',
            'related_id' => $account->id,
            'from_name' => $account->from_name,
            'from_email' => $account->from_email,
            'reply_to' => $account->reply_to,
            'recipient_email' => $recipientEmail,
            'recipient_name' => $recipientName,
            'subject' => '[DTH Email] SMTP test - '.$account->name,
            'html_body' => '<p>This is a DTH Email Module SMTP configuration test.</p>',
            'text_body' => 'This is a DTH Email Module SMTP configuration test.',
            'status' => EmailMessageStatus::Sending,
            'queued_at' => now(),
            'metadata' => ['test' => true],
        ]);

        try {
            $providerMessageId = $this->transport->send($message, $account);

            $message->update([
                'status' => EmailMessageStatus::Sent,
                'provider_message_id' => $providerMessageId,
                'sent_at' => now(),
                'failure_reason' => null,
                'failed_at' => null,
            ]);

            EmailEvent::query()->create([
                'message_id' => $message->id,
                'event_type' => EmailEventType::Sent,
                'payload' => ['test' => true, 'provider_message_id' => $providerMessageId],
                'occurred_at' => now(),
            ]);

            $account->update([
                'status' => SendingAccountStatus::Active,
                'last_tested_at' => now(),
                'last_test_status' => 'success',
                'last_test_error' => null,
            ]);

            return $message->refresh();
        } catch (Throwable $e) {
            $message->update([
                'status' => EmailMessageStatus::Failed,
                'failed_at' => now(),
                'failure_reason' => mb_substr($e->getMessage(), 0, 5000),
            ]);

            EmailEvent::query()->create([
                'message_id' => $message->id,
                'event_type' => EmailEventType::Failed,
                'payload' => ['test' => true, 'exception' => $e::class, 'message' => mb_substr($e->getMessage(), 0, 1000)],
                'occurred_at' => now(),
            ]);

            $account->update([
                'status' => SendingAccountStatus::Error,
                'last_tested_at' => now(),
                'last_test_status' => 'failed',
                'last_test_error' => mb_substr($e->getMessage(), 0, 5000),
            ]);

            throw $e;
        }
    }
}
