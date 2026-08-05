<?php

namespace Tests\Unit\Marketing;

use App\Models\Crm\LandingPageForm;
use App\Models\Crm\PersonalContactProfile;
use App\Models\Marketing\Contact;
use App\Models\Marketing\EmailTemplate;
use App\Models\Marketing\FormField;
use App\Models\Marketing\FormTemplate;
use App\Models\Marketing\LandingPage;
use App\Services\Marketing\LandingPageSubmissionService;
use App\Services\Marketing\TemplateRenderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class LandingPageSubmissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private LandingPageSubmissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LandingPageSubmissionService::class);
    }

    private function makeLandingPage(array $overrides = []): LandingPage
    {
        $formTemplate = FormTemplate::create([
            'name' => 'Submission Test Form',
            'slug' => 'submission-test-form',
            'submit_button_text' => 'Submit',
            'status' => 'active',
            'auto_tag_names' => ['Auto Tag'],
            'auto_list_names' => ['Auto List'],
            'auto_create_tags' => true,
            'auto_create_lists' => true,
        ]);

        FormField::create([
            'landing_form_template_id' => $formTemplate->id,
            'label' => 'Email',
            'field_key' => 'email',
            'field_type' => 'email',
            'is_required' => true,
            'contact_mapping' => 'personal.email',
            'sort_order' => 1,
        ]);
        FormField::create([
            'landing_form_template_id' => $formTemplate->id,
            'label' => 'First Name',
            'field_key' => 'first_name',
            'field_type' => 'text',
            'is_required' => false,
            'contact_mapping' => 'personal.first_name',
            'sort_order' => 2,
        ]);

        $defaults = [
            'landing_form_template_id' => $formTemplate->id,
            'name' => 'Submission Test Page',
            'slug' => 'submission-test-page',
            'status' => 'published',
            'published_at' => now(),
            'auto_tag_names' => ['Page Tag'],
            'auto_list_names' => ['Page List'],
            'auto_create_tags' => true,
            'auto_create_lists' => true,
            'auto_create_segment' => false,
        ];

        $page = LandingPage::create(array_merge($defaults, $overrides));

        LandingPageForm::create([
            'landing_page_id' => $page->id,
            'form_template_id' => $formTemplate->id,
            'form_type' => 'personal',
            'display_mode' => 'single',
            'is_default' => true,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        return $page;
    }

    private function makeRequest(array $data = []): Request
    {
        $request = Request::create('/test', 'POST', $data);
        $request->setLaravelSession(app('session')->driver());

        return $request;
    }

    public function test_handle_creates_new_contact(): void
    {
        $page = $this->makeLandingPage();
        $request = $this->makeRequest();

        $submission = $this->service->handle($page, [
            'email' => 'newsub@example.test',
            'first_name' => 'New',
        ], $request);

        $this->assertDatabaseCount('contacts', 1);
        $this->assertDatabaseHas('personal_contact_profiles', ['email' => 'newsub@example.test']);
        $this->assertEquals('created', $submission->contact_action->value);
    }

    public function test_handle_does_not_duplicate_existing_contact(): void
    {
        $page = $this->makeLandingPage();
        $contact = Contact::create(['contact_type' => 'personal']);
        PersonalContactProfile::create([
            'contact_id' => $contact->id,
            'email' => 'existing@example.test',
        ]);

        $request = $this->makeRequest();
        $submission = $this->service->handle($page, ['email' => 'existing@example.test'], $request);

        $this->assertDatabaseCount('contacts', 1);
        $this->assertEquals('updated', $submission->contact_action->value);
    }

    public function test_auto_tags_are_created(): void
    {
        $page = $this->makeLandingPage();
        $request = $this->makeRequest();

        $this->service->handle($page, ['email' => 'tagcheck@example.test'], $request);

        $this->assertDatabaseHas('tags', ['name' => 'Auto Tag']);
        $this->assertDatabaseHas('tags', ['name' => 'Page Tag']);
    }

    public function test_auto_lists_are_created(): void
    {
        $page = $this->makeLandingPage();
        $request = $this->makeRequest();

        $this->service->handle($page, ['email' => 'listcheck@example.test'], $request);

        $this->assertDatabaseHas('contact_lists', ['name' => 'Auto List']);
        $this->assertDatabaseHas('contact_lists', ['name' => 'Page List']);
    }

    public function test_auto_segment_is_created_when_enabled(): void
    {
        $page = $this->makeLandingPage(['auto_create_segment' => true]);
        $request = $this->makeRequest();

        $this->service->handle($page, ['email' => 'seg@example.test'], $request);

        $this->assertDatabaseHas('segments', [
            'slug' => 'lead-tu-lp-submission-test-page',
        ]);
    }

    public function test_submission_is_saved_with_utm_data(): void
    {
        $page = $this->makeLandingPage();
        $request = Request::create('/test?utm_source=facebook&utm_medium=cpc', 'POST', ['email' => 'utm@example.test']);
        $request->setLaravelSession(app('session')->driver());

        $submission = $this->service->handle($page, ['email' => 'utm@example.test'], $request);

        $this->assertEquals('facebook', $submission->utm_source);
        $this->assertEquals('cpc', $submission->utm_medium);
    }

    public function test_landing_page_url_placeholder_in_email_template(): void
    {
        $renderService = app(TemplateRenderService::class);

        $template = new EmailTemplate([
            'subject' => 'Hello',
            'html_body' => '<a href="{{landing_page_url}}">Click</a>',
            'text_body' => null,
            'preheader' => null,
        ]);

        $rendered = $renderService->render($template, null, null, '', null, [
            'landing_page_url' => 'https://example.test/lp/test-page',
        ]);

        $this->assertStringContainsString('https://example.test/lp/test-page', $rendered['html_body']);
    }
}
