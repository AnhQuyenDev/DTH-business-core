<?php
namespace App\Policies;
use App\Models\Crm\CustomerAssignment; use App\Models\User;
class CustomerAssignmentPolicy
{
    public function viewAny(User $user): bool { return $user->can('customer-care.view'); }
    public function view(User $user, CustomerAssignment $assignment): bool
    {
        if (! $user->can('customer-care.view')) return false;
        if ($user->isAdmin() || $user->canReadAcrossBusiness() || $user->can('customer-care.manage-assignments')) return true;
        $staffId=$user->staff?->id; return $staffId!==null && ($assignment->customer?->assignments()->where('staff_id',$staffId)->where('status','active')->exists() ?? false);
    }
    public function create(User $user): bool { return $user->can('customer-care.manage-assignments'); }
    public function update(User $user, CustomerAssignment $assignment): bool { return $user->can('customer-care.manage-assignments'); }
    public function delete(User $user, CustomerAssignment $assignment): bool { return $user->can('customer-care.manage-assignments'); }
}
