<?php

namespace Tests\Feature\Marketing;

use App\Models\Crm\Company;
use App\Models\Crm\LandingPageForm;
use App\Models\Marketing\FormField;
use App\Models\Marketing\FormTemplate;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageCompanyResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('business_flow.v2_enabled', true);
        config()->set('business_flow.company_resolution_enabled', true);
    }

    private function createBusinessLandingPage(): LandingPage
    {
        $template = FormTemplate::create([
            'name' => 'Business Form',
            'slug' => 'business-form-company',
            'audience_type' => 'business',
            'status' => 'active',
            'submit_button_text' => 'Gửi doanh nghiệp',
            'success_message' => 'Cảm ơn doanh nghiệp!',
            'html_body' => '<div class="lp-form-template"><form method="POST">{{fields}}<button type="submit">{{submit_button_text}}</button></form></div>',
        ]);

        $fields = [
            ['label' => 'Tên công ty', 'field_key' => 'company_name', 'field_type' => 'text', 'contact_mapping' => 'business.company_name', 'required' => true],
            ['label' => 'Email doanh nghiệp', 'field_key' => 'business_email', 'field_type' => 'email', 'contact_mapping' => 'business.business_email', 'required' => true],
            ['label' => 'Mã số thuế', 'field_key' => 'tax_code', 'field_type' => 'text', 'contact_mapping' => 'business.tax_code', 'required' => false],
        ];

        foreach ($fields as $index => $field) {
            FormField::create([
                'landing_form_template_id' => $template->id,
                'label' => $field['label'],
                'field_key' => $field['field_key'],
                'field_type' => $field['field_type'],
                'is_required' => $field['required'],
                'contact_mapping' => $field['contact_mapping'],
                'sort_order' => $index + 1,
            ]);
        }

        $page = LandingPage::create([
            'name' => 'Business Landing',
            'slug' => 'business-landing-'.uniqid(),
            'status' => 'published',
            'published_at' => now(),
        ]);

        LandingPageForm::create([
            'landing_page_id' => $page->id,
            'form_template_id' => $template->id,
            'form_type' => 'business',
            'display_mode' => 'embedded',
            'position_key' => 'end',
            'is_default' => true,
            'sort_order' => 0,
            'status' => 'active',
        ]);

        return $page;
    }

    public function test_business_submission_creates_company_when_flag_enabled(): void
    {
        $page = $this->createBusinessLandingPage();

        $this->post('/lp/'.$page->slug.'/submit', [
            '_token' => csrf_token(),
            'submission_type' => 'business',
            'company_name' => 'Công ty TNHH ABC',
            'business_email' => 'sales@abc.com.vn',
            'tax_code' => '0312345678',
        ]);

        $company = Company::query()->where('tax_code', '0312345678')->first();
        $this->assertNotNull($company);
        $this->assertSame('abc', $company->normalized_name);
        $this->assertSame('abc.com.vn', $company->email_domain);

        $submission = LandingPageSubmission::query()->where('landing_page_id', $page->id)->first();
        $this->assertNotNull($submission);
        $this->assertSame($company->id, $submission->company_id);

        $contact = $submission->contact;
        $this->assertTrue($contact->companies()->where('companies.id', $company->id)->exists());
    }

    public function test_business_submission_reuses_existing_company_by_tax_code(): void
    {
        $existing = Company::query()->create([
            'company_code' => 'COM-2026-000001',
            'legal_name' => 'Công ty TNHH ABC',
            'normalized_name' => 'abc',
            'tax_code' => '0312345678',
        ]);

        $page = $this->createBusinessLandingPage();

        $this->post('/lp/'.$page->slug.'/submit', [
            '_token' => csrf_token(),
            'submission_type' => 'business',
            'company_name' => 'Công ty TNHH ABC',
            'business_email' => 'sales@abc.com.vn',
            'tax_code' => '0312345678',
        ]);

        $submission = LandingPageSubmission::query()->where('landing_page_id', $page->id)->first();
        $this->assertSame($existing->id, $submission->company_id);
        $this->assertSame(1, Company::query()->count());
    }

    public function test_business_submission_with_free_email_domain_does_not_store_gmail(): void
    {
        $page = $this->createBusinessLandingPage();

        $this->post('/lp/'.$page->slug.'/submit', [
            '_token' => csrf_token(),
            'submission_type' => 'business',
            'company_name' => 'Công ty TNHH ABC',
            'business_email' => 'someone@gmail.com',
        ]);

        $submission = LandingPageSubmission::query()->where('landing_page_id', $page->id)->first();
        $this->assertNotNull($submission->company_id);

        $company = $submission->company;
        $this->assertNull($company->email_domain);
        $this->assertSame('abc', $company->normalized_name);
    }

    public function test_business_submission_respects_company_resolution_flag(): void
    {
        config()->set('business_flow.company_resolution_enabled', false);

        $page = $this->createBusinessLandingPage();

        $this->post('/lp/'.$page->slug.'/submit', [
            '_token' => csrf_token(),
            'submission_type' => 'business',
            'company_name' => 'Công ty TNHH ABC',
            'business_email' => 'sales@abc.com.vn',
            'tax_code' => '0312345678',
        ]);

        $submission = LandingPageSubmission::query()->where('landing_page_id', $page->id)->first();
        $this->assertNull($submission->company_id);
        $this->assertSame(0, Company::query()->count());
    }
}
