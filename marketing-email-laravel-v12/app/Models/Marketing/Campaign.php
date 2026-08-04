<?php

namespace App\Models\Marketing;

use App\Models\User;
use App\Services\Marketing\AuditLogService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'subject',
        'preheader',
        'email_template_id',
        'sending_account_id',
        'landing_page_id',
        'audience_type',
        'audience_id',
        'status',
        'scheduled_at',
        'sent_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function sendingAccount(): BelongsTo
    {
        return $this->belongsTo(SendingAccount::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(EmailEvent::class);
    }

    public function trackedLinks(): HasMany
    {
        return $this->hasMany(TrackedLink::class);
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    protected static function booted(): void
    {
        static::created(function (self $campaign): void {
            app(AuditLogService::class)->log('campaign.created', $campaign, [], $campaign->attributesToArray());
        });

        static::updated(function (self $campaign): void {
            $changes = $campaign->getChanges();

            if ($changes !== []) {
                $action = array_key_exists('status', $changes)
                    ? 'campaign.' . $changes['status']
                    : 'campaign.updated';

                app(AuditLogService::class)->log($action, $campaign, $campaign->getOriginal(), $changes);
            }
        });

        static::deleted(function (self $campaign): void {
            app(AuditLogService::class)->log('campaign.deleted', $campaign, $campaign->getOriginal(), []);
        });
    }
}