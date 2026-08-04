<?php

namespace App\Policies;

use App\Models\User;

class CustomerAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAnyMarketingUser();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user): bool
    {
        return $user->isAdmin();
    }
}
