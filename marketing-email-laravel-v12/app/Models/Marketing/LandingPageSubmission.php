<?php

namespace App\Models\Marketing;

use App\Enums\Marketing\LandingPageContactAction;
use App\Enums\Marketing\LandingPageSubmissionStatus;
use App\Models\Crm\Company;
use App\Models\Crm\Lead;
use App\Models\Crm\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LandingPageSubmission extends Model
{
    protected $fillable = [
        'landing_page_id',
        'campaign_id',
        'marketing_campaign_id',
        'landing_form_template_id',
        'submission_token',
        'payload_fingerprint',
        'contact_id',
        'company_id',
        'data',
        'normalized_email',
        'status',
        'contact_action',
        'submission_type',
        'business_tax_code',
        'qualification_status',
        'assigned_staff_id',
        'verified_at',
        'verified_by_staff_id',
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

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'status' => LandingPageSubmissionStatus::class,
            'contact_action' => LandingPageContactAction::class,
            'submitted_at' => 'datetime',
        ];
    }

    public function lead(): HasOne
    {
        return $this->hasOne(Lead::class, 'submission_id');
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function marketingCampaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'marketing_campaign_id');
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'landing_form_template_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'assigned_staff_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'verified_by_staff_id');
    }
}
