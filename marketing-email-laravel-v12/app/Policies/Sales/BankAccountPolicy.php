<?php

namespace App\Policies\Sales;

use App\Models\Sales\BankAccount;
use App\Models\User;

class BankAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAnyMarketingUser();
    }

    public function view(User $user, BankAccount $bankAccount): bool
    {
        return $user->isAnyMarketingUser();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, BankAccount $bankAccount): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, BankAccount $bankAccount): bool
    {
        return $user->isAdmin();
    }
}
