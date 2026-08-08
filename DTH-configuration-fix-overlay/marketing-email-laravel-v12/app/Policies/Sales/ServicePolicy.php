<?php

namespace App\Policies\Sales;

use App\Models\Sales\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.view-services');
    }

    public function view(User $user, Service $service): bool
    {
        return $user->can('sales.view-services');
    }

    public function create(User $user): bool
    {
        return $user->can('sales.manage-services');
    }

    public function update(User $user, Service $service): bool
    {
        return $user->can('sales.manage-services');
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->can('sales.manage-services');
    }
}
