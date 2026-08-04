<?php

namespace Tests\Unit;

use App\Models\Crm\BusinessContactProfile;
use App\Models\Crm\PersonalContactProfile;
use App\Models\Marketing\Contact;
use App\Models\Marketing\EmailTemplate;
use App\Models\Marketing\SuppressionEntry;
use App\Services\Marketing\SuppressionService;
use App\Services\Marketing\TemplateRenderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_render_replaces_contact_variables_and_unsubscribe_link(): void
    {
        $template = EmailTemplate::query()->create([
            'name' => 'Test Template',
            'subject' => 'Hello {{first_name}} from {{company_name}}',
            'preheader' => 'Welcome {{full_name}}',
            'html_body' => '<p>Hi {{first_name}} {{last_name}}</p>',
            'text_body' => 'Hi {{email}}',
            'status' => 'active',
            'created_by' => null,
        ]);

        $contact = Contact::query()->create([
            'contact_type' => 'personal',
        ]);

        PersonalContactProfile::query()->create([
            'contact_id' => $contact->id,
            'first_name' => 'Anh',
            'last_name' => 'Nguyen',
            'email' => 'anh@example.test',
        ]);

        BusinessContactProfile::query()->create([
            'contact_id' => $contact->id,
            'company_name' => 'Demo Co',
        ]);

        $rendered = app(TemplateRenderService::class)->render($template, $contact, null, 'https://example.test/unsubscribe/abc');

        $this->assertSame('Hello Anh from Demo Co', $rendered['subject']);
        $this->assertStringContainsString('Hi Anh Nguyen', $rendered['html_body']);
        $this->assertStringContainsString('https://example.test/unsubscribe/abc', $rendered['html_body']);
        $this->assertStringContainsString('Hi anh@example.test', $rendered['text_body']);
    }

    public function test_suppression_service_flags_blocked_email(): void
    {
        SuppressionEntry::query()->create([
            'email' => 'blocked@example.test',
            'reason' => 'unsubscribe',
            'source' => 'test',
        ]);

        $service = app(SuppressionService::class);

        $this->assertTrue($service->isSuppressed('blocked@example.test'));
        $this->assertFalse($service->isSuppressed('allowed@example.test'));
    }
}