<?php

namespace Dth\HumanResource\Policies;

use Dth\HumanResource\Support\HumanResourceAuthorization;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final class HumanResourcePolicy
{
    public function __construct(private readonly HumanResourceAuthorization $authorization) {}

    public function viewAny(Authenticatable $user): bool
    {
        return $this->authorization->allows('hr.view', $user);
    }

    public function view(Authenticatable $user, Model $model): bool
    {
        return $this->authorization->allows('hr.view', $user);
    }

    public function create(Authenticatable $user): bool
    {
        return $this->authorization->allows('hr.manage', $user);
    }

    public function update(Authenticatable $user, Model $model): bool
    {
        return $this->authorization->allows('hr.manage', $user);
    }

    public function delete(Authenticatable $user, Model $model): bool
    {
        return $this->authorization->allows('hr.manage', $user);
    }

    public function deleteAny(Authenticatable $user): bool
    {
        return $this->authorization->allows('hr.manage', $user);
    }
}
