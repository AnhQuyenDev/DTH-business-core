<?php

namespace App\Models\Finance;

use App\Models\Crm\Lead;
use App\Models\Marketing\Campaign;
use App\Models\Marketing\LandingPage;
use App\Models\Marketing\LandingPageSubmission;
use App\Models\Marketing\MarketingCampaign;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAttribution extends Model
{
    protected $fillable = [
        'payment_id', 'lead_id', 'landing_page_submission_id', 'landing_page_id',
        'marketing_campaign_id', 'email_campaign_id', 'acquisition_source',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
        'referrer', 'attribution_model', 'weight', 'attribution_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:4',
            'attribution_snapshot' => 'array',
        ];
    }

    public function payment(): BelongsTo { return $this->belongsTo(Payment::class); }
    public function lead(): BelongsTo { return $this->belongsTo(Lead::class); }
    public function submission(): BelongsTo { return $this->belongsTo(LandingPageSubmission::class, 'landing_page_submission_id'); }
    public function landingPage(): BelongsTo { return $this->belongsTo(LandingPage::class); }
    public function marketingCampaign(): BelongsTo { return $this->belongsTo(MarketingCampaign::class); }
    public function emailCampaign(): BelongsTo { return $this->belongsTo(Campaign::class, 'email_campaign_id'); }
}
