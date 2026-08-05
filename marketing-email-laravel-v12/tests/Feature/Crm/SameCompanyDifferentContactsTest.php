<?php

namespace Tests\Feature\Crm;

use App\Models\Crm\Company;
use App\Models\Crm\LandingPageForm;
use App\Models\Marketing\FormField;
use App\Models\Marketing\FormTemplate;
use App\Models\Marketing\LandingPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SameCompanyDifferentContactsTest extends TestCase
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
            'slug' => 'business-form-company-'.uniqid(),
            'audience_type' => 'business',
            'status' => 'active',
            'submit_button_text' => 'Gửi doanh nghiệp',
            'success_message' => 'Cảm ơn doanh nghiệp!',
            'html_body' => '<div class="lp-form-template"><form method="POST">{{fields}}<button type="submit">{{submit_button_text}}</button></form></div>',
        ]);

        $fields = [
            ['label' => 'Tên công ty', 'field_key' => 'company_name', 'field_type' => 'text', 'contact_mapping' => 'business.company_name', 'required' => true],
            ['label' => 'Email doanh nghiệp', 'field_key' => 'business_email', 'field_type' => 'email', 'contact_mapping' => 'business.business_email', 'required' => true],
            ['label' => 'Số điện thoại', 'field_key' => 'business_phone', 'field_type' => 'text', 'contact_mapping' => 'business.business_phone', 'required' => true],
            ['label' => 'Mã số thuế', 'field_key' => 'tax_code', 'field_type' => 'text', 'contact_mapping' => 'business.tax_code', 'required' => true],
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

    private function submitBusiness(LandingPage $page, array $payload): void
    {
        $this->post('/lp/'.$page->slug.'/submit', array_merge([
            '_token' => csrf_token(),
            'submission_type' => 'business',
        ], $payload))->assertSessionHasNoErrors();
    }

    public function test_two_people_with_same_tax_code_are_two_contacts_in_one_company(): void
    {
        // Arrange: tạo business landing page và form có name/email/phone/tax.
        $page = $this->createBusinessLandingPage();

        // Act 1: Giám đốc submit.
        $this->submitBusiness($page, [
            'company_name' => 'Công ty TNHH ABC',
            'business_email' => 'director@abc.com.vn',
            'business_phone' => '0901111111',
            'tax_code' => '0312345678',
        ]);

        // Act 2: IT submit với email + phone khác, cùng MST.
        $this->submitBusiness($page, [
            'company_name' => 'Công ty TNHH ABC',
            'business_email' => 'it@abc.com.vn',
            'business_phone' => '0902222222',
            'tax_code' => '0312345678',
        ]);

        $this->assertDatabaseCount('contacts', 2);
        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('company_contacts', 2);

        $company = Company::query()->firstOrFail();

        $this->assertSame(
            1,
            $company->contacts()
                ->wherePivot('is_primary', true)
                ->count()
        );
    }
}
