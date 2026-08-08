<?php

namespace App\Policies\Sales;

use App\Models\Sales\ServicePackage;
use App\Models\User;

class ServicePackagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.view-service-packages');
    }

    public function view(User $user, ServicePackage $servicePackage): bool
    {
        return $user->can('sales.view-service-packages');
    }

    public function create(User $user): bool
    {
        return $user->can('sales.manage-service-packages');
    }

    public function update(User $user, ServicePackage $servicePackage): bool
    {
        return $user->can('sales.manage-service-packages');
    }

    public function delete(User $user, ServicePackage $servicePackage): bool
    {
        return $user->can('sales.manage-service-packages');
    }
}
