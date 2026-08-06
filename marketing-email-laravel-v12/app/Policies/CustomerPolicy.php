<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Crm\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function before(User $user): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::CustomerServiceManager,
            UserRole::CustomerServiceStaff,
        ]);
    }

    public function view(User $user, Customer $customer): bool
    {
        if ($user->isCustomerServiceManager()) {
            return true;
        }

        return $this->hasActiveAssignment($user, $customer);
    }

    public function create(User $user): bool
    {
        if (
            config('business_flow.v2_enabled')
            && config('business_flow.customer_on_paid_only')
        ) {
            return false;
        }

        return false;
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->isCustomerServiceManager()
            || $this->hasActiveAssignment($user, $customer);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return false;
    }

    public function interact(User $user, Customer $customer): bool
    {
        return $user->isCustomerServiceManager()
            || $this->hasActiveAssignment($user, $customer);
    }

    public function manageAssignments(
        User $user,
        Customer $customer,
    ): bool {
        return $user->isCustomerServiceManager();
    }

    private function hasActiveAssignment(
        User $user,
        Customer $customer,
    ): bool {
        $staffId = $user->staff?->id;

        if ($staffId === null) {
            return false;
        }

        return $customer->assignments()
            ->where('staff_id', $staffId)
            ->where('status', 'active')
            ->exists();
    }
}
