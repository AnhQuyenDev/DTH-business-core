<?php

namespace Dth\Marketing\Services;

use Dth\Marketing\Contracts\CatalogProvider;
use Dth\Marketing\DTO\CatalogPackageData;
use Dth\Marketing\DTO\CatalogServiceData;
use Dth\Marketing\Models\MarketingCampaign;
use Illuminate\Validation\ValidationException;

final class LandingPageCatalogService
{
    public function __construct(
        private readonly CatalogProvider $catalog,
    ) {}

    public function available(): bool
    {
        return $this->catalog->available();
    }

    /** @return array<string, string> */
    public function serviceOptions(?int $campaignId): array
    {
        if (! $this->catalog->available()) {
            return [];
        }

        $allowed = null;

        if ($campaignId !== null) {
            $campaign = MarketingCampaign::query()->find($campaignId);
            $campaignRefs = array_values(array_filter((array) ($campaign?->service_references ?? [])));
            $allowed = $campaignRefs !== [] ? array_flip(array_map('strval', $campaignRefs)) : null;
        }

        $options = [];

        foreach ($this->catalog->services() as $service) {
            if (! $service instanceof CatalogServiceData || ! $service->active) {
                continue;
            }

            if ($allowed !== null && ! isset($allowed[$service->reference])) {
                continue;
            }

            $options[$service->reference] = $service->name;
        }

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    /** @return array<string, string> */
    public function packageOptions(?string $serviceReference): array
    {
        if (! $this->catalog->available() || blank($serviceReference)) {
            return [];
        }

        $options = [];

        foreach ($this->catalog->packagesForService((string) $serviceReference) as $package) {
            if (! $package instanceof CatalogPackageData || ! $package->active) {
                continue;
            }

            $options[$package->reference] = $package->name;
        }

        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    /** @param array<int, mixed> $packageReferences */
    public function assertSelectionValid(
        ?MarketingCampaign $campaign,
        ?string $serviceReference,
        array $packageReferences,
    ): void {
        $serviceReference = filled($serviceReference) ? trim((string) $serviceReference) : null;
        $packageReferences = array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $packageReferences,
        ))));

        $campaignScope = array_values(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            (array) ($campaign?->service_references ?? []),
        )));

        if ($campaign !== null && $campaignScope !== []) {
            if ($serviceReference === null) {
                throw ValidationException::withMessages([
                    'service_reference' => 'A Landing Page inside a scoped campaign must select one promoted service.',
                ]);
            }

            if (! in_array($serviceReference, $campaignScope, true)) {
                throw ValidationException::withMessages([
                    'service_reference' => 'The selected service is outside the Marketing Campaign service scope.',
                ]);
            }
        }

        if ($serviceReference === null) {
            if ($packageReferences !== []) {
                throw ValidationException::withMessages([
                    'package_references' => 'Select a service before selecting service packages.',
                ]);
            }

            return;
        }

        if (! $this->catalog->available()) {
            // Existing references remain readable when Sales is temporarily absent,
            // but new values cannot be asserted against an unavailable catalog.
            return;
        }

        $service = $this->catalog->findService($serviceReference);

        if (! $service instanceof CatalogServiceData || ! $service->active) {
            throw ValidationException::withMessages([
                'service_reference' => 'The selected service does not exist or is inactive.',
            ]);
        }

        foreach ($packageReferences as $packageReference) {
            $package = $this->catalog->findPackage($packageReference);

            if (
                ! $package instanceof CatalogPackageData
                || ! $package->active
                || $package->serviceReference !== $serviceReference
            ) {
                throw ValidationException::withMessages([
                    'package_references' => "Package {$packageReference} is invalid for the selected service.",
                ]);
            }
        }
    }

    /** @param array<int, mixed> $packageReferences @return array<string, mixed> */
    public function snapshot(?string $serviceReference, array $packageReferences): array
    {
        if (! $this->catalog->available() || blank($serviceReference)) {
            return [];
        }

        $service = $this->catalog->findService((string) $serviceReference);

        if (! $service instanceof CatalogServiceData) {
            return [];
        }

        $packages = [];

        foreach ($packageReferences as $reference) {
            $package = $this->catalog->findPackage((string) $reference);

            if (! $package instanceof CatalogPackageData) {
                continue;
            }

            $packages[] = [
                'reference' => $package->reference,
                'service_reference' => $package->serviceReference,
                'name' => $package->name,
                'code' => $package->code,
                'active' => $package->active,
                'metadata' => $package->metadata,
            ];
        }

        return [
            'service' => [
                'reference' => $service->reference,
                'name' => $service->name,
                'code' => $service->code,
                'active' => $service->active,
                'metadata' => $service->metadata,
            ],
            'packages' => $packages,
        ];
    }
}
