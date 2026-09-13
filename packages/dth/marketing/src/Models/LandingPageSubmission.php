<?php

namespace Dth\Marketing\Models;

use Dth\Marketing\Enums\LandingPageContactAction;
use Dth\Marketing\Enums\LandingPageSubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandingPageSubmission extends Model
{
    protected $table = 'marketing_landing_page_submissions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'normalized_data' => 'array',
            'tags' => 'array',
            'integration_snapshot' => 'array',
            'status' => LandingPageSubmissionStatus::class,
            'contact_action' => LandingPageContactAction::class,
            'submitted_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class, 'landing_page_id');
    }

    public function marketingCampaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'marketing_campaign_id');
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }

    public function listMemberships(): HasMany
    {
        return $this->hasMany(ContactListMember::class, 'source_submission_id');
    }
}
