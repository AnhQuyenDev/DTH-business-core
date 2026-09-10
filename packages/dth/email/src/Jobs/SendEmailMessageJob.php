<?php

namespace Dth\Email\Jobs;

use Dth\Email\Enums\EmailMessageStatus;
use Dth\Email\Models\EmailMessage;
use Dth\Email\Services\EmailDispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendEmailMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $messageId) {}

    public function handle(EmailDispatchService $dispatch): void
    {
        $message = EmailMessage::query()->findOrFail($this->messageId);

        if (in_array($message->status, [EmailMessageStatus::Sent, EmailMessageStatus::Delivered, EmailMessageStatus::Suppressed], true)) {
            return;
        }

        $dispatch->send($message);
    }
}
