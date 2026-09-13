<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Contracts\EmailMarketingBridge;
use Dth\Marketing\Models\MarketingCampaign;
use Dth\Marketing\Models\MarketingCampaignEmailLink;
use Dth\Marketing\Support\UiText;

final class EmailMarketingLinkService
{
    public function __construct(
        private readonly EmailMarketingBridge $bridge,
    ) {}

    public function available(): bool
    {
        return $this->bridge->available();
    }

    public function refreshFromBridge(MarketingCampaign $campaign): int
    {
        if (! $this->bridge->available()) {
            return 0;
        }

        $references = $this->bridge->campaignsForMarketingCampaign((string) $campaign->getKey());
        $count = 0;

        foreach ($references as $reference) {
            MarketingCampaignEmailLink::query()->updateOrCreate(
                [
                    'marketing_campaign_id' => $campaign->getKey(),
                    'email_campaign_reference' => $reference->reference,
                ],
                [
                    'display_name' => $reference->name,
                    'status_snapshot' => $reference->status,
                    'admin_url_snapshot' => $reference->adminUrl
                        ?: $this->bridge->campaignAdminUrl($reference->reference),
                    'metrics_snapshot' => $reference->metrics,
                ],
            );
            $count++;
        }

        return $count;
    }

    /** @param array<string, mixed> $data */
    public function createManualLink(
        MarketingCampaign $campaign,
        array $data,
        ?int $createdBy = null,
    ): MarketingCampaignEmailLink {
        $reference = trim((string) ($data['email_campaign_reference'] ?? ''));
        if ($reference === '') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email_campaign_reference' => UiText::get('email_bridge.validation.reference_required', 'Email Campaign reference is required.'),
            ]);
        }

        return MarketingCampaignEmailLink::query()->updateOrCreate(
            [
                'marketing_campaign_id' => $campaign->getKey(),
                'email_campaign_reference' => $reference,
            ],
            [
                'display_name' => $this->nullableString($data['display_name'] ?? null),
                'status_snapshot' => $this->nullableString($data['status_snapshot'] ?? null),
                'admin_url_snapshot' => $this->nullableString($data['admin_url_snapshot'] ?? null, 2000),
                'created_by' => $createdBy,
            ],
        );
    }

    public function adminUrl(MarketingCampaignEmailLink $link): ?string
    {
        if ($this->bridge->available()) {
            $url = $this->bridge->campaignAdminUrl((string) $link->email_campaign_reference);
            if (filled($url)) {
                return $url;
            }
        }

        return filled($link->admin_url_snapshot)
            ? (string) $link->admin_url_snapshot
            : null;
    }

    private function nullableString(mixed $value, int $max = 255): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? mb_substr($value, 0, $max) : null;
    }
}
