<?php

namespace Tests\Feature\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Models\Crm\Company;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\LandingPageForm;
use App\Models\Crm\Lead;
use App\Models\Marketing\Contact;
use App\Models\Marketing\FormField;
use App\Models\Marketing\FormTemplate;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultipleLeadsPerContactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('business_flow.v2_enabled', true);
        config()->set('business_flow.company_resolution_enabled', true);
    }

    private function createLandingPageWithForm(string $audienceType, array $fields): LandingPage
    {
        $template = FormTemplate::create([
            'name' => $audienceType.' Form',
            'slug' => $audienceType.'-form-'.uniqid(),
            'audience_type' => $audienceType,
            'status' => 'active',
            'submit_button_text' => 'Gửi',
            'success_message' => 'Cảm ơn!',
            'html_body' => '<div class="lp-form-template"><form method="POST">{{fields}}<button type="submit">{{submit_button_text}}</button></form></div>',
        ]);

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
            'name' => ucfirst($audienceType).' Landing',
            'slug' => $audienceType.'-landing-'.uniqid(),
            'status' => 'published',
            'published_at' => now(),
        ]);

        LandingPageForm::create([
            'landing_page_id' => $page->id,
            'form_template_id' => $template->id,
            'form_type' => $audienceType,
            'display_mode' => 'embedded',
            'position_key' => 'end',
            'is_default' => true,
            'sort_order' => 0,
            'status' => 'active',
        ]);

        return $page;
    }

    private function createPersonalLandingPage(): LandingPage
    {
        return $this->createLandingPageWithForm('personal', [
            ['label' => 'Họ tên', 'field_key' => 'full_name', 'field_type' => 'text', 'contact_mapping' => 'personal.full_name', 'required' => true],
            ['label' => 'Email cá nhân', 'field_key' => 'personal_email', 'field_type' => 'email', 'contact_mapping' => 'personal.email', 'required' => true],
        ]);
    }

    private function createBusinessLandingPage(): LandingPage
    {
        return $this->createLandingPageWithForm('business', [
            ['label' => 'Tên công ty', 'field_key' => 'company_name', 'field_type' => 'text', 'contact_mapping' => 'business.company_name', 'required' => true],
            ['label' => 'Email doanh nghiệp', 'field_key' => 'business_email', 'field_type' => 'email', 'contact_mapping' => 'business.business_email', 'required' => true],
            ['label' => 'Mã số thuế', 'field_key' => 'tax_code', 'field_type' => 'text', 'contact_mapping' => 'business.tax_code', 'required' => false],
        ]);
    }

    private function submit(LandingPage $page, string $type, array $payload): void
    {
        $this->post('/lp/'.$page->slug.'/submit', array_merge([
            '_token' => csrf_token(),
            'submission_type' => $type,
        ], $payload))->assertSessionHasNoErrors();
    }

    public function test_same_contact_with_two_submissions_gets_two_leads(): void
    {
        $page = $this->createPersonalLandingPage();

        $this->submit($page, 'personal', [
            'full_name' => 'Nguyễn Văn A',
            'personal_email' => 'a@example.com',
        ]);

        $this->submit($page, 'personal', [
            'full_name' => 'Nguyễn Văn A',
            'personal_email' => 'a@example.com',
        ]);

        $this->assertSame(1, Contact::query()->count());
        $this->assertSame(2, Lead::query()->count());

        $contact = Contact::query()->firstOrFail();
        $leads = Lead::query()->where('contact_id', $contact->id)->get();

        $this->assertCount(2, $leads);
        $this->assertNotSame($leads[0]->lead_code, $leads[1]->lead_code);

        $qualifications = ContactQualification::query()
            ->whereIn('lead_id', $leads->pluck('id'))
            ->get();

        $this->assertCount(2, $qualifications);
        $this->assertSame(
            2,
            $qualifications->pluck('lead_id')->unique()->count()
        );
    }

    public function test_two_contacts_of_same_company_each_have_their_own_lead(): void
    {
        $page = $this->createBusinessLandingPage();

        $this->submit($page, 'business', [
            'company_name' => 'Công ty TNHH ABC',
            'business_email' => 'director@abc.com.vn',
            'tax_code' => '0312345678',
        ]);

        $this->submit($page, 'business', [
            'company_name' => 'Công ty TNHH ABC',
            'business_email' => 'it@abc.com.vn',
            'tax_code' => '0312345678',
        ]);

        $this->assertSame(1, Company::query()->count());
        $this->assertSame(2, Contact::query()->count());
        $this->assertSame(2, Lead::query()->count());

        $company = Company::query()->firstOrFail();
        $leads = Lead::query()->where('company_id', $company->id)->get();

        $this->assertCount(2, $leads);
        $this->assertSame(
            2,
            $leads->pluck('contact_id')->unique()->count()
        );
    }

    public function test_each_submission_uses_qualification_from_its_own_lead(): void
    {
        $page = $this->createPersonalLandingPage();

        $this->submit($page, 'personal', [
            'full_name' => 'Nguyễn Văn A',
            'personal_email' => 'submission-relation@example.com',
        ]);

        $this->submit($page, 'personal', [
            'full_name' => 'Nguyễn Văn A',
            'personal_email' => 'submission-relation@example.com',
        ]);

        $submissions = LandingPageSubmission::query()
            ->with('lead.qualification')
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $submissions);

        $firstLead = $submissions[0]->lead;
        $secondLead = $submissions[1]->lead;

        $this->assertNotNull($firstLead);
        $this->assertNotNull($secondLead);
        $this->assertNotSame($firstLead->id, $secondLead->id);

        $firstLead->qualification()->update([
            'status' => ContactQualificationStatus::Contacting->value,
        ]);

        $secondLead->qualification()->update([
            'status' => ContactQualificationStatus::Qualified->value,
        ]);

        $submissions = LandingPageSubmission::query()
            ->with('lead.qualification')
            ->orderBy('id')
            ->get();

        $this->assertSame(
            ContactQualificationStatus::Contacting,
            $submissions[0]->lead?->qualification?->status
        );

        $this->assertSame(
            ContactQualificationStatus::Qualified,
            $submissions[1]->lead?->qualification?->status
        );
    }
}
