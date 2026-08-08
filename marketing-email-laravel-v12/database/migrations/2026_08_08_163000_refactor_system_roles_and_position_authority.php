<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            $table->string('authority_level', 30)
                ->default('member')
                ->after('title')
                ->index();
        });

        $this->normalizeExistingPositions();
        $this->ensureLegacyUsersHaveUsablePositions();
        $this->deduplicatePositions();

        DB::table('users')
            ->whereIn('role', [
                'marketing_manager',
                'marketing_staff',
                'customer_service_manager',
                'customer_service_staff',
                'sales_manager',
                'sales_staff',
                'finance_staff',
            ])
            ->update(['role' => 'user']);
    }

    public function down(): void
    {
        $users = DB::table('users')
            ->whereIn('role', ['user', 'executive'])
            ->get(['id', 'role']);

        foreach ($users as $user) {
            $staff = DB::table('staff')
                ->where('user_id', $user->id)
                ->first(['department_id', 'position_id']);

            if (! $staff) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['role' => 'viewer']);
                continue;
            }

            $departmentFunction = DB::table('departments')
                ->where('id', $staff->department_id)
                ->value('function_key');

            $authority = $staff->position_id
                ? DB::table('positions')
                    ->where('id', $staff->position_id)
                    ->value('authority_level')
                : 'member';

            $isManager = in_array($authority, ['executive', 'manager'], true);

            $legacyRole = match ($departmentFunction) {
                'marketing' => $isManager
                    ? 'marketing_manager'
                    : 'marketing_staff',
                'customer_service' => $isManager
                    ? 'customer_service_manager'
                    : 'customer_service_staff',
                'sales' => $isManager
                    ? 'sales_manager'
                    : 'sales_staff',
                'finance' => 'finance_staff',
                default => 'viewer',
            };

            DB::table('users')
                ->where('id', $user->id)
                ->update(['role' => $legacyRole]);
        }

        Schema::table('positions', function (Blueprint $table) {
            $table->dropIndex(['authority_level']);
            $table->dropColumn('authority_level');
        });
    }

    private function normalizeExistingPositions(): void
    {
        $positions = DB::table('positions')
            ->get(['id', 'title']);

        foreach ($positions as $position) {
            DB::table('positions')
                ->where('id', $position->id)
                ->update([
                    'title' => $this->genericTitle((string) $position->title),
                    'authority_level' => $this->inferAuthority((string) $position->title),
                    'updated_at' => now(),
                ]);
        }
    }

    private function ensureLegacyUsersHaveUsablePositions(): void
    {
        $legacyUsers = DB::table('users')
            ->whereIn('role', [
                'marketing_manager',
                'marketing_staff',
                'customer_service_manager',
                'customer_service_staff',
                'sales_manager',
                'sales_staff',
                'finance_staff',
            ])
            ->get(['id', 'role']);

        foreach ($legacyUsers as $user) {
            $staff = DB::table('staff')
                ->where('user_id', $user->id)
                ->first(['id', 'department_id', 'position_id']);

            if (! $staff) {
                continue;
            }

            $isManager = Str::endsWith((string) $user->role, '_manager');
            $desiredAuthority = $isManager ? 'manager' : 'member';
            $desiredTitle = $isManager ? 'Trưởng phòng' : 'Nhân viên';

            if ($staff->position_id) {
                DB::table('positions')
                    ->where('id', $staff->position_id)
                    ->update([
                        'authority_level' => $desiredAuthority,
                        'updated_at' => now(),
                    ]);

                continue;
            }

            if (! $staff->department_id) {
                continue;
            }

            $positionId = DB::table('positions')
                ->where('department_id', $staff->department_id)
                ->where('title', $desiredTitle)
                ->value('id');

            if (! $positionId) {
                $positionId = DB::table('positions')->insertGetId([
                    'title' => $desiredTitle,
                    'authority_level' => $desiredAuthority,
                    'department_id' => $staff->department_id,
                    'description' => null,
                    'sort_order' => $isManager ? 10 : 50,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('staff')
                ->where('id', $staff->id)
                ->update([
                    'position_id' => $positionId,
                    'updated_at' => now(),
                ]);
        }
    }

    private function deduplicatePositions(): void
    {
        $groups = DB::table('positions')
            ->select('department_id', 'title', DB::raw('COUNT(*) as total'))
            ->groupBy('department_id', 'title')
            ->having('total', '>', 1)
            ->get();

        foreach ($groups as $group) {
            $ids = DB::table('positions')
                ->where('department_id', $group->department_id)
                ->where('title', $group->title)
                ->orderBy('id')
                ->pluck('id');

            $keepId = $ids->shift();

            if (! $keepId || $ids->isEmpty()) {
                continue;
            }

            DB::table('staff')
                ->whereIn('position_id', $ids)
                ->update(['position_id' => $keepId]);

            DB::table('positions')
                ->whereIn('id', $ids)
                ->delete();
        }
    }

    private function genericTitle(string $title): string
    {
        $normalized = Str::lower(trim($title));

        return match (true) {
            str_contains($normalized, 'phó giám đốc') => 'Phó Giám đốc',
            str_contains($normalized, 'giám đốc') => 'Giám đốc',
            str_contains($normalized, 'trưởng phòng') => 'Trưởng phòng',
            str_contains($normalized, 'phó phòng') => 'Phó phòng',
            str_contains($normalized, 'trưởng nhóm') => 'Trưởng nhóm',
            str_contains($normalized, 'chuyên viên cao cấp') => 'Chuyên viên cao cấp',
            str_contains($normalized, 'chuyên viên') => 'Chuyên viên',
            str_contains($normalized, 'nhân viên') => 'Nhân viên',
            str_contains($normalized, 'thực tập') => 'Thực tập sinh',
            str_contains($normalized, 'cộng tác') => 'Cộng tác viên',
            default => trim($title),
        };
    }

    private function inferAuthority(string $title): string
    {
        $normalized = Str::lower(trim($title));

        return match (true) {
            str_contains($normalized, 'giám đốc') => 'executive',
            str_contains($normalized, 'trưởng phòng'),
            str_contains($normalized, 'phó phòng'),
            str_contains($normalized, 'quản lý') => 'manager',
            str_contains($normalized, 'trưởng nhóm') => 'lead',
            str_contains($normalized, 'thực tập'),
            str_contains($normalized, 'cộng tác') => 'limited',
            default => 'member',
        };
    }
};
