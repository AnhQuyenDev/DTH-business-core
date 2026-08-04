<?php

namespace App\Policies;

use App\Models\User;

class CustomerInteractionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAnyMarketingUser();
    }

    public function create(User $user): bool
    {
        return $user->isMarketingStaff() || $user->isCustomerServiceStaff();
    }
}
