<?php

namespace Dth\Marketing\Tests\Feature;

use Dth\Marketing\Services\SemanticMappingRepairService;
use Dth\Marketing\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PublicSubmissionPipelineTest extends TestCase
{
    public function test_published_landing_page_tracks_view_and_processes_idempotent_submission(): void
    {
        [$campaignId, $templateId, $pageId] = $this->seedPublishedPersonalLandingPage();

        $this->get('/lp/phase-me?utm_source=google&utm_medium=cpc&utm_campaign=launch')
            ->assertOk();

        $this->assertDatabaseHas('marketing_landing_page_views', [
            'landing_page_id' => $pageId,
            'marketing_campaign_id' => $campaignId,
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'launch',
        ]);

        $token = (string) Str::uuid();
        $payload = [
            'submission_type' => 'personal',
            '_submission_token' => $token,
            'full_name' => 'Nguyen Van A',
            'email' => 'A@Example.com',
            'utm_source' => 'google',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'launch',
        ];

        $this->post('/lp/phase-me/submit', $payload)
            ->assertRedirect('/lp/phase-me/thank-you');

        $this->assertDatabaseHas('marketing_landing_page_submissions', [
            'landing_page_id' => $pageId,
            'marketing_campaign_id' => $campaignId,
            'form_template_id' => $templateId,
            'submission_token' => $token,
            'normalized_email' => 'a@example.com',
            'status' => 'processed',
            'utm_source' => 'google',
        ]);

        // The same token and payload is idempotent: no second record is created.
        $this->post('/lp/phase-me/submit', $payload)
            ->assertRedirect('/lp/phase-me/thank-you');

        $this->assertSame(
            1,
            DB::table('marketing_landing_page_submissions')
                ->where('landing_page_id', $pageId)
                ->where('submission_token', $token)
                ->count(),
        );
    }


    public function test_successful_submission_runs_local_tag_list_and_field_value_automation(): void
    {
        [, $templateId, $pageId] = $this->seedPublishedPersonalLandingPage();

        DB::table('marketing_form_templates')->where('id', $templateId)->update([
            'auto_create_tags' => true,
            'auto_tag_names' => json_encode(['Website lead']),
            'auto_create_lists' => true,
            'auto_list_names' => json_encode(['Hosting prospects']),
        ]);
        DB::table('marketing_form_fields')
            ->where('form_template_id', $templateId)
            ->where('field_key', 'full_name')
            ->update(['tag_from_value' => true]);

        $this->post('/lp/phase-me/submit', [
            'submission_type' => 'personal',
            '_submission_token' => (string) Str::uuid(),
            'full_name' => 'VIP Lead',
            'email' => 'vip@example.com',
        ])->assertRedirect('/lp/phase-me/thank-you');

        $submission = DB::table('marketing_landing_page_submissions')
            ->where('landing_page_id', $pageId)
            ->first();
        $this->assertNotNull($submission);

        $tags = json_decode((string) $submission->tags, true, 512, JSON_THROW_ON_ERROR);
        $this->assertContains('Website lead', $tags);
        $this->assertContains('VIP Lead', $tags);

        $listId = DB::table('marketing_contact_lists')
            ->where('slug', 'hosting-prospects')
            ->value('id');
        $this->assertNotNull($listId);
        $this->assertDatabaseHas('marketing_contact_list_members', [
            'contact_list_id' => $listId,
            'source_submission_id' => $submission->id,
            'status' => 'subscribed',
        ]);
    }


    public function test_submission_normalizes_name_from_semantics_even_when_html_key_is_arbitrary(): void
    {
        [, $templateId, $pageId] = $this->seedPublishedPersonalLandingPage();

        DB::table('marketing_form_fields')
            ->where('form_template_id', $templateId)
            ->where('field_key', 'full_name')
            ->update([
                'label' => 'Họ và Tên',
                'field_key' => 'customer_abc',
                'contact_mapping' => null,
                'semantic_role' => null,
                'semantic_confidence' => null,
                'semantic_source' => null,
            ]);

        $this->post('/lp/phase-me/submit', [
            'submission_type' => 'personal',
            '_submission_token' => (string) Str::uuid(),
            'customer_abc' => 'Anh Quyền',
            'email' => 'semantic@example.com',
        ])->assertRedirect('/lp/phase-me/thank-you');

        $submission = DB::table('marketing_landing_page_submissions')
            ->where('landing_page_id', $pageId)
            ->first();

        $this->assertNotNull($submission);
        $this->assertSame('Anh Quyền', $submission->display_name);
        $normalized = json_decode((string) $submission->normalized_data, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Anh Quyền', data_get($normalized, 'person.name'));
        $this->assertSame('semantic@example.com', data_get($normalized, 'contact.email'));
    }


    public function test_repair_service_backfills_existing_submission_name_without_resubmission(): void
    {
        [, $templateId, $pageId] = $this->seedPublishedPersonalLandingPage();

        DB::table('marketing_form_fields')
            ->where('form_template_id', $templateId)
            ->where('field_key', 'full_name')
            ->update([
                'label' => 'Họ và Tên',
                'field_key' => 'legacy_random_key',
                'contact_mapping' => null,
                'semantic_role' => null,
                'semantic_confidence' => null,
                'semantic_source' => null,
            ]);

        $now = now();
        $submissionId = DB::table('marketing_landing_page_submissions')->insertGetId([
            'landing_page_id' => $pageId,
            'form_template_id' => $templateId,
            'payload_fingerprint' => hash('sha256', 'legacy-semantic-test'),
            'member_key' => hash('sha256', 'legacy-member'),
            'submission_type' => 'personal',
            // Older/imported data can be positional; repair aligns it to the
            // Form Template field order before semantic normalization.
            'data' => json_encode([
                'Anh Quyền',
                'old@example.com',
            ], JSON_UNESCAPED_UNICODE),
            'status' => 'processed',
            'contact_action' => 'skipped',
            'submitted_at' => $now,
            'processed_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        app(SemanticMappingRepairService::class)->repairAll();

        $row = DB::table('marketing_landing_page_submissions')->where('id', $submissionId)->first();
        $this->assertNotNull($row);
        $this->assertSame('Anh Quyền', $row->display_name);

        $normalized = json_decode((string) $row->normalized_data, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Anh Quyền', data_get($normalized, 'person.name'));
    }


    public function test_submission_hardening_rejects_oversized_payload_and_writes_audit_events(): void
    {
        [, , $pageId] = $this->seedPublishedPersonalLandingPage();
        config()->set('dth-marketing.security.max_submission_payload_bytes', 1024);

        $this->from('/lp/phase-me')->post('/lp/phase-me/submit', [
            'submission_type' => 'personal',
            '_submission_token' => (string) Str::uuid(),
            'full_name' => str_repeat('A', 1800),
            'email' => 'oversized@example.com',
        ])->assertSessionHasErrors('submission');

        $this->assertSame(0, DB::table('marketing_landing_page_submissions')
            ->where('landing_page_id', $pageId)
            ->count());

        config()->set('dth-marketing.security.max_submission_payload_bytes', 65536);
        $this->post('/lp/phase-me/submit', [
            'submission_type' => 'personal',
            '_submission_token' => (string) Str::uuid(),
            'full_name' => 'Audited User',
            'email' => 'audit@example.com',
        ])->assertRedirect('/lp/phase-me/thank-you');

        $this->assertDatabaseHas('marketing_audit_logs', ['action' => 'submission.received']);
        $this->assertDatabaseHas('marketing_audit_logs', ['action' => 'submission.processed']);
    }

    public function test_same_token_cannot_be_reused_for_different_payload(): void
    {
        [, , $pageId] = $this->seedPublishedPersonalLandingPage();
        $token = (string) Str::uuid();

        $this->post('/lp/phase-me/submit', [
            'submission_type' => 'personal',
            '_submission_token' => $token,
            'full_name' => 'First',
            'email' => 'first@example.com',
        ])->assertRedirect();

        $response = $this->from('/lp/phase-me')->post('/lp/phase-me/submit', [
            'submission_type' => 'personal',
            '_submission_token' => $token,
            'full_name' => 'Second',
            'email' => 'second@example.com',
        ]);

        $response->assertRedirect('/lp/phase-me');
        $response->assertSessionHasErrors('_submission_token');

        $this->assertSame(
            1,
            DB::table('marketing_landing_page_submissions')
                ->where('landing_page_id', $pageId)
                ->where('submission_token', $token)
                ->count(),
        );
    }

    /** @return array{int,int,int} */
    private function seedPublishedPersonalLandingPage(): array
    {
        $now = now();

        $campaignId = DB::table('marketing_campaigns')->insertGetId([
            'name' => 'Launch 2026',
            'slug' => 'launch-2026',
            'status' => 'active',
            'currency' => 'VND',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $templateId = DB::table('marketing_form_templates')->insertGetId([
            'name' => 'Personal form',
            'slug' => 'personal-form',
            'audience_type' => 'personal',
            'status' => 'active',
            'version' => 1,
            'submit_button_text' => 'Submit',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('marketing_form_fields')->insert([
            [
                'form_template_id' => $templateId,
                'label' => 'Full name',
                'field_key' => 'full_name',
                'field_type' => 'text',
                'is_required' => true,
                'contact_mapping' => 'lead.name',
                'tag_from_value' => false,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'form_template_id' => $templateId,
                'label' => 'Email',
                'field_key' => 'email',
                'field_type' => 'email',
                'is_required' => true,
                'contact_mapping' => 'lead.email',
                'tag_from_value' => false,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $pageId = DB::table('marketing_landing_pages')->insertGetId([
            'marketing_campaign_id' => $campaignId,
            'personal_form_template_id' => $templateId,
            'name' => 'Phase M-E',
            'slug' => 'phase-me',
            'status' => 'published',
            'published_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [$campaignId, $templateId, $pageId];
    }
}
