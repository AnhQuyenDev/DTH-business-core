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
        array $formAnswers = [],
        array $serviceContext = [],
        ?int $userId = null,
    ): Lead {
        $existing = Lead::query()
            ->where('submission_id', $submission->id)
            ->first();

        if ($existing !== null) {
            return $existing->loadMissing('qualification');
        }

        $serviceInterestAnswer = collect($formAnswers)
            ->first(function (mixed $answer): bool {
                if (! is_array($answer)) {
                    return false;
                }

                return in_array($answer['mapping'] ?? null, [
                    'lead.service_interest',
                    'personal.service_interest',
                    'business.service_interest',
                ], true);
            });
        $serviceInterestLabel = is_array($serviceInterestAnswer)
            ? ($serviceInterestAnswer['display_value'] ?? $serviceInterest)
            : $serviceInterest;
        $resolvedCompanyId = $companyId ?? $submission->company_id;
        $intakeIssues = [];

        if (blank($serviceInterest)) {
            $intakeIssues[] = 'missing_service_interest';
        }

        if ($submission->contact_id === null) {
            $intakeIssues[] = 'missing_contact';
        }

        if (
            $submission->submission_type === 'business'
            && $resolvedCompanyId === null
        ) {
            $intakeIssues[] = 'company_resolution_pending';
        }

        $lead = Lead::query()->create([
            'lead_code' => $this->codeGenerator->next(),
            'submission_id' => $submission->id,
            'contact_id' => $submission->contact_id,
            'company_id' => $resolvedCompanyId,
            'source' => 'landing_page',
            'source_detail' => $submission->landingPage?->name,
            'title' => filled($serviceInterestLabel)
                ? 'Yêu cầu tư vấn: '.$serviceInterestLabel
                : 'Yêu cầu tư vấn từ Landing Page',
            'service_interest' => $serviceInterest,
            'intake_status' => LeadIntakeStatus::New->value,
            'metadata' => [
                'service_interest_label' => $serviceInterestLabel,
                'submission_type' => $submission->submission_type,
                'landing_page_id' => $submission->landing_page_id,
                'landing_page_name' => $submission->landingPage?->name,
                // campaign_id là Email Campaign attribution cũ.
                'campaign_id' => $submission->campaign_id,
                // marketing_campaign_id là Ads Campaign nghiệp vụ của Landing Page.
                'marketing_campaign_id' => $submission->marketing_campaign_id,
                'marketing_campaign_name' => $submission
                    ->landingPage?->marketingCampaign?->name,
                'form_template_id' => $submission->landing_form_template_id,
                'captured_at' => $submission->submitted_at?->toIso8601String()
                    ?? now()->toIso8601String(),
                'intake_ready' => $intakeIssues === [],
                'intake_issues' => $intakeIssues,
                'form_answers' => array_values($formAnswers),
                'service_context' => $serviceContext,
                'attribution' => [
                    'referrer' => $submission->referrer,
                    'utm_source' => $submission->utm_source,
                    'utm_medium' => $submission->utm_medium,
                    'utm_campaign' => $submission->utm_campaign,
                    'utm_content' => $submission->utm_content,
                    'utm_term' => $submission->utm_term,
                ],
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
