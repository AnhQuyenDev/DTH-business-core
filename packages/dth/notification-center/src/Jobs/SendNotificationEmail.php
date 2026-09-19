<?php

namespace Dth\NotificationCenter\Jobs;

use Dth\NotificationCenter\Mail\SystemNotificationMail;
use Dth\NotificationCenter\Models\NotificationRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;
    public int $backoff;

    public function __construct(public readonly int $recipientId)
    {
        $this->tries = max(1, (int) config('dth-notification-center.email.tries', 3));
        $this->backoff = max(1, (int) config('dth-notification-center.email.backoff_seconds', 60));
    }

    public function handle(): void
    {
        $recipient = NotificationRecipient::query()->with(['notification', 'user'])->find($this->recipientId);
        if (! $recipient || $recipient->email_status === 'sent' || ! $recipient->notification || ! $recipient->user?->email) {
            return;
        }

        try {
            $recipient->increment('email_attempts');
            Mail::to($recipient->user->email)->send(new SystemNotificationMail($recipient->notification, $recipient->user));
            $recipient->forceFill([
                'email_status' => 'sent',
                'email_sent_at' => now(),
                'email_failed_at' => null,
                'email_last_error' => null,
            ])->save();
        } catch (Throwable $exception) {
            $recipient->forceFill([
                'email_status' => 'failed',
                'email_failed_at' => now(),
                'email_last_error' => str($exception->getMessage())->limit(1800)->toString(),
            ])->save();

            throw $exception;
        }
    }
}
