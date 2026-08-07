<?php

namespace App\Observers\Crm;

use App\Models\Crm\Lead;

final class LeadObserver
{
    public function saving(Lead $lead): void
    {
        $metadata = is_array($lead->metadata) ? $lead->metadata : [];
        $submissionType = data_get($metadata, 'submission_type');

        if (blank($submissionType) && $lead->submission_id !== null) {
            $submissionType = $lead->submission()
                ->value('submission_type');
        }

        $issues = [];

        if (blank($lead->service_interest)) {
            $issues[] = 'missing_service_interest';
        }

        if ($lead->contact_id === null) {
            $issues[] = 'missing_contact';
        }

        if (
            $submissionType === 'business'
            && $lead->company_id === null
        ) {
            $issues[] = 'company_resolution_pending';
        }

        $metadata['submission_type'] = $submissionType;
        $metadata['intake_ready'] = $issues === [];
        $metadata['intake_issues'] = $issues;

        $lead->metadata = $metadata;
    }
}
