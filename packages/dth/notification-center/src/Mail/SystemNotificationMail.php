<?php

namespace Dth\NotificationCenter\Mail;

use Dth\NotificationCenter\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\SerializesModels;

class SystemNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Notification $notificationRecord,
        public readonly mixed $recipientUser,
    ) {}

    public function build(): static
    {
        $subject = (string) data_get($this->notificationRecord->metadata ?? [], 'email_subject', $this->notificationRecord->title);

        $mail = $this
            ->subject($subject)
            ->view('dth-notification-center::mail.notification', [
                'notification' => $this->notificationRecord,
                'recipient' => $this->recipientUser,
            ]);

        foreach ((array) ($this->notificationRecord->attachments ?? []) as $attachment) {
            $disk = (string) data_get($attachment, 'disk', config('dth-notification-center.attachments.disk', 'local'));
            $path = (string) data_get($attachment, 'path', '');
            if ($path === '' || ! Storage::disk($disk)->exists($path)) {
                continue;
            }

            $mail->attachFromStorageDisk(
                $disk,
                $path,
                (string) data_get($attachment, 'name', basename($path)),
            );
        }

        return $mail;
    }
}
