<?php
namespace Dth\AccountManagement\Support;

use Dth\AccountManagement\Services\AccessControlService;

final class AccountAuthorization
{
    public function __construct(private readonly AccessControlService $access) {}
    public function allows(string $permission, mixed $user = null): bool
    {
        $user ??= auth()->user();
        return $this->access->allows($user, $permission);
    }
}
