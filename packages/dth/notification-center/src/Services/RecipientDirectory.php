<?php

namespace Dth\NotificationCenter\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class RecipientDirectory
{
    /** @return array<int, string> */
    public function userOptions(?int $excludeUserId = null): array
    {
        $model = $this->userModel();
        if (! class_exists($model) || ! Schema::hasTable('users')) {
            return [];
        }

        return $this->activeUserQuery($model)
            ->when($excludeUserId, fn (Builder $query) => $query->whereKeyNot($excludeUserId))
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->mapWithKeys(fn ($user): array => [
                (int) $user->id => trim((string) $user->name).' · '.(string) $user->email,
            ])
            ->all();
    }

    /** @return array<int, string> */
    public function roleOptions(): array
    {
        if (! Schema::hasTable('account_roles')) {
            return [];
        }

        return DB::table('account_roles')
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id): array => [(int) $id => (string) $name])
            ->all();
    }

    /** @return array<int, string> */
    public function groupOptions(): array
    {
        if (! Schema::hasTable('account_groups')) {
            return [];
        }

        return DB::table('account_groups')
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id): array => [(int) $id => (string) $name])
            ->all();
    }

    /** @return array<int, string> */
    public function departmentOptions(): array
    {
        if (! Schema::hasTable('hr_departments')) {
            return [];
        }

        return DB::table('hr_departments')
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->mapWithKeys(fn ($name, $id): array => [(int) $id => (string) $name])
            ->all();
    }

    /**
     * @param array<string, mixed> $targets
     * @return array<int>
     */
    public function resolve(array $targets, bool $allowBroadcast = false): array
    {
        $ids = collect((array) ($targets['user_ids'] ?? []))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id): int => (int) $id);

        if ($allowBroadcast && (bool) ($targets['broadcast'] ?? false)) {
            $model = $this->userModel();
            if (class_exists($model) && Schema::hasTable('users')) {
                $ids = $ids->merge($this->activeUserQuery($model)->pluck('id')->map(fn ($id): int => (int) $id));
            }
        }

        $roleIds = collect((array) ($targets['role_ids'] ?? []))->filter(fn ($id) => is_numeric($id))->map(fn ($id): int => (int) $id)->all();
        if ($roleIds !== [] && Schema::hasTable('account_role_user')) {
            $ids = $ids->merge(DB::table('account_role_user')->whereIn('role_id', $roleIds)->pluck('user_id')->map(fn ($id): int => (int) $id));
        }

        $groupIds = collect((array) ($targets['group_ids'] ?? []))->filter(fn ($id) => is_numeric($id))->map(fn ($id): int => (int) $id)->all();
        if ($groupIds !== [] && Schema::hasTable('account_group_user')) {
            $ids = $ids->merge(DB::table('account_group_user')->whereIn('group_id', $groupIds)->pluck('user_id')->map(fn ($id): int => (int) $id));
        }

        $departmentIds = collect((array) ($targets['department_ids'] ?? []))->filter(fn ($id) => is_numeric($id))->map(fn ($id): int => (int) $id)->all();
        if ($departmentIds !== [] && Schema::hasTable('hr_employees')) {
            $ids = $ids->merge(DB::table('hr_employees')
                ->whereIn('department_id', $departmentIds)
                ->whereNotNull('user_id')
                ->whereNull('deleted_at')
                ->pluck('user_id')
                ->map(fn ($id): int => (int) $id));
        }

        return $this->filterActiveUserIds($ids->unique()->values()->all());
    }

    /** @return array<int> */
    public function usersWithPermission(string $permission): array
    {
        $model = $this->userModel();
        if (! class_exists($model) || ! Schema::hasTable('users')) {
            return [];
        }

        $fallbackEmails = array_values(array_filter(array_map('strtolower', [
            ...(array) config('dth-notification-center.fallback_administrator_emails', []),
            ...(array) config('dth-account-management.bootstrap.administrator_emails', []),
        ])));

        if (! class_exists('Dth\\AccountManagement\\Services\\AccessControlService')) {
            if ($fallbackEmails === []) {
                return [];
            }

            return $this->activeUserQuery($model)
                ->get(['id', 'email'])
                ->filter(fn ($user): bool => in_array(strtolower((string) $user->email), $fallbackEmails, true))
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();
        }

        $access = app('Dth\\AccountManagement\\Services\\AccessControlService');
        $ids = [];
        $this->activeUserQuery($model)
            ->orderBy('id')
            ->chunkById(200, function ($users) use (&$ids, $access, $permission): void {
                foreach ($users as $user) {
                    if ($access->allows($user, $permission)) {
                        $ids[] = (int) $user->getKey();
                    }
                }
            });

        return array_values(array_unique($ids));
    }

    /** @param array<int> $ids @return array<int> */
    private function filterActiveUserIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $model = $this->userModel();
        if (! class_exists($model)) {
            return [];
        }

        return $this->activeUserQuery($model)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /** @param class-string $model */
    private function activeUserQuery(string $model): Builder
    {
        $query = $model::query();
        if (Schema::hasColumn('users', 'account_status')) {
            $query->where('account_status', 'active');
        }
        if (Schema::hasColumn('users', 'locked_until')) {
            $query->where(function (Builder $builder): void {
                $builder->whereNull('locked_until')->orWhere('locked_until', '<=', now());
            });
        }

        return $query;
    }

    /** @return class-string */
    private function userModel(): string
    {
        return (string) config('auth.providers.users.model', \App\Models\User::class);
    }
}
