<?php

namespace Dth\Marketing\Tests\Feature;

use Dth\Marketing\Contracts\CatalogProvider;
use Dth\Marketing\DTO\CatalogServiceData;
use Dth\Marketing\Enums\MarketingCampaignStatus;
use Dth\Marketing\Models\MarketingCampaign;
use Dth\Marketing\Services\CampaignServiceScopeService;
use Dth\Marketing\Services\MarketingCampaignLifecycleService;
use Dth\Marketing\Tests\Fakes\FakeCatalogProvider;
use Dth\Marketing\Tests\TestCase;
use Illuminate\Validation\ValidationException;

class MarketingCampaignCoreTest extends TestCase
{
    public function test_campaign_lifecycle_and_terminal_scope_lock_are_enforced_in_backend(): void
    {
        $campaign = MarketingCampaign::query()->create([
            'name' => 'VPS Launch 2026',
            'budget' => 10000000,
            'start_date' => '2026-09-11',
            'end_date' => '2026-10-31',
        ]);

        $this->assertSame(MarketingCampaignStatus::Draft, $campaign->status);

        $lifecycle = app(MarketingCampaignLifecycleService::class);
        $lifecycle->transition($campaign, MarketingCampaignStatus::Active);
        $this->assertSame(MarketingCampaignStatus::Active, $campaign->status);

        $campaign->budget = 15000000;
        $campaign->end_date = '2026-11-15';
        $campaign->save();

        $lifecycle->transition($campaign, MarketingCampaignStatus::Paused);
        $lifecycle->transition($campaign, MarketingCampaignStatus::Active);
        $lifecycle->transition($campaign, MarketingCampaignStatus::Completed);

        $this->assertSame(MarketingCampaignStatus::Completed, $campaign->status);

        $this->expectException(ValidationException::class);
        $campaign->budget = 20000000;
        $campaign->save();
    }

    public function test_invalid_campaign_transition_is_rejected(): void
    {
        $campaign = MarketingCampaign::query()->create(['name' => 'Draft Campaign']);

        $this->expectException(ValidationException::class);
        app(MarketingCampaignLifecycleService::class)
            ->transition($campaign, MarketingCampaignStatus::Completed);
    }

    public function test_new_campaign_cannot_bypass_draft_status(): void
    {
        $this->expectException(ValidationException::class);

        MarketingCampaign::query()->create([
            'name' => 'Bypass Attempt',
            'status' => MarketingCampaignStatus::Active->value,
        ]);
    }

    public function test_catalog_scope_is_verified_only_through_catalog_provider(): void
    {
        $this->app->instance(CatalogProvider::class, new FakeCatalogProvider([
            new CatalogServiceData('svc-vps', 'VPS', 'VPS', true),
            new CatalogServiceData('svc-old', 'Old Service', 'OLD', false),
        ]));
        $this->app->forgetInstance(CampaignServiceScopeService::class);

        $campaign = MarketingCampaign::query()->create([
            'name' => 'Scoped Campaign',
            'service_references' => ['svc-vps'],
        ]);

        app(MarketingCampaignLifecycleService::class)
            ->transition($campaign, MarketingCampaignStatus::Active);

        $this->assertSame(['svc-vps'], $campaign->service_references);
        $this->assertSame('VPS', data_get($campaign->service_snapshot, '0.name'));
    }

    public function test_available_catalog_requires_a_valid_service_before_activation(): void
    {
        $this->app->instance(CatalogProvider::class, new FakeCatalogProvider([
            new CatalogServiceData('svc-vps', 'VPS', 'VPS', true),
        ]));
        $this->app->forgetInstance(CampaignServiceScopeService::class);

        $campaign = MarketingCampaign::query()->create(['name' => 'Missing Scope']);

        $this->expectException(ValidationException::class);
        $campaign->status = MarketingCampaignStatus::Active;
        $campaign->save();
    }
}
