<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Models\LandingPage;
use Dth\Marketing\Models\LandingPageUtmUrl;

final class LandingPageUtmService
{
    public function __construct(
        private readonly LandingPageUtmBuilder $builder,
    ) {}

    /** @param array<string, mixed> $values */
    public function create(LandingPage $page, array $values, ?int $createdBy = null): LandingPageUtmUrl
    {
        $url = $this->builder->build($page, $values);
        $page->loadMissing('marketingCampaign');

        $campaign = trim((string) ($values['campaign'] ?? ''));
        if ($campaign === '') {
            $campaign = (string) ($page->marketingCampaign?->slug ?: $page->marketingCampaign?->name ?: $page->slug);
        }

        return LandingPageUtmUrl::query()->create([
            'landing_page_id' => $page->getKey(),
            'marketing_campaign_id' => $page->marketing_campaign_id,
            'name' => $this->nullableString($values['name'] ?? null),
            'utm_source' => $this->nullableString($values['source'] ?? null),
            'utm_medium' => $this->nullableString($values['medium'] ?? null),
            'utm_campaign' => $this->nullableString($campaign),
            'utm_content' => $this->nullableString($values['content'] ?? null),
            'utm_term' => $this->nullableString($values['term'] ?? null),
            'url' => $url,
            'created_by' => $createdBy,
        ]);
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? mb_substr($value, 0, 255) : null;
    }
}
