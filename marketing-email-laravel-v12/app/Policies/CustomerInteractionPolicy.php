<?php

namespace App\Policies;

use App\Models\Crm\CustomerInteraction;
use App\Models\User;

class CustomerInteractionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin()
            || $user->canReadAcrossBusiness()
            || $user->isCustomerServiceManager()
            || $user->isCustomerServiceStaff();
    }

    public function view(User $user, CustomerInteraction $interaction): bool
    {
        if ($user->isAdmin() || $user->canReadAcrossBusiness() || $user->isCustomerServiceManager()) {
            return true;
        }

        return $this->hasActiveAssignment($user, $interaction);
    }

    public function create(User $user): bool
    {
        // The relation manager is reachable only through an authorized
        // Customer. Admin/read-across roles are deliberately read-only.
        return $user->isCustomerServiceManager()
            || $user->isCustomerServiceStaff();
    }

    public function update(User $user, CustomerInteraction $interaction): bool
    {
        return $user->isCustomerServiceManager()
            || $this->hasActiveAssignment($user, $interaction);
    }

    public function delete(User $user, CustomerInteraction $interaction): bool
    {
        return $user->isCustomerServiceManager()
            || $this->hasActiveAssignment($user, $interaction);
    }

    private function hasActiveAssignment(User $user, CustomerInteraction $interaction): bool
    {
        $staffId = $user->staff?->id;

        if ($staffId === null) {
            return false;
        }

        return $interaction->customer?->assignments()
            ->where('staff_id', $staffId)
            ->where('status', 'active')
            ->exists() ?? false;
    }
}
