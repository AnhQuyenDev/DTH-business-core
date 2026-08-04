<?php

namespace App\Policies\Sales;

use App\Models\Sales\Quotation;
use App\Models\User;

class QuotationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAnyMarketingUser();
    }

    public function view(User $user, Quotation $quotation): bool
    {
        if ($user->isAdmin() || $user->isCustomerServiceManager()) {
            return true;
        }
        $staff = $user->staff;
        if (!$staff) return false;
        return $quotation->assigned_staff_id === $staff->id
            || $quotation->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isCustomerServiceStaff() || $user->isCustomerServiceManager();
    }

    public function update(User $user, Quotation $quotation): bool
    {
        if (!$quotation->status->isEditable()) return false;
        if ($user->isAdmin()) return true;
        return $quotation->created_by === $user->id;
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        if ($quotation->status->isTerminal()) return false;
        return $user->isAdmin() || $quotation->created_by === $user->id;
    }

    public function send(User $user, Quotation $quotation): bool
    {
        return $quotation->canSend()
            && ($user->isAdmin() || $user->isCustomerServiceManager() || $quotation->created_by === $user->id);
    }

    public function approve(User $user, Quotation $quotation): bool
    {
        return $user->isAdmin() || $user->isCustomerServiceManager();
    }
}
