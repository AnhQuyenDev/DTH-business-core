<?php

namespace App\Policies;

use App\Models\Crm\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAnyMarketingUser();
    }

    public function view(User $user, Customer $customer): bool
    {
        if ($user->isAdmin() || $user->isMarketingManager() || $user->isCustomerServiceManager()) {
            return true;
        }

        $staff = $user->staff;

        if (! $staff) {
            return false;
        }

        return $customer->assignments()
            ->where('staff_id', $staff->id)
            ->where('status', 'active')
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Customer $customer): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $staff = $user->staff;

        if (! $staff) {
            return false;
        }

        return $customer->assignments()
            ->where('staff_id', $staff->id)
            ->where('status', 'active')
            ->exists();
    }

    public function delete(User $user): bool
    {
        return $user->isAdmin();
    }
}
