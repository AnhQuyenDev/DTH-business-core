<?php

namespace Tests\Feature\Crm;

use App\Models\Crm\BusinessContactProfile;
use App\Models\Crm\Company;
use App\Models\Crm\CompanyMatchCandidate;
use App\Models\Marketing\Contact;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageSubmission;
use App\Models\User;
use App\Services\Crm\CompanyMatchReviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyMatchCandidateWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function makePendingCandidate(): CompanyMatchCandidate
    {
        $company = Company::query()->create([
            'company_code' => 'COM-2026-000001',
            'legal_name' => 'Công ty TNHH ABC',
            'normalized_name' => 'abc',
        ]);

        $contact = Contact::query()->create(['contact_type' => 'business']);

        BusinessContactProfile::query()->create([
            'contact_id' => $contact->id,
            'company_name' => 'Công ty TNHH ABC',
            'legal_representative' => 'Nguyen Van A',
        ]);

        $page = LandingPage::query()->create([
            'name' => 'Business Landing',
            'slug' => 'business-landing-'.uniqid(),
            'status' => 'published',
        ]);

        $submission = LandingPageSubmission::query()->create([
            'landing_page_id' => $page->id,
            'contact_id' => $contact->id,
            'data' => ['company_name' => 'Công ty TNHH ABC'],
            'status' => 'processed',
            'submission_type' => 'business',
            'submitted_at' => now(),
        ]);

        return CompanyMatchCandidate::query()->create([
            'submission_id' => $submission->id,
            'contact_id' => $contact->id,
            'suggested_company_id' => $company->id,
            'confidence_score' => 65,
            'matched_by' => 'normalized_name',
            'status' => 'pending',
            'evidence' => [
                'submitted_company_name' => 'Công ty TNHH ABC',
            ],
        ]);
    }

    public function test_name_candidate_is_not_attached_before_acceptance(): void
    {
        // Tạo Company ABC không MST.
        // Submit Contact mới chỉ trùng normalized name.

        $candidate = $this->makePendingCandidate();
        $submission = $candidate->submission;
        $profile = $candidate->contact->businessProfile;

        $this->assertSame('pending', $candidate->status);
        $this->assertNull($submission->company_id);
        $this->assertNull($profile->company_id);
        $this->assertDatabaseMissing('company_contacts', [
            'company_id' => $candidate->suggested_company_id,
            'contact_id' => $candidate->contact_id,
        ]);
    }

    public function test_accept_candidate_updates_all_company_links(): void
    {
        $candidate = $this->makePendingCandidate();
        $admin = User::factory()->create(['role' => 'admin']);

        app(CompanyMatchReviewService::class)->accept(
            $candidate,
            $admin->id
        );

        $candidate->refresh();
        $candidate->submission->refresh();
        $candidate->contact->businessProfile->refresh();

        $this->assertSame('accepted', $candidate->status);
        $this->assertSame(
            $candidate->suggested_company_id,
            $candidate->submission->company_id
        );
        $this->assertSame(
            $candidate->suggested_company_id,
            $candidate->contact->businessProfile->company_id
        );
        $this->assertDatabaseHas('company_contacts', [
            'company_id' => $candidate->suggested_company_id,
            'contact_id' => $candidate->contact_id,
        ]);
    }
}
