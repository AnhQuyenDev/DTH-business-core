<?php

namespace Dth\Email\Tests\Feature;

use Dth\Email\Contracts\EmailTransport;
use Dth\Email\Enums\EmailMessageStatus;
use Dth\Email\Enums\SendingAccountStatus;
use Dth\Email\Models\EmailMessage;
use Dth\Email\Models\SendingAccount;
use Dth\Email\Services\SendingAccountTestService;
use Dth\Email\Tests\TestCase;
use RuntimeException;

class SendingAccountTestServiceTest extends TestCase
{
    public function test_successful_smtp_test_is_logged_and_marks_account_active(): void
    {
        $account = $this->account();

        $transport = new class implements EmailTransport {
            public function send(EmailMessage $message, SendingAccount $account): ?string
            {
                return 'provider-test-123';
            }
        };

        $message = (new SendingAccountTestService($transport))->sendTest($account, 'TEST@EXAMPLE.COM');

        $this->assertSame(EmailMessageStatus::Sent, $message->status);
        $this->assertSame('test@example.com', $message->recipient_email);
        $this->assertSame('provider-test-123', $message->provider_message_id);
        $this->assertSame(SendingAccountStatus::Active, $account->refresh()->status);
        $this->assertSame('success', $account->last_test_status);
    }

    public function test_failed_smtp_test_is_logged_and_marks_account_error(): void
    {
        $account = $this->account();

        $transport = new class implements EmailTransport {
            public function send(EmailMessage $message, SendingAccount $account): ?string
            {
                throw new RuntimeException('SMTP authentication failed');
            }
        };

        try {
            (new SendingAccountTestService($transport))->sendTest($account, 'test@example.com');
            $this->fail('Expected SMTP test to fail.');
        } catch (RuntimeException $e) {
            $this->assertSame('SMTP authentication failed', $e->getMessage());
        }

        $account->refresh();
        $this->assertSame(SendingAccountStatus::Error, $account->status);
        $this->assertSame('failed', $account->last_test_status);
        $this->assertStringContainsString('SMTP authentication failed', (string) $account->last_test_error);
        $this->assertDatabaseHas('email_messages', ['status' => 'failed']);
    }

    private function account(): SendingAccount
    {
        return SendingAccount::query()->create([
            'name' => 'SMTP Test',
            'provider' => 'smtp',
            'from_name' => 'DTH',
            'from_email' => 'mail@example.com',
            'encrypted_config' => [
                'host' => 'smtp.example.com',
                'port' => 587,
                'scheme' => 'smtp',
                'username' => 'user',
                'password' => 'secret',
            ],
            'status' => 'inactive',
        ]);
    }
}
