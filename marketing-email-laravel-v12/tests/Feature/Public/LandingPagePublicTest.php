<?php

namespace Tests\Feature\Public;

use App\Enums\Marketing\LandingPageStatus;
use App\Models\Crm\LandingPageForm;
use App\Models\Crm\PersonalContactProfile;
use App\Models\Marketing\Contact;
use App\Models\Marketing\FormField;
use App\Models\Marketing\FormTemplate;
use App\Models\Marketing\LandingPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPagePublicTest extends TestCase
{
    use RefreshDatabase;

    private function makeLandingPage(array $overrides = []): LandingPage
    {
        $formTemplate = FormTemplate::create([
            'name' => 'Test Form',
            'slug' => 'test-form',
            'submit_button_text' => 'Submit',
            'status' => 'active',
            'auto_tag_names' => ['Test Tag'],
            'auto_list_names' => ['Test List'],
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
            'label' => 'Tên',
            'field_key' => 'first_name',
            'field_type' => 'text',
            'is_required' => true,
            'contact_mapping' => 'personal.first_name',
            'sort_order' => 2,
        ]);

        $defaults = [
            'landing_form_template_id' => $formTemplate->id,
            'name' => 'Test Landing Page',
            'slug' => 'test-landing-page',
            'headline' => 'Hello World',
            'html_body' => '<!DOCTYPE html><html><head><title>{{page_title}}</title></head><body><h1>{{headline}}</h1>{{form}}</body></html>',
            'status' => LandingPageStatus::Published,
            'published_at' => now(),
            'auto_tag_names' => ['Test Tag'],
            'auto_list_names' => ['Test List'],
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

    public function test_published_landing_page_is_accessible(): void
    {
        $page = $this->makeLandingPage();

        $response = $this->get('/lp/'.$page->slug);
        $response->assertStatus(200);
        $response->assertSee('Hello World');
    }

    public function test_draft_landing_page_returns_404(): void
    {
        $page = $this->makeLandingPage(['status' => LandingPageStatus::Draft, 'published_at' => null]);

        $this->get('/lp/'.$page->slug)->assertStatus(404);
    }

    public function test_nonexistent_landing_page_returns_404(): void
    {
        $this->get('/lp/nonexistent-slug')->assertStatus(404);
    }

    public function test_view_is_tracked_on_page_visit(): void
    {
        $page = $this->makeLandingPage();

        $this->get('/lp/'.$page->slug);

        $this->assertDatabaseHas('landing_page_views', [
            'landing_page_id' => $page->id,
        ]);
    }

    public function test_utm_params_are_tracked_for_view_and_submission(): void
    {
        $page = $this->makeLandingPage();

        $query = '?utm_source=facebook&utm_medium=paid_social&utm_campaign=summer_sale&utm_content=video_1&utm_term=retarget';

        $this->get('/lp/'.$page->slug.$query)->assertStatus(200);

        $this->post('/lp/'.$page->slug.'/submit'.$query, [
            '_token' => csrf_token(),
            'email' => 'utm-tracking@example.test',
            'first_name' => 'UTM',
        ])->assertRedirect();

        $this->assertDatabaseHas('landing_page_views', [
            'landing_page_id' => $page->id,
            'utm_source' => 'facebook',
            'utm_medium' => 'paid_social',
            'utm_campaign' => 'summer_sale',
            'utm_content' => 'video_1',
            'utm_term' => 'retarget',
        ]);

        $this->assertDatabaseHas('landing_page_submissions', [
            'landing_page_id' => $page->id,
            'normalized_email' => 'utm-tracking@example.test',
            'utm_source' => 'facebook',
            'utm_medium' => 'paid_social',
            'utm_campaign' => 'summer_sale',
            'utm_content' => 'video_1',
            'utm_term' => 'retarget',
        ]);
    }

    public function test_submit_form_creates_new_contact(): void
    {
        $page = $this->makeLandingPage();

        $response = $this->post('/lp/'.$page->slug.'/submit', [
            '_token' => csrf_token(),
            'email' => 'newlead@example.test',
            'first_name' => 'Nguyen',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('contacts', 1);
        $this->assertDatabaseHas('personal_contact_profiles', ['email' => 'newlead@example.test']);
        $this->assertDatabaseHas('landing_page_submissions', [
            'landing_page_id' => $page->id,
            'contact_action' => 'created',
        ]);
    }

    public function test_submit_form_with_existing_email_does_not_duplicate_contact(): void
    {
        $page = $this->makeLandingPage();

        $existing = Contact::create(['contact_type' => 'personal']);
        PersonalContactProfile::create([
            'contact_id' => $existing->id,
            'email' => 'existing@example.test',
            'first_name' => 'Old',
        ]);

        $this->post('/lp/'.$page->slug.'/submit', [
            '_token' => csrf_token(),
            'email' => 'existing@example.test',
            'first_name' => 'NewName',
        ]);

        $this->assertDatabaseCount('contacts', 1);
        $this->assertDatabaseHas('landing_page_submissions', [
            'normalized_email' => 'existing@example.test',
            'contact_action' => 'updated',
        ]);
    }

    public function test_submit_form_auto_creates_tags(): void
    {
        $page = $this->makeLandingPage();

        $this->post('/lp/'.$page->slug.'/submit', [
            '_token' => csrf_token(),
            'email' => 'tagtest@example.test',
            'first_name' => 'Tag',
        ]);

        $this->assertDatabaseHas('tags', ['name' => 'Test Tag']);

        $contact = Contact::firstWhere('contact_type', 'personal');
        $this->assertNotNull($contact);
        $this->assertDatabaseHas('personal_contact_profiles', [
            'contact_id' => $contact->id,
            'email' => 'tagtest@example.test',
        ]);
    }

    public function test_submit_form_auto_adds_contact_to_list(): void
    {
        $page = $this->makeLandingPage();

        $this->post('/lp/'.$page->slug.'/submit', [
            '_token' => csrf_token(),
            'email' => 'listtest@example.test',
            'first_name' => 'List',
        ]);

        $contact = Contact::firstWhere('contact_type', 'personal');
        $this->assertNotNull($contact);
        $this->assertDatabaseHas('personal_contact_profiles', [
            'contact_id' => $contact->id,
            'email' => 'listtest@example.test',
        ]);
        $this->assertDatabaseHas('contact_lists', ['name' => 'Test List']);
    }

    public function test_submission_is_recorded_in_database(): void
    {
        $page = $this->makeLandingPage();

        $this->post('/lp/'.$page->slug.'/submit', [
            '_token' => csrf_token(),
            'email' => 'sub@example.test',
            'first_name' => 'Sub',
        ]);

        $this->assertDatabaseHas('landing_page_submissions', [
            'landing_page_id' => $page->id,
            'normalized_email' => 'sub@example.test',
            'status' => 'processed',
        ]);
    }

    public function test_thank_you_page_is_accessible(): void
    {
        $page = $this->makeLandingPage();

        $response = $this->withSession(['success_message' => 'Cảm ơn!'])
            ->get('/lp/'.$page->slug.'/thank-you');

        $response->assertStatus(200);
        $response->assertSee('Cảm ơn!');
    }
}
