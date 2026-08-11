<?php
namespace App\Policies;
use App\Models\Crm\Customer; use App\Models\User;
class CustomerPolicy
{
    public function viewAny(User $user): bool { return $user->can('customer-care.view'); }
    public function view(User $user, Customer $customer): bool
    {
        if (! $user->can('customer-care.view')) return false;
        if ($user->isAdmin() || $user->canReadAcrossBusiness() || $user->can('customer-care.manage-assignments')) return true;
        return $this->hasActiveAssignment($user,$customer);
    }
    public function create(User $user): bool { return false; }
    public function update(User $user, Customer $customer): bool { return $user->can('customer-care.interact') && ($user->can('customer-care.manage-assignments') || $this->hasActiveAssignment($user,$customer)); }
    public function delete(User $user, Customer $customer): bool { return false; }
    public function interact(User $user, Customer $customer): bool { return $this->update($user,$customer); }
    public function manageAssignments(User $user, Customer $customer): bool { return $user->can('customer-care.manage-assignments'); }
    private function hasActiveAssignment(User $user, Customer $customer): bool { $staffId=$user->staff?->id; return $staffId!==null && $customer->assignments()->where('staff_id',$staffId)->where('status','active')->exists(); }
}
