<?php

namespace Dth\Email\Models;

use Dth\Email\Enums\CampaignRecipientStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CampaignRecipient extends Model
{
    protected $table = 'email_campaign_recipients';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => CampaignRecipientStatus::class,
            'variables' => 'array',
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'clicked_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = mb_strtolower(trim($value));
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'campaign_id');
    }

    public function message(): HasOne
    {
        return $this->hasOne(EmailMessage::class, 'campaign_recipient_id');
    }
}
