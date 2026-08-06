<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Crm\Lead;
use App\Models\User;

class LeadPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::MarketingManager,
            UserRole::MarketingStaff,
            UserRole::CustomerServiceManager,
            UserRole::CustomerServiceStaff,
        ]);
    }

    public function view(User $user, Lead $lead): bool
    {
        if ($user->hasAnyRole([
            UserRole::MarketingManager,
            UserRole::MarketingStaff,
            UserRole::CustomerServiceManager,
        ])) {
            return true;
        }

        if (! $user->hasRole(UserRole::CustomerServiceStaff)) {
            return false;
        }

        $staffId = $user->staff?->id;

        if ($staffId === null) {
            return false;
        }

        return $lead->assigned_staff_id === $staffId
            || $lead->company?->account_owner_staff_id === $staffId;
    }

    public function process(User $user, Lead $lead): bool
    {
        if ($user->isCustomerServiceManager()) {
            return true;
        }

        return $user->hasRole(UserRole::CustomerServiceStaff)
            && $user->staff?->id !== null
            && $lead->assigned_staff_id === $user->staff->id;
    }

    public function assign(User $user, Lead $lead): bool
    {
        return $user->isCustomerServiceManager();
    }

    public function reassign(User $user, Lead $lead): bool
    {
        return $user->isCustomerServiceManager();
    }

    public function archive(User $user, Lead $lead): bool
    {
        return $user->isCustomerServiceManager();
    }

    public function createOpportunity(User $user, Lead $lead): bool
    {
        return $user->hasAnyRole([
            UserRole::CustomerServiceManager,
            UserRole::SalesManager,
        ]);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->process($user, $lead);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return false;
    }
}
