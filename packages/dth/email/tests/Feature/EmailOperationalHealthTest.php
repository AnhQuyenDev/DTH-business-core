<?php

namespace Dth\Email\Tests\Feature;

use Dth\Email\Exceptions\EmailQuotaExceededException;
use Dth\Email\Models\EmailMessage;
use Dth\Email\Models\SendingAccount;
use Dth\Email\Services\EmailSendingQuotaService;
use Dth\Email\Services\EmailSystemHeartbeatService;
use Dth\Email\Tests\TestCase;
use Illuminate\Support\Str;

class EmailOperationalHealthTest extends TestCase
{
    public function test_heartbeat_is_recorded_and_reported_healthy(): void
    {
        $service = app(EmailSystemHeartbeatService::class);
        $service->touch(EmailSystemHeartbeatService::SCHEDULER);

        $this->assertSame('healthy', $service->health(EmailSystemHeartbeatService::SCHEDULER, 180));
        $this->assertNotNull($service->lastSeen(EmailSystemHeartbeatService::SCHEDULER));
    }

    public function test_hourly_quota_is_enforced_with_a_reserved_slot(): void
    {
        $account = SendingAccount::query()->create([
            'name' => 'Limited SMTP',
            'provider' => 'smtp',
            'from_email' => 'sender@example.com',
            'encrypted_config' => ['host' => 'smtp.example.com'],
            'status' => 'active',
            'hourly_limit' => 1,
        ]);

        $first = $this->message($account, 'one@example.com');
        $second = $this->message($account, 'two@example.com');
        $quota = app(EmailSendingQuotaService::class);

        $quota->reserve($first, $account);
        $quota->consume($first);

        $this->expectException(EmailQuotaExceededException::class);
        $quota->reserve($second, $account);
    }

    private function message(SendingAccount $account, string $recipient): EmailMessage
    {
        return EmailMessage::query()->create([
            'uuid' => (string) Str::uuid(),
            'sending_account_id' => $account->id,
            'from_email' => $account->from_email,
            'recipient_email' => $recipient,
            'subject' => 'Quota test',
            'status' => 'queued',
            'tracking_token' => (string) Str::uuid(),
            'unsubscribe_token' => (string) Str::uuid(),
            'queued_at' => now(),
        ]);
    }
}
