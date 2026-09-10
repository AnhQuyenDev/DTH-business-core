<?php

namespace Dth\Email\Tests\Feature;

use Dth\Email\Contracts\EmailTransport;
use Dth\Email\Models\EmailMessage;
use Dth\Email\Models\SendingAccount;
use Dth\Email\Services\EmailDispatchService;
use Dth\Email\Tests\TestCase;
use Illuminate\Support\Str;
use RuntimeException;

class EmailDispatchAccountStatusTest extends TestCase
{
    public function test_normal_dispatch_rejects_inactive_sending_account(): void
    {
        $account = SendingAccount::query()->create([
            'name' => 'Inactive',
            'provider' => 'smtp',
            'from_email' => 'mail@example.com',
            'encrypted_config' => ['host' => 'smtp.example.com'],
            'status' => 'inactive',
        ]);

        $message = EmailMessage::query()->create([
            'uuid' => (string) Str::uuid(),
            'tracking_token' => (string) Str::uuid(),
            'unsubscribe_token' => (string) Str::uuid(),
            'sending_account_id' => $account->id,
            'from_email' => 'mail@example.com',
            'recipient_email' => 'customer@example.com',
            'subject' => 'Test',
            'status' => 'queued',
        ]);

        $transport = new class implements EmailTransport {
            public function send(EmailMessage $message, SendingAccount $account): ?string
            {
                throw new RuntimeException('Transport must not be called.');
            }
        };

        $this->app->instance(EmailTransport::class, $transport);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is not active');

        app(EmailDispatchService::class)->send($message);
    }
}
