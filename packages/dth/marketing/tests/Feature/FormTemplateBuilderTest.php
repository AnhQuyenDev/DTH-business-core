<?php

namespace Dth\Marketing\Tests\Feature;

use Dth\Marketing\Enums\FormAudienceType;
use Dth\Marketing\Enums\FormFieldType;
use Dth\Marketing\Enums\FormTemplateStatus;
use Dth\Marketing\Enums\SemanticFieldRole;
use Dth\Marketing\Models\FormTemplate;
use Dth\Marketing\Services\FormTemplateHtmlImportService;
use Dth\Marketing\Services\FormTemplateLifecycleService;
use Dth\Marketing\Services\FormTemplatePreviewService;
use Dth\Marketing\Tests\TestCase;
use Illuminate\Validation\ValidationException;

class FormTemplateBuilderTest extends TestCase
{
    public function test_form_requires_fields_before_activation_and_preserves_dynamic_field_configuration(): void
    {
        $template = FormTemplate::query()->create([
            'name' => 'Personal Lead Form',
            'audience_type' => FormAudienceType::Personal->value,
        ]);

        try {
            $template->status = FormTemplateStatus::Active;
            $template->save();
            $this->fail('Activation without fields must fail.');
        } catch (ValidationException) {
            $template->refresh();
        }

        $field = $template->fields()->create([
            'label' => 'Email',
            'field_key' => 'email',
            'field_type' => FormFieldType::Email->value,
            'placeholder' => 'name@example.com',
            'is_required' => true,
            'validation_rules' => 'email|max:255',
            'sort_order' => 10,
        ]);

        $template->status = FormTemplateStatus::Active;
        $template->save();

        $this->assertSame(FormTemplateStatus::Active, $template->status);
        $this->assertSame(FormFieldType::Email, $field->fresh()->field_type);
        $this->assertTrue($field->fresh()->is_required);

        $html = (string) app(FormTemplatePreviewService::class)->render($template);
        $this->assertStringContainsString('type="email"', $html);
        $this->assertStringContainsString('disabled', $html);
    }

    public function test_form_audience_is_immutable_after_activation_and_archive_is_read_only(): void
    {
        $template = FormTemplate::query()->create([
            'name' => 'Business Lead Form',
            'audience_type' => FormAudienceType::Business->value,
        ]);
        $template->fields()->create([
            'label' => 'Company',
            'field_key' => 'company_name',
            'field_type' => FormFieldType::Text->value,
        ]);

        $lifecycle = app(FormTemplateLifecycleService::class);
        $lifecycle->transition($template, FormTemplateStatus::Active);

        try {
            $template->audience_type = FormAudienceType::Personal;
            $template->save();
            $this->fail('Audience type must be immutable after activation.');
        } catch (ValidationException) {
            $template->refresh();
        }

        $lifecycle->transition($template, FormTemplateStatus::Archived);

        $this->expectException(ValidationException::class);
        $template->name = 'Changed historical form';
        $template->save();
    }

    public function test_form_version_can_be_incremented_for_saved_builder_edits(): void
    {
        $template = FormTemplate::query()->create([
            'name' => 'Versioned Form',
            'audience_type' => FormAudienceType::Personal->value,
        ]);

        app(FormTemplateLifecycleService::class)->bumpVersion($template);

        $this->assertSame(2, $template->version);
    }

    public function test_html_import_detects_fields_and_maps_common_crm_targets(): void
    {
        $html = <<<'HTML'
<form class="lead-form">
    <label for="full_name">Full name</label>
    <input id="full_name" name="full_name" class="form-control" required>
    <label for="email">Email</label>
    <input id="email" name="email" type="email" placeholder="you@example.com" required>
    <label for="phone">Phone</label>
    <input id="phone" name="phone" type="tel">
    <select name="service_interest"><option value="">Choose</option><option value="vps">VPS</option></select>
    <button type="submit" class="btn-primary">Register</button>
</form>
HTML;

        $template = app(FormTemplateHtmlImportService::class)->import([
            'name' => 'Imported Personal Form',
            'audience_type' => FormAudienceType::Personal->value,
        ], $html);

        $this->assertSame(FormTemplateStatus::Draft, $template->status);
        $this->assertSame(4, $template->fields->count());
        $this->assertSame('lead.name', $template->fields->firstWhere('field_key', 'full_name')?->contact_mapping);
        $this->assertSame(SemanticFieldRole::PersonName, $template->fields->firstWhere('field_key', 'full_name')?->semantic_role);
        $this->assertGreaterThanOrEqual(85, (int) $template->fields->firstWhere('field_key', 'full_name')?->semantic_confidence);
        $this->assertSame('lead.email', $template->fields->firstWhere('field_key', 'email')?->contact_mapping);
        $this->assertSame('lead.phone', $template->fields->firstWhere('field_key', 'phone')?->contact_mapping);
        $this->assertSame('lead.service_interest', $template->fields->firstWhere('field_key', 'service_interest')?->contact_mapping);
        $this->assertStringContainsString('{{fields}}', (string) $template->html_body);
        $this->assertStringContainsString('dth-marketing-imported-form', (string) $template->html_body);

        $preview = (string) app(FormTemplatePreviewService::class)->render($template);
        $this->assertStringContainsString('class="lp-form-template__form"', $preview);
        $this->assertStringContainsString('Full name', $preview);
        $this->assertStringContainsString('Register', $preview);
        $this->assertStringNotContainsString('form-control', $preview);
    }


    public function test_html_import_understands_field_meaning_without_requiring_a_special_key(): void
    {
        $html = <<<'HTML'
<form>
    <label for="customer_abc">Họ và Tên</label>
    <input id="customer_abc" name="customer_abc" type="text" placeholder="Nhập họ và tên..." required>
    <label for="mail_x">Email cá nhân</label>
    <input id="mail_x" name="mail_x" type="email" required>
    <button type="submit">Gửi</button>
</form>
HTML;

        $template = app(FormTemplateHtmlImportService::class)->import([
            'name' => 'Semantic Personal Form',
            'audience_type' => FormAudienceType::Personal->value,
        ], $html);

        $name = $template->fields->firstWhere('field_key', 'customer_abc');
        $email = $template->fields->firstWhere('field_key', 'mail_x');

        $this->assertNotNull($name);
        $this->assertSame(SemanticFieldRole::PersonName, $name->semantic_role);
        $this->assertSame('auto', $name->semantic_source);
        $this->assertGreaterThanOrEqual(85, (int) $name->semantic_confidence);
        $this->assertSame(SemanticFieldRole::ContactEmail, $email?->semantic_role);
    }

    public function test_form_template_is_hard_deleted_from_management_actions(): void
    {
        $template = FormTemplate::query()->create([
            'name' => 'Delete Me',
            'audience_type' => FormAudienceType::Personal->value,
        ]);

        $template->delete();

        $this->assertDatabaseMissing('marketing_form_templates', ['id' => $template->id]);
    }

    public function test_new_form_template_cannot_bypass_draft_status(): void
    {
        $this->expectException(ValidationException::class);

        FormTemplate::query()->create([
            'name' => 'Bypass Form',
            'audience_type' => FormAudienceType::Personal->value,
            'status' => FormTemplateStatus::Active->value,
        ]);
    }
}
