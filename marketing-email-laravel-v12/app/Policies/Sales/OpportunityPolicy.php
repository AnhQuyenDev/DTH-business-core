<?php

namespace App\Policies\Sales;

use App\Models\Sales\Opportunity;
use App\Models\User;

class OpportunityPolicy
{
    public function viewAny(User $user): bool { return $user->can('sales.view-opportunities'); }
    public function view(User $user, Opportunity $opportunity): bool
    {
        if (! $user->can('sales.view-opportunities')) return false;
        if ($user->isAdmin() || $user->canReadAcrossBusiness() || $user->can('crm.assign-lead')) return true;
        return $user->staff?->id !== null && $opportunity->assigned_staff_id === $user->staff->id;
    }
    public function create(User $user): bool { return $user->can('sales.create-opportunities'); }
    public function process(User $user, Opportunity $opportunity): bool
    {
        if (! $user->can('sales.process-opportunities')) return false;
        if ($user->can('crm.assign-lead')) return true;
        return $user->staff?->id !== null && $opportunity->assigned_staff_id === $user->staff->id;
    }
    public function update(User $user, Opportunity $opportunity): bool { return $this->process($user, $opportunity); }
    public function createQuotation(User $user, Opportunity $opportunity): bool { return $user->can('sales.create-quotations') && $this->process($user, $opportunity); }
    public function delete(User $user, Opportunity $opportunity): bool { return false; }
}
