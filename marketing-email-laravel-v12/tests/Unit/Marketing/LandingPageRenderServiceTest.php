<?php

namespace Tests\Unit\Marketing;

use App\Models\Crm\LandingPageForm;
use App\Models\Marketing\FormField;
use App\Models\Marketing\FormTemplate;
use App\Models\Marketing\LandingPage;
use App\Services\Marketing\LandingPageRenderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageRenderServiceTest extends TestCase
{
    use RefreshDatabase;

    private LandingPageRenderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LandingPageRenderService();
    }

    private function makeLandingPage(): LandingPage
    {
        $formTemplate = FormTemplate::create([
            'name'               => 'Render Form',
            'slug'               => 'render-form',
            'submit_button_text' => 'Submit Test',
            'status'             => 'active',
        ]);

        FormField::create([
            'landing_form_template_id' => $formTemplate->id,
            'label'           => 'Email',
            'field_key'       => 'email',
            'field_type'      => 'email',
            'is_required'     => true,
            'contact_mapping' => 'email',
            'sort_order'      => 1,
        ]);

        $page = LandingPage::create([
            'landing_form_template_id' => $formTemplate->id,
            'name'                     => 'Render Test Page',
            'slug'                     => 'render-test-page',
            'headline'                 => 'Amazing Headline',
            'html_body'                => '<!DOCTYPE html><html><body><h1>{{headline}}</h1><div>{{form}}</div></body></html>',
            'status'                   => 'published',
            'published_at'             => now(),
        ]);

        LandingPageForm::create([
            'landing_page_id'    => $page->id,
            'form_template_id'   => $formTemplate->id,
            'form_type'          => 'personal',
            'display_mode'       => 'single',
            'is_default'         => true,
            'sort_order'         => 1,
            'status'             => 'active',
        ]);

        return $page;
    }

    public function test_render_replaces_headline_placeholder(): void
    {
        $page   = $this->makeLandingPage();
        $output = $this->service->render($page);

        $this->assertStringContainsString('Amazing Headline', $output);
    }

    public function test_render_includes_form_html(): void
    {
        $page   = $this->makeLandingPage();
        $output = $this->service->render($page);

        $this->assertStringContainsString('<form', $output);
        $this->assertStringContainsString('Submit Test', $output);
    }

    public function test_render_form_has_csrf_token(): void
    {
        $page     = $this->makeLandingPage();
        $formHtml = $this->service->renderForm($page);

        $this->assertStringContainsString('_token', $formHtml);
    }

    public function test_render_appends_form_when_no_placeholder(): void
    {
        $formTemplate = FormTemplate::create([
            'name'   => 'Append Form',
            'slug'   => 'append-form',
            'status' => 'active',
        ]);

        $page = LandingPage::create([
            'landing_form_template_id' => $formTemplate->id,
            'name'                     => 'Append Test Page',
            'slug'                     => 'append-test-page',
            'html_body'                => '<html><body><p>No form here.</p></body></html>',
            'status'                   => 'published',
            'published_at'             => now(),
        ]);

        LandingPageForm::create([
            'landing_page_id'    => $page->id,
            'form_template_id'   => $formTemplate->id,
            'form_type'          => 'personal',
            'display_mode'       => 'single',
            'is_default'         => true,
            'sort_order'         => 1,
            'status'             => 'active',
        ]);

        $output = $this->service->render($page);
        // Form should be appended before </body>
        $this->assertStringContainsString('<form', $output);
    }

    public function test_sanitize_removes_script_tags(): void
    {
        $html = '<html><body><script>alert("xss")</script><p>Safe</p></body></html>';
        $clean = $this->service->sanitizeImportedHtml($html);

        $this->assertStringNotContainsString('<script>', $clean);
        $this->assertStringNotContainsString('alert(', $clean);
        $this->assertStringContainsString('<p>Safe</p>', $clean);
    }

    public function test_sanitize_removes_onclick_attributes(): void
    {
        $html = '<a onclick="badCode()">Click</a>';
        $clean = $this->service->sanitizeImportedHtml($html);

        $this->assertStringNotContainsString('onclick', $clean);
    }

    public function test_render_without_html_body_uses_fallback(): void
    {
        $page = LandingPage::create([
            'name'        => 'No Html Page',
            'slug'        => 'no-html-page',
            'headline'    => 'Fallback',
            'status'      => 'published',
            'published_at' => now(),
        ]);

        $output = $this->service->render($page);
        $this->assertStringContainsString('<!DOCTYPE html>', $output);
    }
}
