<?php

namespace App\Policies\Sales;

use App\Models\Sales\PriceBook;
use App\Models\User;

class PriceBookPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.view-price-books');
    }

    public function view(User $user, PriceBook $priceBook): bool
    {
        return $user->can('sales.view-price-books');
    }

    public function create(User $user): bool
    {
        return $user->can('sales.manage-price-books');
    }

    public function update(User $user, PriceBook $priceBook): bool
    {
        return $user->can('sales.manage-price-books');
    }

    public function delete(User $user, PriceBook $priceBook): bool
    {
        return $user->can('sales.manage-price-books');
    }

    public function approve(User $user, PriceBook $priceBook): bool
    {
        return $user->can('sales.approve-price-books');
    }
}
