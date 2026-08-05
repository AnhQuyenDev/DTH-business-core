<?php

namespace App\Services\Crm;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\LeadIntakeStatus;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\Lead;
use App\Models\Marketing\LandingPageSubmission;

final class LeadCreationService
{
    public function __construct(
        private readonly LeadCodeGenerator $codeGenerator,
    ) {}

    public function createFromSubmission(
        LandingPageSubmission $submission,
        ?int $companyId = null,
        ?string $serviceInterest = null,
        ?int $userId = null,
    ): Lead {
        $existing = Lead::query()
            ->where('submission_id', $submission->id)
            ->first();

        if ($existing !== null) {
            return $existing->loadMissing('qualification');
        }

        $lead = Lead::query()->create([
            'lead_code' => $this->codeGenerator->next(),
            'submission_id' => $submission->id,
            'contact_id' => $submission->contact_id,
            'company_id' => $companyId ?? $submission->company_id,
            'source' => 'landing_page',
            'source_detail' => $submission->landingPage?->name,
            'title' => filled($serviceInterest)
                ? 'Yêu cầu tư vấn: '.$serviceInterest
                : 'Yêu cầu tư vấn từ Landing Page',
            'service_interest' => $serviceInterest,
            'intake_status' => LeadIntakeStatus::New->value,
            'metadata' => [
                'submission_type' => $submission->submission_type,
                'landing_page_id' => $submission->landing_page_id,
                'campaign_id' => $submission->campaign_id,
            ],
            'created_by' => $userId,
        ]);

        ContactQualification::query()->create([
            'lead_id' => $lead->id,
            'contact_id' => $lead->contact_id,
            'status' => ContactQualificationStatus::New->value,
            'priority' => 'normal',
            'service_interest' => $serviceInterest,
        ]);

        return $lead->fresh([
            'submission',
            'contact',
            'company',
            'qualification',
        ]);
    }
}
