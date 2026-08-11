<?php
namespace App\Policies;
use App\Models\Crm\CustomerInteraction; use App\Models\User;
class CustomerInteractionPolicy
{
    public function viewAny(User $user): bool { return $user->can('customer-care.view'); }
    public function view(User $user, CustomerInteraction $interaction): bool
    {
        if (! $user->can('customer-care.view')) return false;
        if ($user->isAdmin() || $user->canReadAcrossBusiness() || $user->can('customer-care.manage-assignments')) return true;
        return $this->hasActiveAssignment($user,$interaction);
    }
    public function create(User $user): bool { return $user->can('customer-care.interact'); }
    public function update(User $user, CustomerInteraction $interaction): bool { return $user->can('customer-care.interact') && ($user->can('customer-care.manage-assignments') || $this->hasActiveAssignment($user,$interaction)); }
    public function delete(User $user, CustomerInteraction $interaction): bool { return $this->update($user,$interaction); }
    private function hasActiveAssignment(User $user, CustomerInteraction $interaction): bool { $staffId=$user->staff?->id; return $staffId!==null && ($interaction->customer?->assignments()->where('staff_id',$staffId)->where('status','active')->exists() ?? false); }
}
