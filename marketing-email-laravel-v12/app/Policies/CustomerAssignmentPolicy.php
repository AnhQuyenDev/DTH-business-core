<?php

namespace App\Policies;

use App\Models\Crm\CustomerAssignment;
use App\Models\User;

class CustomerAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin()
            || $user->canReadAcrossBusiness()
            || $user->isCustomerServiceManager()
            || $user->isCustomerServiceStaff();
    }

    public function view(User $user, CustomerAssignment $assignment): bool
    {
        if ($user->isAdmin() || $user->canReadAcrossBusiness() || $user->isCustomerServiceManager()) {
            return true;
        }

        $staffId = $user->staff?->id;

        return $staffId !== null && $assignment->customer?->assignments()
            ->where('staff_id', $staffId)
            ->where('status', 'active')
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->isCustomerServiceManager();
    }

    public function update(User $user, CustomerAssignment $assignment): bool
    {
        return $user->isCustomerServiceManager();
    }

    public function delete(User $user, CustomerAssignment $assignment): bool
    {
        return $user->isCustomerServiceManager();
    }
}
