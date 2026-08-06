<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Crm\Company;
use App\Models\User;

class CompanyPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::MarketingManager,
            UserRole::CustomerServiceManager,
            UserRole::CustomerServiceStaff,
            UserRole::SalesManager,
            UserRole::SalesStaff,
        ]);
    }

    public function view(User $user, Company $company): bool
    {
        if ($user->hasAnyRole([
            UserRole::MarketingManager,
            UserRole::CustomerServiceManager,
            UserRole::SalesManager,
        ])) {
            return true;
        }

        $staffId = $user->staff?->id;

        if ($staffId === null) {
            return false;
        }

        if ($user->hasRole(UserRole::CustomerServiceStaff)) {
            return $company->account_owner_staff_id === $staffId
                || $company->leads()
                    ->where('assigned_staff_id', $staffId)
                    ->exists();
        }

        if ($user->hasRole(UserRole::SalesStaff)) {
            return $company->opportunities()
                ->where('assigned_staff_id', $staffId)
                ->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Company $company): bool
    {
        return false;
    }

    public function delete(User $user, Company $company): bool
    {
        return false;
    }

    public function manageOwner(User $user, Company $company): bool
    {
        return $user->isCustomerServiceManager();
    }
}
