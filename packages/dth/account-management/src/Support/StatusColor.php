<?php
namespace Dth\AccountManagement\Support;

final class StatusColor
{
    public static function account(mixed $state): string
    {
        $value = $state instanceof \BackedEnum ? $state->value : (string) $state;
        return match ($value) {
            'active', 'accepted' => 'success',
            'pending' => 'warning',
            'suspended', 'locked' => 'danger',
            'inactive', 'expired', 'revoked' => 'gray',
            default => 'gray',
        };
    }

    public static function module(string $module): string
    {
        return match ($module) {
            'accounts' => 'gray', 'commercial' => 'info', 'crm' => 'success', 'marketing' => 'primary', 'human-resource' => 'info', 'email' => 'warning',
            default => 'gray',
        };
    }
}
