<?php

namespace Dth\Commercial\Integrations\Marketing;

use Dth\Commercial\Enums\ServiceStatus;
use Dth\Commercial\Models\Service;
use Dth\Commercial\Models\ServicePackage;
use Dth\Marketing\Contracts\CatalogProvider;
use Dth\Marketing\DTO\CatalogPackageData;
use Dth\Marketing\DTO\CatalogServiceData;

final class DthMarketingCatalogProvider implements CatalogProvider
{
    public function available(): bool
    {
        return (bool) config('dth-commercial.enabled', true)
            && (bool) config('dth-commercial.features.catalog', true);
    }

    public function capabilities(): array
    {
        return [
            'services' => $this->available(),
            'packages' => $this->available() && (bool) config('dth-commercial.features.packages', true),
        ];
    }

    public function services(): array
    {
        if (! $this->available()) {
            return [];
        }

        return Service::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Service $service): CatalogServiceData => $this->toServiceData($service))
            ->all();
    }

    public function findService(string $reference): ?CatalogServiceData
    {
        if (! $this->available()) {
            return null;
        }

        $reference = trim($reference);
        $service = Service::query()
            ->where('slug', $reference)
            ->orWhere('service_code', $reference)
            ->first();

        return $service ? $this->toServiceData($service) : null;
    }

    public function packagesForService(string $serviceReference): array
    {
        if (! $this->available() || ! config('dth-commercial.features.packages', true)) {
            return [];
        }

        $service = Service::query()
            ->where('slug', $serviceReference)
            ->orWhere('service_code', $serviceReference)
            ->first();

        if (! $service) {
            return [];
        }

        return $service->packages()
            ->get()
            ->map(fn (ServicePackage $package): CatalogPackageData => $this->toPackageData($package, $service))
            ->all();
    }

    public function findPackage(string $reference): ?CatalogPackageData
    {
        if (! $this->available() || ! config('dth-commercial.features.packages', true)) {
            return null;
        }

        $package = ServicePackage::query()->with('service')->where('package_code', trim($reference))->first();

        return $package && $package->service ? $this->toPackageData($package, $package->service) : null;
    }

    private function toServiceData(Service $service): CatalogServiceData
    {
        $status = $service->status instanceof ServiceStatus ? $service->status->value : (string) $service->status;

        return new CatalogServiceData(
            reference: $service->reference(),
            name: (string) $service->name,
            code: (string) $service->service_code,
            active: $status === ServiceStatus::Active->value,
            metadata: [
                'description' => $service->description,
                'scope' => $service->default_scope,
                'terms' => $service->default_terms,
                'commercial_id' => $service->getKey(),
            ],
        );
    }

    private function toPackageData(ServicePackage $package, Service $service): CatalogPackageData
    {
        $status = $package->status instanceof ServiceStatus ? $package->status->value : (string) $package->status;

        return new CatalogPackageData(
            reference: $package->reference(),
            serviceReference: $service->reference(),
            name: (string) $package->name,
            code: (string) $package->package_code,
            active: $status === ServiceStatus::Active->value,
            metadata: [
                'description' => $package->description,
                'audience_type' => $package->audience_type instanceof \BackedEnum ? $package->audience_type->value : $package->audience_type,
                'billing_period' => $package->billing_period,
                'billing_period_unit' => $package->billing_period_unit instanceof \BackedEnum ? $package->billing_period_unit->value : $package->billing_period_unit,
                'unit' => $package->unit,
                'default_quantity' => $package->default_quantity,
                'commercial_id' => $package->getKey(),
            ],
        );
    }
}
