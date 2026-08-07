<?php

namespace App\Observers\Marketing;

use App\Models\Marketing\LandingPageSubmission;
use LogicException;

final class LandingPageSubmissionObserver
{
    public function updating(LandingPageSubmission $submission): void
    {
        $immutableFields = [
            'landing_page_id',
            'campaign_id',
            'landing_form_template_id',
            'submission_token',
            'payload_fingerprint',
            'contact_id',
            'data',
            'normalized_email',
            'submission_type',
            'business_tax_code',
            'ip_address',
            'user_agent',
            'referrer',
            'utm_source',
            'utm_medium',
            'utm_campaign',
            'utm_content',
            'utm_term',
            'submitted_at',
        ];

        foreach ($immutableFields as $field) {
            if ($submission->isDirty($field)) {
                throw new LogicException(
                    "Không được sửa dữ liệu gốc của lượt gửi: {$field}."
                );
            }
        }
    }
}
