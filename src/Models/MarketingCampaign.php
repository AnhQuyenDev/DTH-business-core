<?php

namespace Dth\Marketing\Models;

use Dth\Marketing\Enums\MarketingCampaignStatus;
use Dth\Marketing\Services\CampaignServiceScopeService;
use Dth\Marketing\Services\MarketingCampaignLifecycleService;
use Dth\Marketing\Support\UniqueSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MarketingCampaign extends Model
{
    protected $table = 'marketing_campaigns';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => MarketingCampaignStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'budget' => 'decimal:2',
            'service_references' => 'array',
            'service_snapshot' => 'array',
            'context' => 'array',
            'status_changed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $campaign): void {
            $campaign->slug = UniqueSlug::forModel(
                $campaign,
                (string) $campaign->name,
                $campaign->slug,
            );
            $requestedStatus = $campaign->status instanceof \BackedEnum
                ? (string) $campaign->status->value
                : (string) ($campaign->status ?? MarketingCampaignStatus::Draft->value);

            if ($requestedStatus !== MarketingCampaignStatus::Draft->value) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'status' => 'New Marketing Campaigns must start in Draft status.',
                ]);
            }

            $campaign->status = MarketingCampaignStatus::Draft;
            $campaign->currency = strtoupper((string) ($campaign->currency ?: 'VND'));
            $campaign->service_references = app(CampaignServiceScopeService::class)
                ->normalize((array) ($campaign->service_references ?? []));
            app(CampaignServiceScopeService::class)
                ->assertReferencesValid((array) $campaign->service_references);
            $campaign->service_snapshot = app(CampaignServiceScopeService::class)
                ->snapshot((array) $campaign->service_references);
            app(MarketingCampaignLifecycleService::class)->assertDates(
                $campaign->start_date?->toDateString(),
                $campaign->end_date?->toDateString(),
            );
        });

        static::updating(function (self $campaign): void {
            $lifecycle = app(MarketingCampaignLifecycleService::class);
            $originalStatus = (string) ($campaign->getRawOriginal('status') ?: MarketingCampaignStatus::Draft->value);
            $nextStatus = $campaign->status instanceof \BackedEnum
                ? (string) $campaign->status->value
                : (string) $campaign->status;

            if ($campaign->isDirty('status')) {
                $lifecycle->assertTransition($originalStatus, $nextStatus);

                if ($nextStatus === MarketingCampaignStatus::Active->value) {
                    app(CampaignServiceScopeService::class)->assertActivationReady($campaign);
                }
            }

            $lifecycle->assertChangesAllowed($originalStatus, array_keys($campaign->getDirty()));
            $lifecycle->assertDates(
                $campaign->start_date?->toDateString(),
                $campaign->end_date?->toDateString(),
            );

            if ($campaign->isDirty('slug') || trim((string) $campaign->slug) === '') {
                $campaign->slug = UniqueSlug::forModel(
                    $campaign,
                    (string) $campaign->name,
                    $campaign->slug,
                );
            }

            if ($campaign->isDirty('service_references')) {
                $scope = app(CampaignServiceScopeService::class);
                $campaign->service_references = $scope->normalize((array) $campaign->service_references);
                $scope->assertReferencesValid((array) $campaign->service_references);
                $campaign->service_snapshot = $scope->snapshot((array) $campaign->service_references);
            }
        });
    }

    public function landingPages(): HasMany
    {
        return $this->hasMany(LandingPage::class, 'marketing_campaign_id');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            MarketingCampaignStatus::Completed,
            MarketingCampaignStatus::Cancelled,
        ], true);
    }
}
