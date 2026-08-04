<?php

namespace App\Policies\Sales;

use App\Models\Sales\Service;
use App\Models\User;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAnyMarketingUser();
    }

    public function view(User $user, Service $service): bool
    {
        return $user->isAnyMarketingUser();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Service $service): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Service $service): bool
    {
        return $user->isAdmin();
    }
}
