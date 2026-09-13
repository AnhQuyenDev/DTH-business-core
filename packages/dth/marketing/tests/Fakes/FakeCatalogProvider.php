<?php

namespace Dth\Marketing\Tests\Fakes;

use Dth\Marketing\Contracts\CatalogProvider;
use Dth\Marketing\DTO\CatalogPackageData;
use Dth\Marketing\DTO\CatalogServiceData;

final class FakeCatalogProvider implements CatalogProvider
{
    /** @param array<int, CatalogServiceData> $services @param array<int, CatalogPackageData> $packages */
    public function __construct(
        private readonly array $services = [],
        private readonly array $packages = [],
    ) {}

    public function available(): bool
    {
        return true;
    }

    public function capabilities(): array
    {
        return [
            'services' => true,
            'packages' => true,
        ];
    }

    public function services(): array
    {
        return $this->services;
    }

    public function findService(string $reference): ?CatalogServiceData
    {
        foreach ($this->services as $service) {
            if ($service->reference === $reference) {
                return $service;
            }
        }

        return null;
    }

    public function packagesForService(string $serviceReference): array
    {
        return array_values(array_filter(
            $this->packages,
            static fn (CatalogPackageData $package): bool => $package->serviceReference === $serviceReference,
        ));
    }

    public function findPackage(string $reference): ?CatalogPackageData
    {
        foreach ($this->packages as $package) {
            if ($package->reference === $reference) {
                return $package;
            }
        }

        return null;
    }
}
