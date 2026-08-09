<?php

namespace App\Services\Marketing;

use App\Models\Marketing\SendingAccount;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

final class SendingAccountMailerService
{
    /**
     * Send a mailable through the selected Sending Account.
     *
     * @param  string|array<int, string>  $recipientEmail
     * @param  array<int, string>  $cc
     * @param  array<int, string>  $bcc
     */
    public function send(
        SendingAccount $account,
        string|array $recipientEmail,
        Mailable $mailable,
        array $cc = [],
        array $bcc = [],
    ): void {
        $this->assertUsable($account);

        $recipients = $this->normalizeRecipients($recipientEmail);
        $cc = $this->normalizeRecipients($cc, allowEmpty: true);
        $bcc = $this->normalizeRecipients($bcc, allowEmpty: true);

        $mailable->from(
            $account->from_email,
            $account->from_name ?: config('app.name')
        );

        if (filled($account->reply_to)) {
            $mailable->replyTo($account->reply_to);
        }

        if ($account->provider === 'laravel_mail') {
            $pending = Mail::to($recipients);

            if ($cc !== []) {
                $pending->cc($cc);
            }

            if ($bcc !== []) {
                $pending->bcc($bcc);
            }

            $pending->send($mailable);

            return;
        }

        $mailerName = $this->prepareMailer($account);

        try {
            $pending = Mail::mailer($mailerName)->to($recipients);

            if ($cc !== []) {
                $pending->cc($cc);
            }

            if ($bcc !== []) {
                $pending->bcc($bcc);
            }

            $pending->send($mailable);
        } finally {
            // Queue workers are long-lived. Purging after every send prevents
            // stale credentials/transports from leaking into a later message.
            $this->purgeMailer($mailerName);
        }
    }

    /**
     * Configure and return a dedicated dynamic mailer for an SMTP account.
     * This is public for legacy call sites that still need a mailer name.
     */
    public function prepareMailer(SendingAccount $account): string
    {
        $this->assertUsable($account);

        if ($account->provider !== 'smtp') {
            throw ValidationException::withMessages([
                'sending_account' => 'Chỉ tài khoản SMTP mới có thể tạo mailer động.',
            ]);
        }

        $mailerName = 'sending_account_'.$account->id;

        // MailManager caches resolved mailers. Purge before replacing config so
        // edited credentials are picked up immediately by web and queue workers.
        $this->purgeMailer($mailerName);
        Config::set("mail.mailers.{$mailerName}", $this->smtpConfig($account));

        return $mailerName;
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

        if (filled($account->reply_to) && ! filter_var($account->reply_to, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'sending_account' => 'Reply-To của tài khoản gửi không hợp lệ.',
            ]);
        }

        if ($account->provider === 'laravel_mail') {
            return;
        }

        if ($account->provider !== 'smtp') {
            throw ValidationException::withMessages([
                'sending_account' => 'Nhà cung cấp email của tài khoản gửi chưa được hỗ trợ.',
            ]);
        }

        $config = (array) ($account->config_encrypted ?? []);
        $host = trim((string) ($config['host'] ?? ''));
        $username = trim((string) ($config['username'] ?? ''));
        $password = (string) ($config['password'] ?? '');
        $port = (int) ($config['port'] ?? 0);

        if ($host === '') {
            throw ValidationException::withMessages([
                'sending_account' => 'Tài khoản SMTP chưa cấu hình host.',
            ]);
        }

        if ($port < 0 || $port > 65535) {
            throw ValidationException::withMessages([
                'sending_account' => 'Port SMTP không hợp lệ.',
            ]);
        }

        // Allow unauthenticated SMTP relays, but reject half-configured auth.
        if (($username === '') xor ($password === '')) {
            throw ValidationException::withMessages([
                'sending_account' => 'SMTP username và password phải được cấu hình cùng nhau.',
            ]);
        }
    }

    /**
     * Build Laravel 12 / Symfony Mailer SMTP configuration.
     *
     * Laravel 12 selects TLS mode from `scheme`; the older `encryption` key is
     * therefore normalized here once for every module in the application.
     *
     * @return array<string, mixed>
     */
    private function smtpConfig(SendingAccount $account): array
    {
        $config = (array) ($account->config_encrypted ?? []);
        $encryption = strtolower(trim((string) ($config['encryption'] ?? $config['scheme'] ?? 'tls')));

        $scheme = match ($encryption) {
            'ssl', 'smtps' => 'smtps',
            'none', 'null', '' => 'smtp',
            default => 'smtp', // TLS/STARTTLS is negotiated by Symfony Mailer.
        };

        $defaultPort = $scheme === 'smtps' ? 465 : 587;
        $host = trim((string) ($config['host'] ?? ''));
        $username = trim((string) ($config['username'] ?? ''));
        $password = $config['password'] ?? null;

        return array_filter([
            'transport' => 'smtp',
            'scheme' => $scheme,
            'host' => $host,
            'port' => (int) ($config['port'] ?? $defaultPort),
            'username' => $username !== '' ? $username : null,
            'password' => $password !== '' ? $password : null,
            'timeout' => isset($config['timeout']) ? (int) $config['timeout'] : null,
            'local_domain' => $config['local_domain']
                ?? parse_url((string) config('app.url'), PHP_URL_HOST),
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    private function purgeMailer(string $mailerName): void
    {
        $manager = Mail::getFacadeRoot();

        // Mail::fake() replaces MailManager during tests and does not need a
        // transport purge. Production MailManager exposes purge().
        if (is_object($manager) && method_exists($manager, 'purge')) {
            $manager->purge($mailerName);
        }
    }

    /**
     * @param  string|array<int, string>  $recipients
     * @return array<int, string>
     */
    private function normalizeRecipients(string|array $recipients, bool $allowEmpty = false): array
    {
        $normalized = array_values(array_unique(array_filter(
            array_map(
                static fn ($email): string => trim((string) $email),
                is_array($recipients) ? $recipients : [$recipients]
            ),
            static fn (string $email): bool => $email !== ''
        )));

        if (! $allowEmpty && $normalized === []) {
            throw ValidationException::withMessages([
                'recipient' => 'Email người nhận không được để trống.',
            ]);
        }

        foreach ($normalized as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw ValidationException::withMessages([
                    'recipient' => "Email người nhận không hợp lệ: {$email}",
                ]);
            }
        }

        return $normalized;
    }
}
