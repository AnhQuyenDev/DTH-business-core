<?php

namespace Tests\Feature\Marketing;

use App\Enums\Crm\ContactQualificationStatus;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\LandingPageForm;
use App\Models\Crm\Lead;
use App\Models\Marketing\Contact;
use App\Models\Marketing\FormField;
use App\Models\Marketing\FormTemplate;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use LogicException;
use Tests\TestCase;

class LeadIntakeHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('business_flow.v2_enabled', true);
        config()->set('business_flow.company_resolution_enabled', true);
    }

    private function makeHostingPage(): LandingPage
    {
        $template = FormTemplate::query()->create([
            'name' => 'Hosting Personal Form',
            'slug' => 'hosting-personal-'.uniqid(),
            'audience_type' => 'personal',
            'status' => 'active',
            'submit_button_text' => 'Đăng ký',
        ]);

        $fields = [
            [
                'label' => 'Họ',
                'field_key' => 'first_name',
                'field_type' => 'text',
                'is_required' => true,
                'contact_mapping' => 'personal.first_name',
            ],
            [
                'label' => 'Tên',
                'field_key' => 'last_name',
                'field_type' => 'text',
                'is_required' => true,
                'contact_mapping' => 'personal.last_name',
            ],
            [
                'label' => 'Email',
                'field_key' => 'email',
                'field_type' => 'email',
                'is_required' => true,
                'contact_mapping' => 'personal.email',
            ],
            [
                'label' => 'Số điện thoại',
                'field_key' => 'phone',
                'field_type' => 'phone',
                'is_required' => true,
                'contact_mapping' => 'personal.phone',
            ],
            [
                'label' => 'Dịch vụ quan tâm',
                'field_key' => 'service_interest',
                'field_type' => 'select',
                'options' => [
                    'hosting_basic' => 'Web Hosting - Bình thường',
                    'hosting_pro' => 'Web Hosting - Trung bình',
                    'hosting_vip' => 'Web Hosting - Cao cấp',
                ],
                'is_required' => true,
                'contact_mapping' => 'lead.service_interest',
            ],
            [
                'label' => 'Số lượng website',
                'field_key' => 'website_count',
                'field_type' => 'number',
                'is_required' => true,
                'validation_rules' => 'integer|min:1|max:1000',
            ],
            [
                'label' => 'Cần chuyển dữ liệu',
                'field_key' => 'migration_required',
                'field_type' => 'select',
                'options' => [
                    'yes' => 'Có, cần hỗ trợ chuyển dữ liệu',
                    'no' => 'Không cần chuyển dữ liệu',
                    'unsure' => 'Chưa xác định, cần tư vấn',
                ],
                'is_required' => true,
            ],
            [
                'label' => 'Ngày dự kiến triển khai',
                'field_key' => 'expected_start_date',
                'field_type' => 'date',
                'is_required' => true,
                'validation_rules' => 'after_or_equal:today',
            ],
        ];

        foreach ($fields as $index => $field) {
            FormField::query()->create(array_merge($field, [
                'landing_form_template_id' => $template->id,
                'sort_order' => $index + 1,
                'position' => $index + 1,
            ]));
        }

        $page = LandingPage::query()->create([
            'name' => 'Hosting Intake Test',
            'slug' => 'hosting-intake-'.uniqid(),
            'status' => 'published',
            'published_at' => now(),
        ]);

        LandingPageForm::query()->create([
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

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            '_token' => csrf_token(),
            '_submission_token' => (string) Str::uuid(),
            'submission_type' => 'personal',
            'first_name' => 'Lê',
            'last_name' => 'Thu Hà',
            'email' => 'lead.hosting.personal.001@example.com',
            'phone' => '0900001002',
            'service_interest' => 'hosting_pro',
            'website_count' => 5,
            'migration_required' => 'yes',
            'expected_start_date' => now()->addDays(10)->toDateString(),
        ], $overrides);
    }

    private function submit(LandingPage $page, array $payload)
    {
        return $this->post('/lp/'.$page->slug.'/submit', $payload);
    }

    public function test_valid_submission_creates_a_ready_lead_with_snapshot(): void
    {
        $page = $this->makeHostingPage();
        $payload = $this->validPayload(['unexpected_admin_flag' => '1']);

        $this->submit($page, $payload)->assertSessionHasNoErrors();

        $this->assertSame(1, LandingPageSubmission::query()->count());
        $this->assertSame(1, Contact::query()->count());
        $this->assertSame(1, Lead::query()->count());
        $this->assertSame(1, ContactQualification::query()->count());

        $submission = LandingPageSubmission::query()->firstOrFail();
        $lead = Lead::query()->with('qualification')->firstOrFail();

        $this->assertSame(
            $payload['_submission_token'],
            $submission->submission_token
        );
        $this->assertNotNull($submission->payload_fingerprint);
        $this->assertArrayNotHasKey(
            'unexpected_admin_flag',
            $submission->data
        );
        $this->assertSame('hosting_pro', $lead->service_interest);
        $this->assertSame(
            'Web Hosting - Trung bình',
            data_get($lead->metadata, 'service_interest_label')
        );
        $this->assertTrue(
            (bool) data_get($lead->metadata, 'intake_ready')
        );
        $this->assertSame([], data_get($lead->metadata, 'intake_issues'));
        $this->assertSame(
            ContactQualificationStatus::New,
            $lead->qualification?->status
        );
        $this->assertSame(
            'hosting_pro',
            $lead->qualification?->service_interest
        );

        $answers = collect(data_get($lead->metadata, 'form_answers', []));
        $this->assertSame(
            'Web Hosting - Trung bình',
            $answers->firstWhere('key', 'service_interest')['display_value']
        );
        $this->assertSame(
            '5',
            $answers->firstWhere('key', 'website_count')['display_value']
        );
        $this->assertNull($answers->firstWhere('key', 'email'));
    }

    public function test_invalid_select_value_is_rejected_before_writes(): void
    {
        $page = $this->makeHostingPage();

        $this->submit($page, $this->validPayload([
            'service_interest' => 'hosting_free_forever',
        ]))->assertSessionHasErrors('service_interest');

        $this->assertDatabaseCount('landing_page_submissions', 0);
        $this->assertDatabaseCount('contacts', 0);
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_missing_service_interest_is_rejected_by_backend(): void
    {
        $page = $this->makeHostingPage();
        $payload = $this->validPayload();
        unset($payload['service_interest']);

        $this->submit($page, $payload)
            ->assertSessionHasErrors('service_interest');

        $this->assertDatabaseCount('landing_page_submissions', 0);
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_same_submission_token_is_idempotent(): void
    {
        $page = $this->makeHostingPage();
        $payload = $this->validPayload();

        $this->submit($page, $payload)->assertSessionHasNoErrors();
        $this->submit($page, $payload)->assertSessionHasNoErrors();

        $this->assertDatabaseCount('landing_page_submissions', 1);
        $this->assertDatabaseCount('leads', 1);
    }

    public function test_same_payload_with_different_tokens_is_deduplicated_in_window(): void
    {
        $page = $this->makeHostingPage();
        $payload = $this->validPayload();

        $this->submit($page, $payload)->assertSessionHasNoErrors();
        $this->submit($page, array_merge($payload, [
            '_submission_token' => (string) Str::uuid(),
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('landing_page_submissions', 1);
        $this->assertDatabaseCount('leads', 1);
    }

    public function test_same_contact_can_create_independent_leads_for_different_needs(): void
    {
        $page = $this->makeHostingPage();

        $this->submit($page, $this->validPayload())
            ->assertSessionHasNoErrors();
        $this->submit($page, $this->validPayload([
            '_submission_token' => (string) Str::uuid(),
            'service_interest' => 'hosting_vip',
            'website_count' => 12,
            'migration_required' => 'unsure',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('contacts', 1);
        $this->assertDatabaseCount('landing_page_submissions', 2);
        $this->assertDatabaseCount('leads', 2);

        $this->assertSame(
            ['hosting_pro', 'hosting_vip'],
            Lead::query()->orderBy('id')->pluck('service_interest')->all()
        );
    }

    public function test_submission_source_payload_is_immutable(): void
    {
        $page = $this->makeHostingPage();
        $this->submit($page, $this->validPayload())
            ->assertSessionHasNoErrors();

        $submission = LandingPageSubmission::query()->firstOrFail();

        $this->expectException(LogicException::class);
        $submission->update([
            'data' => array_merge($submission->data, ['tampered' => true]),
        ]);
    }
}
