<?php

namespace Dth\Email\Tests\Feature;

use Dth\Email\Contracts\DnsResolver;
use Dth\Email\Enums\SendingDomainStatus;
use Dth\Email\Models\SendingDomain;
use Dth\Email\Services\DomainVerificationService;
use Dth\Email\Tests\TestCase;

class DomainVerificationServiceTest extends TestCase
{
    public function test_it_marks_domain_verified_when_spf_dkim_and_dmarc_are_present(): void
    {
        $domain = SendingDomain::query()->create([
            'domain' => 'Example.COM',
            'dkim_selector' => 'default',
        ]);

        $resolver = new class implements DnsResolver {
            public function txt(string $host): array
            {
                return match ($host) {
                    'example.com' => ['v=spf1 include:mail.example.test ~all'],
                    '_dmarc.example.com' => ['v=DMARC1; p=none'],
                    'default._domainkey.example.com' => ['v=DKIM1; k=rsa; p=ABC123'],
                    default => [],
                };
            }
        };

        $result = (new DomainVerificationService($resolver))->verify($domain);

        $this->assertTrue($result->isVerified());
        $this->assertSame(SendingDomainStatus::Verified, $domain->refresh()->status);
        $this->assertSame('example.com', $domain->domain);
        $this->assertNotNull($domain->verified_at);
    }

    public function test_it_keeps_domain_pending_until_a_dkim_selector_is_configured(): void
    {
        $domain = SendingDomain::query()->create(['domain' => 'example.com']);

        $resolver = new class implements DnsResolver {
            public function txt(string $host): array
            {
                return match ($host) {
                    'example.com' => ['v=spf1 ~all'],
                    '_dmarc.example.com' => ['v=DMARC1; p=none'],
                    default => [],
                };
            }
        };

        (new DomainVerificationService($resolver))->verify($domain);

        $this->assertSame(SendingDomainStatus::Pending, $domain->refresh()->status);
        $this->assertSame('pending', $domain->dkim_status);
    }
}
