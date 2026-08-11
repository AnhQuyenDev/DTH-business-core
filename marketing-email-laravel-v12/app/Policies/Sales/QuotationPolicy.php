<?php

namespace App\Policies\Sales;

use App\Models\Sales\Quotation;
use App\Models\User;

class QuotationPolicy
{
    public function viewAny(User $user): bool { return $user->can('sales.view-quotations'); }

    public function view(User $user, Quotation $quotation): bool
    {
        if (! $user->can('sales.view-quotations')) return false;
        if ($user->isAdmin() || $user->canReadAcrossBusiness() || $user->can('sales.approve-quotations') || $user->can('sales.verify-payments')) return true;
        return $this->isSalesHandler($user, $quotation);
    }

    public function create(User $user): bool { return $user->can('sales.create-quotations'); }

    public function update(User $user, Quotation $quotation): bool
    {
        return $quotation->status->isEditable()
            && $user->can('sales.create-quotations')
            && $this->isSalesHandler($user, $quotation);
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        return $user->can('sales.cancel-quotations') && $quotation->status->isEditable();
    }

    public function send(User $user, Quotation $quotation): bool
    {
        return $quotation->status->canSend() && $user->can('sales.send-quotations') && $this->isSalesHandler($user, $quotation);
    }

    public function approve(User $user, Quotation $quotation): bool
    {
        return $user->can('sales.approve-quotations') && $quotation->created_by !== $user->id;
    }

    public function cancel(User $user, Quotation $quotation): bool
    {
        return $user->can('sales.cancel-quotations') && ! $quotation->status->isTerminal();
    }

    public function revise(User $user, Quotation $quotation): bool
    {
        return $quotation->status->value === 'revision_requested'
            && $user->can('sales.revise-quotations')
            && $this->isSalesHandler($user, $quotation);
    }

    public function recordCustomerResponse(User $user, Quotation $quotation): bool
    {
        return $quotation->status->canConfirm()
            && $user->can('sales.record-customer-response')
            && $this->isSalesHandler($user, $quotation);
    }

    public function verifyPayment(User $user, Quotation $quotation): bool
    {
        return $user->can('sales.verify-payments');
    }

    private function isSalesHandler(User $user, Quotation $quotation): bool
    {
        if ($user->can('sales.approve-quotations') || $user->can('sales.cancel-quotations')) return true;
        $staffId = $user->staff?->id;
        if ($staffId === null) return false;
        return $quotation->assigned_staff_id === $staffId
            || $quotation->created_by === $user->id
            || $quotation->opportunity?->assigned_staff_id === $staffId;
    }
}
