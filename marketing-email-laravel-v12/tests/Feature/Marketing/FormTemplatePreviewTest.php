<?php

namespace Tests\Feature\Marketing;

use App\Models\Marketing\FormField;
use App\Models\Marketing\FormTemplate;
use App\Models\Marketing\LandingPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormTemplatePreviewTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_preview_route_exists_and_works(): void
    {
        $template = FormTemplate::create([
            'name' => 'Test Form',
            'slug' => 'test-form',
            'audience_type' => 'personal',
            'status' => 'draft',
            'submit_button_text' => 'Gửi',
            'html_body' => '<div class="lp-form-template"><form method="POST">{{fields}}<button type="submit">{{submit_button_text}}</button></form></div>',
        ]);

        FormField::create([
            'landing_form_template_id' => $template->id,
            'label' => 'Email',
            'field_key' => 'email',
            'field_type' => 'email',
            'is_required' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->user)->get(route('marketing.form-templates.preview', $template));
        
        $response->assertStatus(200);
        $response->assertSee('Email');
        $response->assertSee('Gửi');
    }

    public function test_preview_with_landing_page_uses_theme_tokens(): void
    {
        $template = FormTemplate::create([
            'name' => 'Test Form',
            'slug' => 'test-form-2',
            'audience_type' => 'personal',
            'status' => 'draft',
            'submit_button_text' => 'Gửi',
            'html_body' => '<div class="lp-form-template"><form method="POST">{{fields}}<button type="submit">{{submit_button_text}}</button></form></div>',
        ]);

        FormField::create([
            'landing_form_template_id' => $template->id,
            'label' => 'Email',
            'field_key' => 'email',
            'field_type' => 'email',
            'is_required' => true,
            'sort_order' => 1,
        ]);

        $landingPage = LandingPage::create([
            'name' => 'Test LP',
            'slug' => 'test-lp',
            'theme_tokens' => [
                'primary' => '#7c3aed',
                'background' => '#f5f3ff',
            ],
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get(route('marketing.form-templates.preview', $template) . '?landing_page_id=' . $landingPage->id);
        
        $response->assertStatus(200);
        // Should contain the theme variables
        $response->assertSee('--lp-primary: #7c3aed;');
        $response->assertSee('--lp-background: #f5f3ff;');
    }

    public function test_preview_without_landing_page_uses_default_theme(): void
    {
        $template = FormTemplate::create([
            'name' => 'Test Form',
            'slug' => 'test-form-3',
            'audience_type' => 'personal',
            'status' => 'draft',
            'submit_button_text' => 'Gửi',
            'html_body' => '<div class="lp-form-template"><form method="POST">{{fields}}<button type="submit">{{submit_button_text}}</button></form></div>',
        ]);

        FormField::create([
            'landing_form_template_id' => $template->id,
            'label' => 'Email',
            'field_key' => 'email',
            'field_type' => 'email',
            'is_required' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->user)->get(route('marketing.form-templates.preview', $template));
        
        $response->assertStatus(200);
        // Should contain default theme
        $response->assertSee('--lp-primary: #2563eb;');
    }
}