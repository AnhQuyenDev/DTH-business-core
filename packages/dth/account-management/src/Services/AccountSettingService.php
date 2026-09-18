<?php
namespace Dth\AccountManagement\Services;

use Dth\AccountManagement\Models\AccountSetting;
use Illuminate\Support\Facades\Schema;

final class AccountSettingService
{
    public function get(string $key, mixed $default = null): mixed
    {
        if (! Schema::hasTable('account_settings')) {
            return $default;
        }
        $setting = AccountSetting::query()->where('key', $key)->first();
        return $setting?->value ?? $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);
        if (is_bool($value)) return $value;
        if (is_numeric($value)) return (bool) $value;
        return in_array(strtolower((string) $value), ['1','true','yes','on'], true);
    }

    public function int(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }
}
