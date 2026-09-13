<?php

namespace Dth\Marketing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingCampaignEmailLink extends Model
{
    protected $table = 'marketing_campaign_email_links';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'metrics_snapshot' => 'array',
        ];
    }

    public function marketingCampaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'marketing_campaign_id');
    }
}
