<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackedLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'campaign_recipient_id',
        'original_url',
        'tracking_token',
        'click_count',
        'last_clicked_at',
    ];

    protected function casts(): array
    {
        return [
            'click_count' => 'integer',
            'last_clicked_at' => 'datetime',
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
}
