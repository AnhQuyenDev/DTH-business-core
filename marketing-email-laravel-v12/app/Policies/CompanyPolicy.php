<?php

namespace App\Policies;

use App\Models\Crm\Company;
use App\Models\User;

class CompanyPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin() && in_array($ability, ['viewAny', 'view'], true)) {
            return true;
        }

        if ($user->canReadAcrossBusiness() && in_array($ability, ['viewAny', 'view'], true)) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can('crm.view-companies');
    }

    public function view(User $user, Company $company): bool
    {
        if (! $user->can('crm.view-companies')) {
            return false;
        }

        if ($user->can('crm.manage-company-owner') || $user->isBusinessManager()) {
            return true;
        }

        $staffId = $user->staff?->id;
        if ($staffId === null) {
            return false;
        }

        return $company->account_owner_staff_id === $staffId
            || $company->leads()->where('assigned_staff_id', $staffId)->exists()
            || $company->opportunities()->where('assigned_staff_id', $staffId)->exists();
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
        return $user->can('crm.manage-company-owner');
    }
}
