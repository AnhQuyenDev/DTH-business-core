<?php

namespace Dth\Marketing\Contracts;

use Dth\Marketing\DTO\CatalogPackageData;
use Dth\Marketing\DTO\CatalogServiceData;

interface CatalogProvider extends IntegrationProvider
{
    /**
     * @return array<int, CatalogServiceData>
     */
    public function services(): array;

    public function findService(string $reference): ?CatalogServiceData;

    /**
     * @return array<int, CatalogPackageData>
     */
    public function packagesForService(string $serviceReference): array;

    public function findPackage(string $reference): ?CatalogPackageData;
}
