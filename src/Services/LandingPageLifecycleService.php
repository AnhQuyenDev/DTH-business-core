<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Enums\FormAudienceType;
use Dth\Marketing\Enums\FormTemplateStatus;
use Dth\Marketing\Enums\LandingPageStatus;
use Dth\Marketing\Enums\MarketingCampaignStatus;
use Dth\Marketing\Models\LandingPage;
use Illuminate\Validation\ValidationException;

final class LandingPageLifecycleService
{
    /** @return array<string, array<int, string>> */
    public function transitions(): array
    {
        return [
            LandingPageStatus::Draft->value => [
                LandingPageStatus::Published->value,
                LandingPageStatus::Archived->value,
            ],
            LandingPageStatus::Published->value => [
                LandingPageStatus::Draft->value,
                LandingPageStatus::Archived->value,
            ],
            LandingPageStatus::Archived->value => [],
        ];
    }

    public function assertTransition(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }

        if (! in_array($to, $this->transitions()[$from] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "Invalid Landing Page transition: {$from} -> {$to}.",
            ]);
        }
    }

    /** @param array<int, string> $dirty */
    public function assertChangesAllowed(string $originalStatus, array $dirty): void
    {
        if ($originalStatus === LandingPageStatus::Archived->value) {
            $businessFields = array_diff($dirty, ['updated_at']);

            if ($businessFields !== []) {
                throw ValidationException::withMessages([
                    'status' => 'Archived Landing Pages are read-only.',
                ]);
            }
        }

        if ($originalStatus === LandingPageStatus::Published->value) {
            $allowed = ['status', 'published_at', 'updated_at'];

            if (array_diff($dirty, $allowed) !== []) {
                throw ValidationException::withMessages([
                    'status' => 'Unpublish the Landing Page before changing its content, campaign, forms or service scope.',
                ]);
            }
        }
    }

    public function assertPublishReady(LandingPage $page): void
    {
        $page->loadMissing(['marketingCampaign', 'personalFormTemplate', 'businessFormTemplate']);
        $campaign = $page->marketingCampaign;

        if ($campaign === null) {
            throw ValidationException::withMessages([
                'marketing_campaign_id' => 'A Marketing Campaign is required before publishing a Landing Page.',
            ]);
        }

        if (in_array($campaign->status, [
            MarketingCampaignStatus::Completed,
            MarketingCampaignStatus::Cancelled,
        ], true)) {
            throw ValidationException::withMessages([
                'marketing_campaign_id' => 'A completed or cancelled Marketing Campaign cannot receive a newly published Landing Page.',
            ]);
        }

        $personal = $page->personalFormTemplate;
        $business = $page->businessFormTemplate;

        if (
            $personal === null
            || $personal->audience_type !== FormAudienceType::Personal
            || $personal->status !== FormTemplateStatus::Active
        ) {
            throw ValidationException::withMessages([
                'personal_form_template_id' => 'Select an active Personal Form Template before publishing.',
            ]);
        }

        if (
            $business === null
            || $business->audience_type !== FormAudienceType::Business
            || $business->status !== FormTemplateStatus::Active
        ) {
            throw ValidationException::withMessages([
                'business_form_template_id' => 'Select an active Business Form Template before publishing.',
            ]);
        }

        $usesServiceInterest = $personal->fields()
            ->where('contact_mapping', 'lead.service_interest')
            ->exists() || $business->fields()
                ->where('contact_mapping', 'lead.service_interest')
                ->exists();

        if ($usesServiceInterest && blank($page->service_reference)) {
            throw ValidationException::withMessages([
                'service_reference' => 'A service is required because an attached form maps service interest to CRM.',
            ]);
        }

        app(LandingPageCatalogService::class)->assertSelectionValid(
            $campaign,
            $page->service_reference,
            (array) ($page->package_references ?? []),
        );
    }

    public function transition(LandingPage $page, LandingPageStatus $target): LandingPage
    {
        $from = $page->status instanceof \BackedEnum
            ? (string) $page->status->value
            : (string) $page->status;

        $this->assertTransition($from, $target->value);

        if ($target === LandingPageStatus::Published) {
            $this->assertPublishReady($page);
        }

        $page->status = $target;
        $page->published_at = $target === LandingPageStatus::Published ? now() : null;
        $page->save();

        return $page->refresh();
    }
}
