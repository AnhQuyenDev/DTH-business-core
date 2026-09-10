<?php

namespace Dth\Email\Services;

use Dth\Email\Contracts\EmailTransport;
use Dth\Email\Enums\EmailEventType;
use Dth\Email\Enums\EmailMessageStatus;
use Dth\Email\Enums\SendingAccountStatus;
use Dth\Email\Models\EmailEvent;
use Dth\Email\Models\EmailMessage;
use RuntimeException;
use Throwable;

class EmailDispatchService
{
    public function __construct(
        private readonly EmailTransport $transport,
        private readonly SuppressionService $suppressions,
        private readonly EmailEventService $events,
    ) {}
    
    public function send(EmailMessage $message): EmailMessage
    {
        $message->loadMissing('sendingAccount');
        $account = $message->sendingAccount;

        if (! $account) {
            throw new RuntimeException('Email message has no sending account.');
        }

        if ($account->status !== SendingAccountStatus::Active) {
            throw new RuntimeException("Sending account [{$account->id}] is not active.");
        }

        if ($this->suppressions->isSuppressed($message->recipient_email)) {
            $message->update([
                'status' => EmailMessageStatus::Suppressed,
                'failure_reason' => 'Recipient is suppressed.',
            ]);

            $this->event($message, EmailEventType::Suppressed);
            return $message->refresh();
        }

        $message->update(['status' => EmailMessageStatus::Sending]);

        try {
            $providerMessageId = $this->transport->send($message, $account);

            $message->update([
                'status' => EmailMessageStatus::Sent,
                'provider_message_id' => $providerMessageId,
                'sent_at' => now(),
                'failure_reason' => null,
                'failed_at' => null,
            ]);

            $this->event($message, EmailEventType::Sent, [
                'provider_message_id' => $providerMessageId,
            ]);

            return $message->refresh();
        } catch (Throwable $e) {
            $message->update([
                'status' => EmailMessageStatus::Failed,
                'failed_at' => now(),
                'failure_reason' => mb_substr($e->getMessage(), 0, 5000),
            ]);

            $this->event($message, EmailEventType::Failed, [
                'exception' => $e::class,
                'message' => mb_substr($e->getMessage(), 0, 1000),
            ]);

            throw $e;
        }
    }

    public function event(
        EmailMessage $message,
        EmailEventType $type,
        array $payload = [],
    ): EmailEvent {
        return $this->events->record(
            message: $message,
            type: $type,
            payload: $payload,
        );
    }
}
