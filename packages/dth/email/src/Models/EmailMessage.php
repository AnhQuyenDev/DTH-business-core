<?php

namespace Dth\Email\Models;

use Dth\Email\Enums\EmailMessageStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailMessage extends Model
{
    protected $table = 'email_messages';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => EmailMessageStatus::class,
            'metadata' => 'array',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function setRecipientEmailAttribute(string $value): void
    {
        $this->attributes['recipient_email'] = mb_strtolower(trim($value));
    }

    public function sendingAccount(): BelongsTo
    {
        return $this->belongsTo(SendingAccount::class, 'sending_account_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    public function campaignRecipient(): BelongsTo
    {
        return $this->belongsTo(CampaignRecipient::class, 'campaign_recipient_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(
            EmailEvent::class,
            'message_id'
        )
            ->orderByDesc('occurred_at')
            ->orderByDesc('id');
    }

    public function trackedLinks(): HasMany
    {
        return $this->hasMany(EmailTrackedLink::class, 'message_id');
    }

}
