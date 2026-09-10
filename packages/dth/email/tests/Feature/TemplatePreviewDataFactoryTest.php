<?php

namespace Dth\Email\Tests\Feature;

use Dth\Email\Enums\EmailTemplateStatus;
use Dth\Email\Models\EmailTemplate;
use Dth\Email\Services\TemplatePreviewDataFactory;
use Dth\Email\Tests\TestCase;

class TemplatePreviewDataFactoryTest extends TestCase
{
    public function test_it_generates_preview_data_from_detected_variables(): void
    {
        $template = EmailTemplate::query()->create([
            'name' => 'Preview template',
            'subject' => 'Hello {{ customer.name }}',
            'html_body' => '<p>{{ customer.email }} - {{ quotation.total }}</p>',
            'status' => EmailTemplateStatus::Draft,
        ]);

        $data = app(TemplatePreviewDataFactory::class)->make($template);

        $this->assertSame('Nguyễn Văn A', data_get($data, 'customer.name'));
        $this->assertSame('customer@example.com', data_get($data, 'customer.email'));
        $this->assertSame('10.000.000 VNĐ', data_get($data, 'quotation.total'));
    }
}
