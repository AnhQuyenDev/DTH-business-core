<?php

namespace Dth\Email\Services;

use Dth\Email\Contracts\EmailTransport;
use Dth\Email\Models\EmailMessage;
use Dth\Email\Models\SendingAccount;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Illuminate\Support\HtmlString;
class SmtpEmailTransport implements EmailTransport
{
    public function send(EmailMessage $message, SendingAccount $account): ?string
    {
        if ($account->provider !== 'smtp') {
            throw new InvalidArgumentException("Unsupported email provider [{$account->provider}].");
        }

        $settings = $account->encrypted_config ?? [];
        $mailer = 'dth-email-runtime-'.$account->id;

        config()->set("mail.mailers.{$mailer}", [
            'transport' => 'smtp',
            'scheme' => $settings['scheme'] ?? null,
            'url' => null,
            'host' => $settings['host'] ?? '127.0.0.1',
            'port' => (int) ($settings['port'] ?? 587),
            'username' => $settings['username'] ?? null,
            'password' => $settings['password'] ?? null,
            'timeout' => $settings['timeout'] ?? null,
            'local_domain' => $settings['local_domain'] ?? null,
        ]);

        try {
            $content = [
                'html' => new HtmlString(
                    $message->html_body
                        ?: nl2br(
                            e($message->text_body ?? '')
                        )
                ),
            ];

            if (filled($message->text_body)) {
                $content['raw'] = $message->text_body;
            }

            $sent = Mail::mailer($mailer)->send(
                $content,
                [],
                function ($mail) use ($message): void {
                    $mail
                        ->to(
                            $message->recipient_email,
                            $message->recipient_name,
                        )
                        ->from(
                            $message->from_email,
                            $message->from_name,
                        )
                        ->subject(
                            $message->subject,
                        );

                    if ($message->reply_to) {
                        $mail->replyTo(
                            $message->reply_to,
                        );
                    }
                },
            );

            return $sent?->getMessageId();
        } finally {
            Mail::purge($mailer);
        }
    }
}
