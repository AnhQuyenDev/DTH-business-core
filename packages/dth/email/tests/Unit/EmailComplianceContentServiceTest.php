<?php

namespace Dth\Email\Tests\Unit;

use Dth\Email\Services\EmailComplianceContentService;
use Dth\Email\Tests\TestCase;

class EmailComplianceContentServiceTest extends TestCase
{
    public function test_it_appends_unsubscribe_footer_when_template_does_not_have_one(): void
    {
        $service = new EmailComplianceContentService();
        $url = 'https://example.test/email/unsubscribe/token';

        $html = $service->ensureHtmlUnsubscribeFooter(
            '<html><body><p>Hello</p></body></html>',
            $url,
            'DTH Business Core',
        );

        $this->assertStringContainsString($url, $html);
        $this->assertStringContainsString('data-dth-email-compliance-footer="1"', $html);
        $this->assertLessThan(stripos($html, '</body>'), stripos($html, $url));
    }

    public function test_it_does_not_duplicate_existing_unsubscribe_link(): void
    {
        $service = new EmailComplianceContentService();
        $url = 'https://example.test/email/unsubscribe/token';
        $html = '<p><a href="'.$url.'">Unsubscribe</a></p>';

        $result = $service->ensureHtmlUnsubscribeFooter($html, $url, 'DTH');

        $this->assertSame(1, substr_count($result, $url));
    }
}
