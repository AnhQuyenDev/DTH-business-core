<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** @var array<int, int> */
    private array $departmentMap = [];

    /** @var array<int, int> */
    private array $positionMap = [];

    /** @var array<int, int> */
    private array $employeeMap = [];

    public function up(): void
    {
        // The monolithic flow used staff/departments/positions without the hr_
        // prefix. Import them when present so upgrading from that flow does not
        // require manual employee re-entry. On a clean modular install this is
        // intentionally a no-op.
        if (! Schema::hasTable('staff') || ! Schema::hasTable('hr_employees')) {
            return;
        }

        DB::transaction(function (): void {
            $this->importDepartments();
            $this->importPositions();
            $this->importEmployees();
            $this->importAvailabilities();
            $this->importBusinessFunctions();
        });
    }

    public function down(): void
    {
        // Data migration intentionally has no destructive rollback. The source
        // legacy tables may already have been removed by the host application,
        // and deleting HR rows here could also delete records edited after the
        // migration completed.
    }

    private function importDepartments(): void
    {
        if (! Schema::hasTable('departments')) {
            return;
        }

        foreach (DB::table('departments')->orderBy('id')->get() as $row) {
            $name = trim((string) ($row->name ?? '')) ?: 'Phòng ban '.$row->id;
            $legacyCode = trim((string) ($row->code ?? ''));
            $code = $legacyCode !== ''
                ? Str::limit($legacyCode, 50, '')
                : $this->uniqueCode('hr_departments', 'code', 'DEPT-'.str_pad((string) $row->id, 4, '0', STR_PAD_LEFT), 50);

            $id = DB::table('hr_departments')->where('code', $code)->value('id');
            if (! $id) {
                $id = DB::table('hr_departments')->whereRaw('LOWER(name) = ?', [strtolower($name)])->value('id');
            }

            if (! $id) {
                $id = DB::table('hr_departments')->insertGetId([
                    'code' => $this->uniqueCode('hr_departments', 'code', $code, 50),
                    'name' => $name,
                    'function_key' => $this->columnValue('departments', $row, 'function_key'),
                    'color' => $this->columnValue('departments', $row, 'color') ?: 'gray',
                    'description' => $this->columnValue('departments', $row, 'description'),
                    'sort_order' => (int) ($this->columnValue('departments', $row, 'sort_order') ?? 0),
                    'is_active' => (bool) ($this->columnValue('departments', $row, 'is_active') ?? true),
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }

            $this->departmentMap[(int) $row->id] = (int) $id;
        }
    }

    private function importPositions(): void
    {
        if (! Schema::hasTable('positions')) {
            return;
        }

        foreach (DB::table('positions')->orderBy('id')->get() as $row) {
            $title = trim((string) ($row->title ?? '')) ?: 'Chức danh '.$row->id;
            $id = DB::table('hr_positions')->whereRaw('LOWER(title) = ?', [strtolower($title)])->value('id');

            if (! $id) {
                $legacyCode = trim((string) $this->columnValue('positions', $row, 'code'));
                $baseCode = $legacyCode !== ''
                    ? $legacyCode
                    : (Str::of($title)->ascii()->slug('_')->lower()->toString() ?: 'job_title_'.$row->id);

                $id = DB::table('hr_positions')->insertGetId([
                    'code' => $this->uniqueCode('hr_positions', 'code', Str::limit($baseCode, 100, ''), 100),
                    'title' => $title,
                    'group_key' => $this->columnValue('positions', $row, 'group_key') ?: $this->guessPositionGroup($title),
                    'authority_level' => $this->columnValue('positions', $row, 'authority_level') ?: $this->guessAuthority($title),
                    'function_key' => $this->columnValue('positions', $row, 'function_key'),
                    'description' => $this->columnValue('positions', $row, 'description'),
                    'sort_order' => (int) ($this->columnValue('positions', $row, 'sort_order') ?? 0),
                    'is_active' => (bool) ($this->columnValue('positions', $row, 'is_active') ?? true),
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                ]);
            }

            $this->positionMap[(int) $row->id] = (int) $id;
        }
    }

    private function importEmployees(): void
    {
        $hasUsers = Schema::hasTable('users');

        foreach (DB::table('staff')->orderBy('id')->get() as $row) {
            $userId = $this->columnValue('staff', $row, 'user_id');
            $employeeCode = trim((string) ($this->columnValue('staff', $row, 'employee_code') ?? ''));
            if ($employeeCode === '') {
                $employeeCode = 'EMP-'.str_pad((string) $row->id, 4, '0', STR_PAD_LEFT);
            }

            $employeeId = $userId
                ? DB::table('hr_employees')->where('user_id', $userId)->value('id')
                : null;

            if (! $employeeId) {
                $employeeId = DB::table('hr_employees')->where('employee_code', $employeeCode)->value('id');
            }

            $departmentId = $this->departmentMap[(int) ($this->columnValue('staff', $row, 'department_id') ?? 0)] ?? null;
            $positionId = $this->positionMap[(int) ($this->columnValue('staff', $row, 'position_id') ?? 0)] ?? null;
            $email = null;
            if ($hasUsers && $userId) {
                $email = DB::table('users')->where('id', $userId)->value('email');
            }

            if (! $employeeId) {
                $employeeId = DB::table('hr_employees')->insertGetId([
                    'user_id' => $userId,
                    'employee_code' => $this->uniqueCode('hr_employees', 'employee_code', Str::limit($employeeCode, 50, ''), 50),
                    'full_name' => trim((string) ($this->columnValue('staff', $row, 'full_name') ?? '')) ?: 'Nhân viên '.$row->id,
                    'email' => $this->normalizeEmail($email),
                    'phone' => $this->normalizePhone($this->columnValue('staff', $row, 'phone')),
                    'department_id' => $departmentId,
                    'position_id' => $positionId,
                    'employment_status' => $this->normalizeEmploymentStatus($this->columnValue('staff', $row, 'employment_status')),
                    'started_at' => $this->columnValue('staff', $row, 'started_at'),
                    'ended_at' => $this->columnValue('staff', $row, 'ended_at'),
                    'metadata' => json_encode([
                        'migrated_from' => 'legacy_staff',
                        'legacy_staff_id' => $row->id,
                    ], JSON_UNESCAPED_UNICODE),
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => $row->updated_at ?? now(),
                    'deleted_at' => $this->columnValue('staff', $row, 'deleted_at'),
                ]);
            } else {
                // Do not overwrite HR identity that was already curated. Only
                // fill structural fields that are still empty.
                $existing = DB::table('hr_employees')->where('id', $employeeId)->first();
                $updates = [];
                if ($existing && $existing->department_id === null && $departmentId !== null) {
                    $updates['department_id'] = $departmentId;
                }
                if ($existing && $existing->position_id === null && $positionId !== null) {
                    $updates['position_id'] = $positionId;
                }
                if ($existing && empty($existing->email) && $email) {
                    $updates['email'] = $this->normalizeEmail($email);
                }
                if ($updates !== []) {
                    $updates['updated_at'] = now();
                    DB::table('hr_employees')->where('id', $employeeId)->update($updates);
                }
            }

            $this->employeeMap[(int) $row->id] = (int) $employeeId;
        }
    }

    private function importAvailabilities(): void
    {
        if (! Schema::hasTable('staff_availabilities')) {
            return;
        }

        foreach (DB::table('staff_availabilities')->orderBy('id')->get() as $row) {
            $employeeId = $this->employeeMap[(int) ($row->staff_id ?? 0)] ?? null;
            if (! $employeeId) {
                continue;
            }

            $startsAt = $row->starts_at ?? null;
            $endsAt = $row->ends_at ?? null;
            if (! $startsAt || ! $endsAt) {
                continue;
            }

            $exists = DB::table('hr_employee_availabilities')
                ->where('employee_id', $employeeId)
                ->where('starts_at', $startsAt)
                ->where('ends_at', $endsAt)
                ->exists();

            if ($exists) {
                continue;
            }

            $status = (string) ($row->status ?? 'working');
            [$receive, $support] = $this->availabilityDefaults($status);

            DB::table('hr_employee_availabilities')->insert([
                'employee_id' => $employeeId,
                'status' => $status,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'can_receive_new_work' => (bool) ($this->columnValue('staff_availabilities', $row, 'can_receive_new_customers') ?? $receive),
                'can_support_existing_work' => (bool) ($this->columnValue('staff_availabilities', $row, 'can_support_customers') ?? $support),
                'reason' => $this->columnValue('staff_availabilities', $row, 'reason'),
                'note' => $this->columnValue('staff_availabilities', $row, 'note'),
                'approved_by_user_id' => $this->columnValue('staff_availabilities', $row, 'approved_by_user_id'),
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ]);
        }
    }

    private function importBusinessFunctions(): void
    {
        if (Schema::hasTable('staff_business_functions')) {
            foreach (DB::table('staff_business_functions')->orderBy('id')->get() as $row) {
                $employeeId = $this->employeeMap[(int) ($row->staff_id ?? 0)] ?? null;
                $function = trim((string) ($row->function_key ?? ''));
                if (! $employeeId || $function === '') {
                    continue;
                }

                DB::table('hr_employee_business_functions')->updateOrInsert(
                    ['employee_id' => $employeeId, 'function_key' => $function],
                    [
                        'authority_level' => $row->authority_level ?? 'member',
                        'is_primary' => (bool) ($row->is_primary ?? false),
                        'is_active' => (bool) ($row->is_active ?? true),
                        'metadata' => $row->metadata ?? json_encode(['migrated_from' => 'legacy_staff_business_functions'], JSON_UNESCAPED_UNICODE),
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => now(),
                    ],
                );
            }

            return;
        }

        // Earlier monolithic snapshots may not yet have capability rows. In
        // that case seed one explicit capability from department + position.
        foreach ($this->employeeMap as $legacyStaffId => $employeeId) {
            $staff = DB::table('staff')->where('id', $legacyStaffId)->first();
            if (! $staff) {
                continue;
            }

            $legacyDepartmentId = $this->columnValue('staff', $staff, 'department_id');
            $department = $legacyDepartmentId && Schema::hasTable('departments')
                ? DB::table('departments')->where('id', $legacyDepartmentId)->first()
                : null;
            $function = trim((string) ($department?->function_key ?? ''));
            if ($function === '' || in_array($function, ['admin', 'other'], true)) {
                continue;
            }

            $legacyPositionId = $this->columnValue('staff', $staff, 'position_id');
            $authority = $legacyPositionId && Schema::hasTable('positions')
                ? DB::table('positions')->where('id', $legacyPositionId)->value('authority_level')
                : null;

            DB::table('hr_employee_business_functions')->updateOrInsert(
                ['employee_id' => $employeeId, 'function_key' => $function],
                [
                    'authority_level' => $authority ?: 'member',
                    'is_primary' => true,
                    'is_active' => true,
                    'metadata' => json_encode(['migrated_from' => 'legacy_department'], JSON_UNESCAPED_UNICODE),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    private function columnValue(string $table, object $row, string $column): mixed
    {
        return Schema::hasColumn($table, $column) ? ($row->{$column} ?? null) : null;
    }

    private function uniqueCode(string $table, string $column, string $base, int $maxLength): string
    {
        $base = trim($base) !== '' ? trim($base) : 'ITEM';
        $base = Str::limit($base, $maxLength, '');
        $code = $base;
        $suffix = 2;

        while (DB::table($table)->where($column, $code)->exists()) {
            $tail = '-'.$suffix++;
            $code = Str::limit($base, max(1, $maxLength - strlen($tail)), '').$tail;
        }

        return $code;
    }

    private function normalizeEmail(mixed $email): ?string
    {
        $value = strtolower(trim((string) $email));

        return $value === '' ? null : $value;
    }

    private function normalizePhone(mixed $phone): ?string
    {
        $value = trim((string) $phone);
        if ($value === '') {
            return null;
        }

        $international = str_starts_with($value, '+');
        $digits = preg_replace('/\D+/', '', $value) ?: '';

        return $digits === '' ? null : ($international ? '+' : '').$digits;
    }

    private function normalizeEmploymentStatus(mixed $status): string
    {
        $value = (string) $status;

        return in_array($value, ['active', 'inactive', 'resigned'], true) ? $value : 'active';
    }

    /** @return array{bool, bool} */
    private function availabilityDefaults(string $status): array
    {
        return match ($status) {
            'working', 'remote' => [true, true],
            'half_day' => [false, true],
            default => [false, false],
        };
    }

    private function guessAuthority(string $title): string
    {
        $value = Str::of($title)->ascii()->lower()->toString();

        return match (true) {
            str_contains($value, 'director'), str_contains($value, 'giam doc') => 'executive',
            str_contains($value, 'manager'), str_contains($value, 'head'), str_contains($value, 'truong phong'), str_contains($value, 'pho phong') => 'manager',
            str_contains($value, 'lead'), str_contains($value, 'truong nhom') => 'lead',
            str_contains($value, 'intern'), str_contains($value, 'thuc tap'), str_contains($value, 'collaborator'), str_contains($value, 'cong tac') => 'limited',
            default => 'member',
        };
    }

    private function guessPositionGroup(string $title): string
    {
        return match ($this->guessAuthority($title)) {
            'executive' => 'leadership',
            'manager', 'lead' => 'management',
            'limited' => 'temporary',
            default => 'professional',
        };
    }
};
