<?php

namespace Dth\Email\Tests\Feature;

use Dth\Email\Enums\EmailTemplateStatus;
use Dth\Email\Models\EmailTemplate;
use Dth\Email\Services\EmailTemplateService;
use Dth\Email\Tests\TestCase;
use InvalidArgumentException;
use LogicException;

class EmailTemplateServiceTest extends TestCase
{
    public function test_it_renders_an_active_template_by_logical_key(): void
    {
        EmailTemplate::query()->create([
            'template_key' => 'sales.quotation.sent',
            'name' => 'Quotation sent',
            'subject' => 'Quotation {{ quotation.number }} for {{ customer.name }}',
            'preheader' => 'Total: {{ quotation.total }}',
            'html_body' => '<p>Hello {{ customer.name }}</p>',
            'text_body' => 'Hello {{ customer.name }}',
            'status' => EmailTemplateStatus::Active,
        ]);

        $result = app(EmailTemplateService::class)->renderByKey(
            'sales.quotation.sent',
            [
                'quotation' => ['number' => 'Q-001', 'total' => '1,000,000 VND'],
                'customer' => ['name' => 'DTH Customer'],
            ],
        );

        $this->assertSame('Quotation Q-001 for DTH Customer', $result->subject);
        $this->assertSame('Total: 1,000,000 VND', $result->preheader);
        $this->assertSame('<p>Hello DTH Customer</p>', $result->htmlBody);
        $this->assertSame([], $result->missingVariables);
    }

    public function test_strict_render_rejects_missing_variables(): void
    {
        $template = EmailTemplate::query()->create([
            'template_key' => 'test.missing-variable',
            'name' => 'Missing variable',
            'subject' => 'Hello {{ name }}',
            'html_body' => '<p>{{ company.name }}</p>',
            'status' => EmailTemplateStatus::Active,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('company.name');

        app(EmailTemplateService::class)->render($template, ['name' => 'Alice'], true);
    }

    public function test_draft_template_cannot_be_used_for_delivery(): void
    {
        $template = EmailTemplate::query()->create([
            'template_key' => 'draft.template',
            'name' => 'Draft template',
            'subject' => 'Draft',
            'html_body' => '<p>Draft</p>',
            'status' => EmailTemplateStatus::Draft,
        ]);

        $this->expectException(LogicException::class);

        app(EmailTemplateService::class)->renderForDelivery($template, []);
    }

    public function test_template_key_is_normalized_and_generated_when_missing(): void
    {
        $template = EmailTemplate::query()->create([
            'name' => 'Welcome Customer',
            'subject' => 'Welcome',
            'html_body' => '<p>Welcome</p>',
            'status' => EmailTemplateStatus::Draft,
        ]);

        $this->assertSame('welcome.customer', $template->template_key);

        $second = EmailTemplate::query()->create([
            'name' => 'Welcome Customer',
            'subject' => 'Welcome again',
            'html_body' => '<p>Welcome again</p>',
            'status' => EmailTemplateStatus::Draft,
        ]);

        $this->assertSame('welcome.customer.2', $second->template_key);
    }
}
