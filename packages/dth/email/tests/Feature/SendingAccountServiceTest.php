<?php

namespace Dth\Email\Tests\Feature;

use Dth\Email\DTO\SmtpAccountData;
use Dth\Email\Models\SendingDomain;
use Dth\Email\Services\SendingAccountService;
use Dth\Email\Tests\TestCase;
use InvalidArgumentException;

class SendingAccountServiceTest extends TestCase
{
    public function test_it_encrypts_smtp_configuration_and_normalizes_sender_email(): void
    {
        $domain = SendingDomain::query()->create(['domain' => 'example.com']);

        $account = app(SendingAccountService::class)->create([
            'sending_domain_id' => $domain->id,
            'name' => 'Main SMTP',
            'from_name' => 'DTH',
            'from_email' => 'INFO@EXAMPLE.COM',
            'status' => 'active',
        ], new SmtpAccountData(
            host: 'smtp.example.com',
            port: 587,
            username: 'mailer',
            password: 'very-secret-password',
        ));

        $this->assertSame('info@example.com', $account->from_email);
        $this->assertSame('smtp.example.com', $account->encrypted_config['host']);
        $this->assertSame('very-secret-password', $account->encrypted_config['password']);

        $raw = $this->app['db']->table('email_sending_accounts')->where('id', $account->id)->value('encrypted_config');
        $this->assertIsString($raw);
        $this->assertStringNotContainsString('very-secret-password', $raw);
        $this->assertStringNotContainsString('smtp.example.com', $raw);
    }

    public function test_it_rejects_from_email_outside_selected_sending_domain(): void
    {
        $domain = SendingDomain::query()->create(['domain' => 'example.com']);

        $this->expectException(InvalidArgumentException::class);

        app(SendingAccountService::class)->create([
            'sending_domain_id' => $domain->id,
            'name' => 'Wrong domain',
            'from_email' => 'info@another.test',
            'status' => 'active',
        ], new SmtpAccountData(host: 'smtp.example.com'));
    }
}
