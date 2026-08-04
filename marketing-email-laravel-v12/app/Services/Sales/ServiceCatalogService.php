<?php

namespace App\Services\Sales;

use App\Models\Sales\Service;
use App\Models\Sales\ServicePackage;

class ServiceCatalogService
{
    public function getActiveServices()
    {
        return Service::where('status', 'active')
            ->with(['packages' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('sort_order')
            ->get();
    }

    public function getActivePackagesByService(int $serviceId)
    {
        return ServicePackage::where('service_id', $serviceId)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();
    }

    public function findServiceByCode(string $code): ?Service
    {
        return Service::where('service_code', $code)->first();
    }

    public function findPackageByCode(string $code): ?ServicePackage
    {
        return ServicePackage::where('package_code', $code)->first();
    }
}
