<?php

namespace App\Policies\Sales;

use App\Enums\UserRole;
use App\Models\Sales\Quotation;
use App\Models\User;

class QuotationPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        // Admin/Executive/Viewer may audit quotations, but operational
        // actions stay with Sales/Finance so maker-checker is preserved.
        if (
            ($user->isAdmin() || $user->canReadAcrossBusiness())
            && in_array($ability, ['viewAny', 'view'], true)
        ) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            UserRole::SalesManager,
            UserRole::SalesStaff,
            UserRole::FinanceStaff,
        ]);
    }

    public function view(User $user, Quotation $quotation): bool
    {
        if ($user->hasAnyRole([
            UserRole::SalesManager,
            UserRole::FinanceStaff,
        ])) {
            return true;
        }

        return $this->isSalesHandler($user, $quotation);
    }

    public function create(User $user): bool
    {
        // Maker: regular Sales staff. A department manager is the checker.
        return $user->isSalesStaff()
            && ! $user->isSalesManager();
    }

    public function update(User $user, Quotation $quotation): bool
    {
        return $quotation->status->isEditable()
            && $this->isSalesHandler($user, $quotation);
    }

    public function delete(User $user, Quotation $quotation): bool
    {
        return $user->isSalesManager()
            && $quotation->status->isEditable();
    }

    public function send(User $user, Quotation $quotation): bool
    {
        return $quotation->status->canSend()
            && $this->isSalesHandler($user, $quotation);
    }

    public function approve(User $user, Quotation $quotation): bool
    {
        return $user->isSalesManager()
            && $quotation->created_by !== $user->id;
    }

    public function cancel(User $user, Quotation $quotation): bool
    {
        return $user->isSalesManager()
            && ! $quotation->status->isTerminal();
    }

    public function revise(User $user, Quotation $quotation): bool
    {
        return $quotation->status->value === 'revision_requested'
            && $this->isSalesHandler($user, $quotation);
    }

    public function verifyPayment(
        User $user,
        Quotation $quotation,
    ): bool {
        return $user->isFinanceStaff();
    }

    private function isSalesHandler(
        User $user,
        Quotation $quotation,
    ): bool {
        if ($user->isSalesManager()) {
            return true;
        }

        if (! $user->isSalesStaff()) {
            return false;
        }

        $staffId = $user->staff?->id;

        if ($staffId === null) {
            return false;
        }

        return $quotation->assigned_staff_id === $staffId
            || $quotation->created_by === $user->id
            || $quotation->opportunity?->assigned_staff_id === $staffId;
    }
}
