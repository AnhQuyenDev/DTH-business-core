<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Contracts\CatalogProvider;
use Dth\Marketing\DTO\CatalogServiceData;
use Dth\Marketing\Models\MarketingCampaign;
use Illuminate\Validation\ValidationException;

final class CampaignServiceScopeService
{
    public function __construct(
        private readonly CatalogProvider $catalog,
    ) {}

    public function available(): bool
    {
        return $this->catalog->available();
    }

    /** @return array<string, string> */
    public function options(): array
    {
        if (! $this->catalog->available()) {
            return [];
        }

        $options = [];

        foreach ($this->catalog->services() as $service) {
            if (! $service instanceof CatalogServiceData || ! $service->active) {
                continue;
            }

            $options[$service->reference] = $service->name;
        }

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    /**
     * @param array<int, mixed> $references
     * @return array<int, string>
     */
    public function normalize(array $references): array
    {
        $normalized = [];

        foreach ($references as $reference) {
            $value = trim((string) $reference);

            if ($value === '') {
                continue;
            }

            $normalized[$value] = $value;
        }

        return array_values($normalized);
    }

    /** @param array<int, mixed> $references */
    public function assertReferencesValid(array $references): void
    {
        $references = $this->normalize($references);

        if ($references === []) {
            return;
        }

        if (! $this->catalog->available()) {
            throw ValidationException::withMessages([
                'service_references' => 'Sales catalog is unavailable, so service scope cannot be verified.',
            ]);
        }

        foreach ($references as $reference) {
            $service = $this->catalog->findService($reference);

            if (! $service instanceof CatalogServiceData || ! $service->active) {
                throw ValidationException::withMessages([
                    'service_references' => "Service {$reference} does not exist or is inactive.",
                ]);
            }
        }
    }

    /** @param array<int, mixed> $references @return array<int, array<string, mixed>> */
    public function snapshot(array $references): array
    {
        if (! $this->catalog->available()) {
            return [];
        }

        $snapshot = [];

        foreach ($this->normalize($references) as $reference) {
            $service = $this->catalog->findService($reference);

            if (! $service instanceof CatalogServiceData) {
                continue;
            }

            $snapshot[] = [
                'reference' => $service->reference,
                'name' => $service->name,
                'code' => $service->code,
                'active' => $service->active,
                'metadata' => $service->metadata,
            ];
        }

        return $snapshot;
    }

    public function assertActivationReady(MarketingCampaign $campaign): void
    {
        if (! $this->catalog->available()) {
            return;
        }

        $references = $this->normalize((array) ($campaign->service_references ?? []));

        if ($references === []) {
            throw ValidationException::withMessages([
                'service_references' => 'An active Marketing Campaign must promote at least one active Sales service.',
            ]);
        }

        $this->assertReferencesValid($references);
    }
}
