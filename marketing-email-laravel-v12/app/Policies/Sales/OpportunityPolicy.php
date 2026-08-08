<?php

namespace App\Policies\Sales;

use App\Enums\UserRole;
use App\Models\Sales\Opportunity;
use App\Models\User;

class OpportunityPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (
            $user->canReadAcrossBusiness()
            && in_array($ability, ['viewAny', 'view'], true)
        ) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::CustomerServiceManager,
            UserRole::SalesManager,
            UserRole::SalesStaff,
        ]);
    }

    public function view(User $user, Opportunity $opportunity): bool
    {
        if ($user->hasAnyRole([
            UserRole::CustomerServiceManager,
            UserRole::SalesManager,
        ])) {
            return true;
        }

        return $user->hasRole(UserRole::SalesStaff)
            && $user->staff?->id !== null
            && $opportunity->assigned_staff_id === $user->staff->id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::CustomerServiceManager,
            UserRole::SalesManager,
        ]);
    }

    public function process(User $user, Opportunity $opportunity): bool
    {
        if ($user->isSalesManager()) {
            return true;
        }

        return $user->hasRole(UserRole::SalesStaff)
            && $user->staff?->id !== null
            && $opportunity->assigned_staff_id === $user->staff->id;
    }

    public function update(User $user, Opportunity $opportunity): bool
    {
        return $this->process($user, $opportunity);
    }

    public function createQuotation(
        User $user,
        Opportunity $opportunity,
    ): bool {
        return $this->process($user, $opportunity);
    }

    public function delete(User $user, Opportunity $opportunity): bool
    {
        return false;
    }
}
