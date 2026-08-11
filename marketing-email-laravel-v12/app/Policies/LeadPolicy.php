<?php

namespace App\Policies;

use App\Models\Crm\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool { return $user->can('crm.view-leads'); }
    public function view(User $user, Lead $lead): bool
    {
        if (! $user->can('crm.view-leads')) return false;
        if ($user->isAdmin() || $user->canReadAcrossBusiness() || $user->can('crm.assign-lead') || $user->can('marketing.view-campaigns')) return true;
        $staffId = $user->staff?->id;
        return $staffId !== null && ($lead->assigned_staff_id === $staffId || $lead->company?->account_owner_staff_id === $staffId);
    }
    public function process(User $user, Lead $lead): bool
    {
        if (! $user->can('crm.process-lead')) return false;
        if ($user->can('crm.assign-lead')) return true;
        return $user->staff?->id !== null && $lead->assigned_staff_id === $user->staff->id;
    }
    public function assign(User $user, Lead $lead): bool { return $user->can('crm.assign-lead'); }
    public function reassign(User $user, Lead $lead): bool { return $user->can('crm.reassign-lead'); }
    public function archive(User $user, Lead $lead): bool { return $user->can('crm.archive-lead'); }
    public function createOpportunity(User $user, Lead $lead): bool { return $user->can('sales.create-opportunities') && $this->process($user, $lead); }
    public function create(User $user): bool { return false; }
    public function update(User $user, Lead $lead): bool { return $this->process($user, $lead); }
    public function delete(User $user, Lead $lead): bool { return false; }
}
