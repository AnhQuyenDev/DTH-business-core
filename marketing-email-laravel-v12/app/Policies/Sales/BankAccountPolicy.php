<?php

namespace App\Policies\Sales;

use App\Models\Sales\BankAccount;
use App\Models\User;

class BankAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin()
            || $user->canReadAcrossBusiness()
            || $user->isSalesStaff()
            || $user->isFinanceStaff();
    }

    public function view(User $user, BankAccount $bankAccount): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isFinanceStaff();
    }

    public function update(User $user, BankAccount $bankAccount): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, BankAccount $bankAccount): bool
    {
        return $this->create($user);
    }
}
