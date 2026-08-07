<?php

namespace Tests\Feature\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\LeadIntakeStatus;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\LandingPageForm;
use App\Models\Crm\Lead;
use App\Models\Marketing\Contact;
use App\Models\Marketing\FormField;
use App\Models\Marketing\FormTemplate;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageSubmission;
use App\Services\Crm\LeadCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubmissionCreatesLeadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('business_flow.v2_enabled', true);
        config()->set('business_flow.company_resolution_enabled', true);
    }

    private function createPersonalLandingPage(): LandingPage
    {
        $template = FormTemplate::create([
            'name' => 'Personal Form',
            'slug' => 'personal-form-'.uniqid(),
            'audience_type' => 'personal',
            'status' => 'active',
            'submit_button_text' => 'Gửi cá nhân',
            'success_message' => 'Cảm ơn cá nhân!',
            'html_body' => '<div class="lp-form-template"><form method="POST">{{fields}}<button type="submit">{{submit_button_text}}</button></form></div>',
        ]);

        $fields = [
            [
                'label' => 'Họ tên',
                'field_key' => 'full_name',
                'field_type' => 'text',
                'contact_mapping' => 'personal.full_name',
                'required' => true,
            ],
            [
                'label' => 'Email cá nhân',
                'field_key' => 'personal_email',
                'field_type' => 'email',
                'contact_mapping' => 'personal.email',
                'required' => true,
            ],
            [
                'label' => 'Điện thoại',
                'field_key' => 'phone',
                'field_type' => 'text',
                'contact_mapping' => 'personal.phone',
                'required' => false,
            ],
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
            'name' => 'Personal Landing',
            'slug' => 'personal-landing-'.uniqid(),
            'status' => 'published',
            'published_at' => now(),
        ]);

        LandingPageForm::create([
            'landing_page_id' => $page->id,
            'form_template_id' => $template->id,
            'form_type' => 'personal',
            'display_mode' => 'embedded',
            'position_key' => 'end',
            'is_default' => true,
            'sort_order' => 0,
            'status' => 'active',
        ]);

        return $page;
    }

    private function submitPersonal(LandingPage $page, array $payload): void
    {
        $this->post('/lp/'.$page->slug.'/submit', array_merge([
            '_token' => csrf_token(),
            'submission_type' => 'personal',
        ], $payload))->assertSessionHasNoErrors();
    }

    public function test_personal_submission_creates_lead_when_v2_enabled(): void
    {
        $page = $this->createPersonalLandingPage();

        $this->submitPersonal($page, [
            'full_name' => 'Nguyễn Văn A',
            'personal_email' => 'a@example.com',
        ]);

        $submission = LandingPageSubmission::query()->firstOrFail();
        $lead = Lead::query()->where('submission_id', $submission->id)->first();

        $this->assertNotNull($lead);
        $this->assertStringStartsWith('LEAD-2026-', $lead->lead_code);
        $this->assertSame($submission->contact_id, $lead->contact_id);
        $this->assertSame('landing_page', $lead->source);
        $this->assertSame('Personal Landing', $lead->source_detail);
        $this->assertSame(LeadIntakeStatus::New, $lead->intake_status);
    }

    public function test_lead_is_linked_to_a_new_qualification(): void
    {
        $page = $this->createPersonalLandingPage();

        $this->submitPersonal($page, [
            'full_name' => 'Nguyễn Văn A',
            'personal_email' => 'a@example.com',
        ]);

        $lead = Lead::query()->firstOrFail();
        $qualification = ContactQualification::query()->where('lead_id', $lead->id)->first();

        $this->assertNotNull($qualification);
        $this->assertSame($lead->contact_id, $qualification->contact_id);
        $this->assertSame(ContactQualificationStatus::New, $qualification->status);

        $this->assertNotNull($lead->qualification);
    }

    public function test_duplicate_payload_within_window_reuses_submission_and_lead(): void
    {
        $page = $this->createPersonalLandingPage();

        $this->submitPersonal($page, [
            'full_name' => 'Nguyễn Văn A',
            'personal_email' => 'a@example.com',
        ]);

        $submission = LandingPageSubmission::query()->firstOrFail();

        $this->post('/lp/'.$page->slug.'/submit', [
            '_token' => csrf_token(),
            'submission_type' => 'personal',
            'full_name' => 'Nguyễn Văn A',
            'personal_email' => 'a@example.com',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, LandingPageSubmission::query()->count());

        app(LeadCreationService::class)->createFromSubmission($submission);

        $this->assertSame(1, Lead::query()->count());
        $this->assertSame(
            1,
            Lead::query()->where('submission_id', $submission->id)->count()
        );
    }

    public function test_v2_disabled_falls_back_to_legacy_qualification(): void
    {
        config()->set('business_flow.v2_enabled', false);

        $page = $this->createPersonalLandingPage();

        $response = $this->post('/lp/'.$page->slug.'/submit', [
            '_token' => csrf_token(),
            'submission_type' => 'personal',
            'full_name' => 'Nguyễn Văn A',
            'personal_email' => 'a@example.com',
        ]);

        $submission = LandingPageSubmission::query()->firstOrFail();

        $this->assertSame(0, Lead::query()->count());
        $this->assertSame(1, ContactQualification::query()->count());
        $this->assertSame($submission->contact_id, ContactQualification::query()->firstOrFail()->contact_id);
    }

    public function test_phone_is_normalized_and_reused_for_contact_deduplication(): void
    {
        $page = $this->createPersonalLandingPage();

        $this->submitPersonal($page, [
            'full_name' => 'Nguyễn Văn A',
            'personal_email' => 'phone-first@example.com',
            'phone' => '090 123-4001',
        ]);

        $this->submitPersonal($page, [
            'full_name' => 'Nguyễn Văn A',
            'personal_email' => 'phone-second@example.com',
            'phone' => '0901234001',
        ]);

        $this->assertSame(1, Contact::query()->count());
        $this->assertSame(2, Lead::query()->count());

        $contact = Contact::query()
            ->with('personalProfile')
            ->firstOrFail();

        $this->assertSame(
            '0901234001',
            $contact->personalProfile?->phone
        );
    }

    public function test_soft_deleting_lead_does_not_delete_qualification(): void
    {
        $page = $this->createPersonalLandingPage();

        $this->submitPersonal($page, [
            'full_name' => 'Nguyễn Văn A',
            'personal_email' => 'soft-delete@example.com',
        ]);

        $lead = Lead::query()
            ->with('qualification')
            ->firstOrFail();

        $qualificationId = $lead->qualification?->id;

        $this->assertNotNull($qualificationId);

        $lead->delete();

        $this->assertSoftDeleted('leads', [
            'id' => $lead->id,
        ]);

        $this->assertDatabaseHas('contact_qualifications', [
            'id' => $qualificationId,
            'lead_id' => $lead->id,
        ]);
    }
}
