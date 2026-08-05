<?php

namespace App\Models\Marketing;

use App\Enums\Marketing\EmailEventType;
use App\Models\Crm\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_token',
        'campaign_id',
        'campaign_recipient_id',
        'contact_id',
        'customer_id',
        'event_type',
        'event_payload',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => EmailEventType::class,
            'event_payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(CampaignRecipient::class, 'campaign_recipient_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
