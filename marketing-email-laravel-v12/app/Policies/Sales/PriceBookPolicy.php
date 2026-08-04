<?php

namespace App\Policies\Sales;

use App\Models\Sales\PriceBook;
use App\Models\User;

class PriceBookPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAnyMarketingUser();
    }

    public function view(User $user, PriceBook $priceBook): bool
    {
        return $user->isAdmin() || $user->isCustomerServiceManager();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, PriceBook $priceBook): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, PriceBook $priceBook): bool
    {
        return $user->isAdmin();
    }

    public function approve(User $user, PriceBook $priceBook): bool
    {
        return $user->isAdmin() || $user->isCustomerServiceManager();
    }
}
