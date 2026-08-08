<?php

namespace App\Services\Marketing;

use App\Models\Marketing\SendingAccount;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

final class SendingAccountMailerService
{
    public function send(
        SendingAccount $account,
        string $recipientEmail,
        Mailable $mailable,
    ): void {
        $this->assertUsable($account);

        $mailable->from(
            $account->from_email,
            $account->from_name ?: config('app.name')
        );

        if (filled($account->reply_to)) {
            $mailable->replyTo($account->reply_to);
        }

        if ($account->provider === 'laravel_mail') {
            Mail::to($recipientEmail)->send($mailable);

            return;
        }

        if ($account->provider !== 'smtp') {
            throw ValidationException::withMessages([
                'sending_account' => 'Nhà cung cấp email của tài khoản gửi chưa được hỗ trợ.',
            ]);
        }

        $mailerName = 'sending_account_'.$account->id;
        Config::set(
            "mail.mailers.{$mailerName}",
            $this->smtpConfig($account)
        );

        // MailManager caches mailer instances. Purge to make sure the latest
        // encrypted credentials from the selected Sending Account are used.
        Mail::purge($mailerName);

        Mail::mailer($mailerName)
            ->to($recipientEmail)
            ->send($mailable);
    }

    public function assertUsable(SendingAccount $account): void
    {
        if ((string) $account->status !== 'active') {
            throw ValidationException::withMessages([
                'sending_account' => 'Tài khoản gửi email đang không ở trạng thái Hoạt động.',
            ]);
        }

        if (! filter_var($account->from_email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'sending_account' => 'Email người gửi của tài khoản gửi không hợp lệ.',
            ]);
        }

        if ($account->provider !== 'smtp') {
            return;
        }

        $config = (array) ($account->config_encrypted ?? []);

        if (blank($config['host'] ?? null)) {
            throw ValidationException::withMessages([
                'sending_account' => 'Tài khoản SMTP chưa cấu hình host.',
            ]);
        }
    }

    private function smtpConfig(SendingAccount $account): array
    {
        $config = (array) ($account->config_encrypted ?? []);
        $encryption = strtolower((string) ($config['encryption'] ?? $config['scheme'] ?? 'tls'));

        $scheme = match ($encryption) {
            'ssl', 'smtps' => 'smtps',
            'none', 'null', '' => null,
            default => 'smtp', // STARTTLS is negotiated automatically when available.
        };

        return array_filter([
            'transport' => 'smtp',
            'scheme' => $scheme,
            'host' => $config['host'] ?? null,
            'port' => (int) ($config['port'] ?? ($scheme === 'smtps' ? 465 : 587)),
            'username' => $config['username'] ?? null,
            'password' => $config['password'] ?? null,
            'timeout' => isset($config['timeout']) ? (int) $config['timeout'] : null,
            'local_domain' => $config['local_domain']
                ?? parse_url((string) config('app.url'), PHP_URL_HOST),
        ], static fn ($value): bool => $value !== null && $value !== '');
    }
}
