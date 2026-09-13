<?php

namespace Dth\Marketing\Integrations\Sales;

use Dth\Marketing\Contracts\CatalogProvider;
use Dth\Marketing\DTO\CatalogPackageData;
use Dth\Marketing\DTO\CatalogServiceData;

final class NullCatalogProvider implements CatalogProvider
{
    public function available(): bool
    {
        return false;
    }

    public function capabilities(): array
    {
        return [];
    }

    public function services(): array
    {
        return [];
    }

    public function findService(string $reference): ?CatalogServiceData
    {
        return null;
    }

    public function packagesForService(string $serviceReference): array
    {
        return [];
    }

    public function findPackage(string $reference): ?CatalogPackageData
    {
        return null;
    }
}
