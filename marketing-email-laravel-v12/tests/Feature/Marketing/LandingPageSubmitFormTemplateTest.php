<?php

namespace Tests\Feature\Marketing;

use App\Enums\Marketing\LandingPageStatus;
use App\Models\Crm\LandingPageForm;
use App\Models\Marketing\FormField;
use App\Models\Marketing\FormTemplate;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageSubmitFormTemplateTest extends TestCase
{
    use RefreshDatabase;

    private function createLandingPageWithBothForms(): LandingPage
    {
        // Create personal form template
        $personalTemplate = FormTemplate::create([
            'name' => 'Personal Form',
            'slug' => 'personal-form',
            'audience_type' => 'personal',
            'status' => 'active',
            'submit_button_text' => 'Gửi cá nhân',
            'success_message' => 'Cảm ơn cá nhân!',
            'html_body' => '<div class="lp-form-template"><form method="POST">{{fields}}<button type="submit">{{submit_button_text}}</button></form></div>',
        ]);

        FormField::create([
            'landing_form_template_id' => $personalTemplate->id,
            'label' => 'Email cá nhân',
            'field_key' => 'personal_email',
            'field_type' => 'email',
            'is_required' => true,
            'contact_mapping' => 'personal.email',
            'sort_order' => 1,
        ]);

        // Create business form template
        $businessTemplate = FormTemplate::create([
            'name' => 'Business Form',
            'slug' => 'business-form',
            'audience_type' => 'business',
            'status' => 'active',
            'submit_button_text' => 'Gửi doanh nghiệp',
            'success_message' => 'Cảm ơn doanh nghiệp!',
            'redirect_url' => 'https://example.com/thank-you-business',
            'html_body' => '<div class="lp-form-template"><form method="POST">{{fields}}<button type="submit">{{submit_button_text}}</button></form></div>',
        ]);

        FormField::create([
            'landing_form_template_id' => $businessTemplate->id,
            'label' => 'Tên công ty',
            'field_key' => 'company_name',
            'field_type' => 'text',
            'is_required' => true,
            'contact_mapping' => 'business.company_name',
            'sort_order' => 1,
        ]);

        FormField::create([
            'landing_form_template_id' => $businessTemplate->id,
            'label' => 'Email doanh nghiệp',
            'field_key' => 'business_email',
            'field_type' => 'email',
            'is_required' => true,
            'contact_mapping' => 'business.business_email',
            'sort_order' => 2,
        ]);

        // Create landing page
        $page = LandingPage::create([
            'name' => 'Test Landing Page',
            'slug' => 'test-landing-page',
            'headline' => 'Test',
            'html_body' => '<!DOCTYPE html><html><body><h1>{{headline}}</h1></body></html>',
            'status' => LandingPageStatus::Published,
            'published_at' => now(),
        ]);

        // Attach both forms
        LandingPageForm::create([
            'landing_page_id' => $page->id,
            'form_template_id' => $personalTemplate->id,
            'form_type' => 'personal',
            'display_mode' => 'embedded',
            'position_key' => 'end',
            'is_default' => true,
            'sort_order' => 0,
            'status' => 'active',
        ]);

        LandingPageForm::create([
            'landing_page_id' => $page->id,
            'form_template_id' => $businessTemplate->id,
            'form_type' => 'business',
            'display_mode' => 'embedded',
            'position_key' => 'end',
            'is_default' => true,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        return $page;
    }

    public function test_personal_submit_uses_personal_form_template(): void
    {
        $page = $this->createLandingPageWithBothForms();

        $response = $this->post('/lp/'.$page->slug.'/submit', [
            '_token' => csrf_token(),
            'submission_type' => 'personal',
            'personal_email' => 'personal@test.com',
        ]);

        $response->assertRedirect();

        // Check submission uses personal form template
        $this->assertDatabaseHas('landing_page_submissions', [
            'landing_page_id' => $page->id,
            'submission_type' => 'personal',
            'normalized_email' => 'personal@test.com',
        ]);

        $submission = LandingPageSubmission::where('landing_page_id', $page->id)
            ->where('submission_type', 'personal')
            ->first();

        $this->assertNotNull($submission);
        $this->assertEquals($page->forms()->where('form_type', 'personal')->first()->form_template_id, $submission->landing_form_template_id);
    }

    public function test_business_submit_uses_business_form_template(): void
    {
        $page = $this->createLandingPageWithBothForms();

        $response = $this->post('/lp/'.$page->slug.'/submit', [
            '_token' => csrf_token(),
            'submission_type' => 'business',
            'company_name' => 'Test Company',
            'business_email' => 'business@test.com',
        ]);

        $response->assertRedirect();

        // Check submission uses business form template
        $this->assertDatabaseHas('landing_page_submissions', [
            'landing_page_id' => $page->id,
            'submission_type' => 'business',
            'normalized_email' => 'business@test.com',
        ]);

        $submission = LandingPageSubmission::where('landing_page_id', $page->id)
            ->where('submission_type', 'business')
            ->first();

        $this->assertNotNull($submission);
        $this->assertEquals($page->forms()->where('form_type', 'business')->first()->form_template_id, $submission->landing_form_template_id);
    }

    public function test_personal_submit_redirects_to_personal_success_message(): void
    {
        $page = $this->createLandingPageWithBothForms();

        $response = $this->post('/lp/'.$page->slug.'/submit', [
            '_token' => csrf_token(),
            'submission_type' => 'personal',
            'personal_email' => 'personal@test.com',
        ]);

        // Should redirect to thank you page with personal success message
        $response->assertRedirect(route('marketing.landing-pages.public.thank-you', $page->slug));

        $this->assertEquals('Cảm ơn cá nhân!', session('success_message'));
    }

    public function test_business_submit_redirects_to_business_redirect_url(): void
    {
        $page = $this->createLandingPageWithBothForms();

        $response = $this->post('/lp/'.$page->slug.'/submit', [
            '_token' => csrf_token(),
            'submission_type' => 'business',
            'company_name' => 'Test Company',
            'business_email' => 'business@test.com',
        ]);

        // Should redirect to business form's redirect_url
        $response->assertRedirect('https://example.com/thank-you-business');
    }

    public function test_submission_form_template_matches_submitted_tab(): void
    {
        $page = $this->createLandingPageWithBothForms();

        // Submit personal
        $this->post('/lp/'.$page->slug.'/submit', [
            '_token' => csrf_token(),
            'submission_type' => 'personal',
            'personal_email' => 'personal@test.com',
        ]);

        $personalSubmission = LandingPageSubmission::where('submission_type', 'personal')->first();
        $personalFormTemplateId = $page->forms()->where('form_type', 'personal')->first()->form_template_id;

        $this->assertEquals($personalFormTemplateId, $personalSubmission->landing_form_template_id);

        // Submit business
        $this->post('/lp/'.$page->slug.'/submit', [
            '_token' => csrf_token(),
            'submission_type' => 'business',
            'company_name' => 'Test Company',
            'business_email' => 'business@test.com',
        ]);

        $businessSubmission = LandingPageSubmission::where('submission_type', 'business')->first();
        $businessFormTemplateId = $page->forms()->where('form_type', 'business')->first()->form_template_id;

        $this->assertEquals($businessFormTemplateId, $businessSubmission->landing_form_template_id);
    }
}
