<?php

namespace Dth\Marketing\Integrations\Email;

use Dth\Marketing\Contracts\EmailMarketingBridge;
use Dth\Marketing\DTO\EmailCampaignReference;
use Throwable;

/**
 * Optional adapter for the already-installed DTH Email package.
 *
 * This is the only layer in Marketing that knows DTH Email implementation
 * classes. Core Marketing services depend exclusively on EmailMarketingBridge.
 * No Email table, model, sending, queue, tracking or suppression logic is
 * modified by this adapter.
 */
final class DthEmailMarketingBridge implements EmailMarketingBridge
{
    private const SOURCE_TYPES = [
        'marketing_campaign',
        'marketing.campaign',
        'Dth\\Marketing\\Models\\MarketingCampaign',
    ];

    public function available(): bool
    {
        return class_exists('Dth\\Email\\Models\\EmailCampaign');
    }

    public function capabilities(): array
    {
        return [
            'campaign_links' => $this->available(),
            'campaign_navigation' => class_exists('Dth\\Email\\Filament\\Resources\\EmailCampaignResource'),
            'campaign_metrics' => class_exists('Dth\\Email\\Services\\CampaignAnalyticsService'),
            'send' => false,
            'tracking_write' => false,
        ];
    }

    public function campaignsForMarketingCampaign(string $marketingCampaignReference): array
    {
        if (! $this->available() || ! ctype_digit($marketingCampaignReference)) {
            return [];
        }

        $model = 'Dth\\Email\\Models\\EmailCampaign';

        try {
            return $model::query()
                ->where('source_id', (int) $marketingCampaignReference)
                ->whereIn('source_type', self::SOURCE_TYPES)
                ->orderByDesc('id')
                ->get()
                ->map(function ($campaign): EmailCampaignReference {
                    $status = $campaign->status instanceof \BackedEnum
                        ? (string) $campaign->status->value
                        : (string) ($campaign->status ?? '');

                    return new EmailCampaignReference(
                        reference: (string) $campaign->getKey(),
                        name: (string) $campaign->name,
                        status: $status !== '' ? $status : null,
                        adminUrl: $this->campaignAdminUrl((string) $campaign->getKey()),
                        metrics: $this->campaignMetrics((int) $campaign->getKey()),
                    );
                })
                ->all();
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }
    }


    /** @return array<string, int|float|null> */
    private function campaignMetrics(int $campaignId): array
    {
        $service = 'Dth\Email\Services\CampaignAnalyticsService';
        if (! class_exists($service)) {
            return [];
        }

        try {
            $stats = app($service)->forCampaignId($campaignId);

            return [
                'recipients' => isset($stats->total) ? (int) $stats->total : null,
                'sent' => isset($stats->sent) ? (int) $stats->sent : null,
                'opened' => isset($stats->opened) ? (int) $stats->opened : null,
                'clicked' => isset($stats->clicked) ? (int) $stats->clicked : null,
                'open_rate' => isset($stats->openRate) ? (float) $stats->openRate : null,
                'click_rate' => isset($stats->clickRate) ? (float) $stats->clickRate : null,
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }
    }

    public function campaignAdminUrl(string $emailCampaignReference): ?string
    {
        if (! class_exists('Dth\\Email\\Filament\\Resources\\EmailCampaignResource')) {
            return null;
        }

        $resource = 'Dth\\Email\\Filament\\Resources\\EmailCampaignResource';

        try {
            return $resource::getUrl('view', ['record' => $emailCampaignReference]);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
