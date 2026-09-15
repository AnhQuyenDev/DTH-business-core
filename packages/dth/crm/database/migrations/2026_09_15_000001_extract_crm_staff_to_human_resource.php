<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('crm_staff')) {
            return;
        }

        if (! Schema::hasTable('hr_employees') || ! Schema::hasTable('hr_departments') || ! Schema::hasTable('hr_positions')) {
            throw new RuntimeException('Human Resource tables must be migrated before extracting CRM staff.');
        }

        $legacyIdentityExists = Schema::hasColumn('crm_staff', 'name');

        if (! Schema::hasColumn('crm_staff', 'employee_id')) {
            Schema::table('crm_staff', function (Blueprint $table): void {
                $table->foreignId('employee_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('hr_employees')
                    ->restrictOnDelete();
            });
        }

        if (! Schema::hasColumn('crm_staff', 'assignment_enabled')) {
            Schema::table('crm_staff', function (Blueprint $table): void {
                $table->boolean('assignment_enabled')->default(true)->index()->after('employee_id');
            });
        }

        if ($legacyIdentityExists) {
            $this->migrateLegacyStaff();
            $this->migrateAvailability();
            $this->removeLegacyIdentityColumns();

            return;
        }

        $this->importLegacyMonolithProfiles();
    }

    public function down(): void
    {
        if (! Schema::hasTable('crm_staff') || Schema::hasColumn('crm_staff', 'name')) {
            return;
        }

        Schema::table('crm_staff', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('staff_code')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 30)->nullable();
            $table->string('department')->nullable()->index();
            $table->string('position')->nullable();
            $table->string('employment_status', 30)->default('active')->index();
        });

        $rows = DB::table('crm_staff')
            ->leftJoin('hr_employees', 'hr_employees.id', '=', 'crm_staff.employee_id')
            ->leftJoin('hr_departments', 'hr_departments.id', '=', 'hr_employees.department_id')
            ->leftJoin('hr_positions', 'hr_positions.id', '=', 'hr_employees.position_id')
            ->get([
                'crm_staff.id as staff_id',
                'hr_employees.user_id',
                'hr_employees.employee_code',
                'hr_employees.full_name',
                'hr_employees.email',
                'hr_employees.phone',
                'hr_employees.employment_status',
                'hr_departments.name as department_name',
                'hr_positions.title as position_title',
            ]);

        foreach ($rows as $row) {
            DB::table('crm_staff')->where('id', $row->staff_id)->update([
                'user_id' => $row->user_id,
                'staff_code' => $row->employee_code,
                'name' => $row->full_name,
                'email' => $row->email,
                'phone' => $row->phone,
                'department' => $row->department_name,
                'position' => $row->position_title,
                'employment_status' => $row->employment_status ?: 'active',
            ]);
        }

        if (! Schema::hasTable('crm_staff_availabilities')) {
            Schema::create('crm_staff_availabilities', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('staff_id')->constrained('crm_staff')->cascadeOnDelete();
                $table->date('date')->index();
                $table->string('status', 30)->default('working');
                $table->text('note')->nullable();
                $table->timestamps();
                $table->unique(['staff_id', 'date']);
            });
        }

        if (Schema::hasTable('hr_employee_availabilities')) {
            $profileMap = DB::table('crm_staff')->pluck('id', 'employee_id');
            $availabilities = DB::table('hr_employee_availabilities')->get();
            foreach ($availabilities as $availability) {
                $staffId = $profileMap[$availability->employee_id] ?? null;
                if (! $staffId) {
                    continue;
                }

                DB::table('crm_staff_availabilities')->updateOrInsert(
                    ['staff_id' => $staffId, 'date' => Carbon::parse($availability->starts_at)->toDateString()],
                    [
                        'status' => $availability->status,
                        'note' => $availability->note,
                        'created_at' => $availability->created_at ?? now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }

        if (Schema::hasColumn('crm_staff', 'employee_id')) {
            Schema::table('crm_staff', fn (Blueprint $table) => $table->dropConstrainedForeignId('employee_id'));
        }
        if (Schema::hasColumn('crm_staff', 'assignment_enabled')) {
            Schema::table('crm_staff', fn (Blueprint $table) => $table->dropColumn('assignment_enabled'));
        }
    }

    private function migrateLegacyStaff(): void
    {
        $rows = DB::table('crm_staff')->orderBy('id')->get();

        foreach ($rows as $row) {
            if (! empty($row->employee_id)) {
                continue;
            }

            $departmentId = $this->departmentId($row->department ?? null);
            $positionId = $this->positionId($row->position ?? null);
            $employeeId = null;

            if (! empty($row->user_id)) {
                $employeeId = DB::table('hr_employees')->where('user_id', $row->user_id)->value('id');
            }

            if (! $employeeId) {
                $employeeCode = $this->uniqueEmployeeCode((int) $row->id, $row->staff_code ?? null);
                $employeeId = DB::table('hr_employees')->insertGetId([
                    'user_id' => $row->user_id ?? null,
                    'employee_code' => $employeeCode,
                    'full_name' => trim((string) ($row->name ?? '')) ?: 'CRM employee '.$row->id,
                    'email' => $this->normalizeEmail($row->email ?? null),
                    'phone' => $this->normalizePhone($row->phone ?? null),
                    'department_id' => $departmentId,
                    'position_id' => $positionId,
                    'employment_status' => $this->employmentStatus($row->employment_status ?? null),
                    'started_at' => null,
                    'ended_at' => null,
                    'metadata' => json_encode([
                        'migrated_from' => 'crm_staff',
                        'legacy_staff_id' => $row->id,
                        'legacy_staff_code' => $row->staff_code ?? null,
                    ], JSON_UNESCAPED_UNICODE),
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => now(),
                    'deleted_at' => $row->deleted_at ?? null,
                ]);
            }

            $function = $this->guessFunction(trim((string) ($row->department ?? '')).' '.trim((string) ($row->position ?? '')));
            if ($function && Schema::hasTable('hr_employee_business_functions')) {
                $hasFunction = DB::table('hr_employee_business_functions')
                    ->where('employee_id', $employeeId)
                    ->where('function_key', $function)
                    ->exists();

                if (! $hasFunction) {
                    DB::table('hr_employee_business_functions')->insert([
                        'employee_id' => $employeeId,
                        'function_key' => $function,
                        'authority_level' => $this->guessAuthority((string) ($row->position ?? '')),
                        'is_primary' => ! DB::table('hr_employee_business_functions')
                            ->where('employee_id', $employeeId)
                            ->where('is_primary', true)
                            ->exists(),
                        'is_active' => true,
                        'metadata' => json_encode(['migrated_from' => 'crm_staff'], JSON_UNESCAPED_UNICODE),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::table('crm_staff')->where('id', $row->id)->update([
                'employee_id' => $employeeId,
                'assignment_enabled' => ($row->employment_status ?? 'active') === 'active',
                'updated_at' => now(),
            ]);
        }
    }

    private function importLegacyMonolithProfiles(): void
    {
        // The older monolithic flow used `staff` as both the employee master
        // and the CRM assignment profile. The HR package imports the employee
        // identity first; this migration then recreates only the CRM-specific
        // capacity/settings side when that legacy table is still present.
        if (! Schema::hasTable('staff')) {
            return;
        }

        foreach (DB::table('staff')->orderBy('id')->get() as $row) {
            $employeeId = null;
            if (Schema::hasColumn('staff', 'user_id') && ! empty($row->user_id)) {
                $employeeId = DB::table('hr_employees')->where('user_id', $row->user_id)->value('id');
            }

            if (! $employeeId && Schema::hasColumn('staff', 'employee_code')) {
                $employeeCode = trim((string) ($row->employee_code ?? ''));
                if ($employeeCode !== '') {
                    $employeeId = DB::table('hr_employees')->where('employee_code', $employeeCode)->value('id');
                }
            }

            if (! $employeeId || DB::table('crm_staff')->where('employee_id', $employeeId)->exists()) {
                continue;
            }

            $employmentStatus = Schema::hasColumn('staff', 'employment_status')
                ? (string) ($row->employment_status ?? 'active')
                : 'active';
            $canReceive = Schema::hasColumn('staff', 'can_receive_customers')
                ? (bool) ($row->can_receive_customers ?? true)
                : true;

            $attributes = [
                'employee_id' => $employeeId,
                'assignment_enabled' => $employmentStatus === 'active' && $canReceive,
                'lead_capacity' => 50,
                'customer_capacity' => Schema::hasColumn('staff', 'customer_capacity')
                    ? $row->customer_capacity
                    : null,
                'distribution_weight' => Schema::hasColumn('staff', 'distribution_weight')
                    ? ($row->distribution_weight ?? 1)
                    : 1,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
                'deleted_at' => Schema::hasColumn('staff', 'deleted_at') ? ($row->deleted_at ?? null) : null,
            ];

            // Preserve the historical Staff ID when the fresh modular table
            // has not already used it. This makes later CRM data import easier
            // and is harmless on a clean database.
            if (! DB::table('crm_staff')->where('id', $row->id)->exists()) {
                $attributes['id'] = $row->id;
            }

            DB::table('crm_staff')->insert($attributes);
        }
    }

    private function migrateAvailability(): void
    {
        if (! Schema::hasTable('crm_staff_availabilities')) {
            return;
        }

        $employeeByStaff = DB::table('crm_staff')->whereNotNull('employee_id')->pluck('employee_id', 'id');

        foreach (DB::table('crm_staff_availabilities')->orderBy('id')->get() as $row) {
            $employeeId = $employeeByStaff[$row->staff_id] ?? null;
            if (! $employeeId) {
                continue;
            }

            $status = (string) ($row->status ?: 'working');
            [$receive, $support] = match ($status) {
                'working', 'remote' => [true, true],
                'half_day' => [false, true],
                default => [false, false],
            };
            $date = Carbon::parse($row->date);

            $exists = DB::table('hr_employee_availabilities')
                ->where('employee_id', $employeeId)
                ->whereDate('starts_at', $date->toDateString())
                ->where('status', $status)
                ->exists();

            if (! $exists) {
                DB::table('hr_employee_availabilities')->insert([
                    'employee_id' => $employeeId,
                    'status' => $status,
                    'starts_at' => $date->copy()->startOfDay(),
                    'ends_at' => $date->copy()->endOfDay(),
                    'can_receive_new_work' => $receive,
                    'can_support_existing_work' => $support,
                    'reason' => null,
                    'note' => $row->note ?? null,
                    'approved_by_user_id' => null,
                    'created_at' => $row->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::dropIfExists('crm_staff_availabilities');
    }

    private function removeLegacyIdentityColumns(): void
    {
        if (Schema::hasColumn('crm_staff', 'user_id')) {
            Schema::table('crm_staff', fn (Blueprint $table) => $table->dropConstrainedForeignId('user_id'));
        }

        $columns = array_values(array_filter([
            'staff_code', 'name', 'email', 'phone', 'department', 'position', 'employment_status',
        ], fn (string $column): bool => Schema::hasColumn('crm_staff', $column)));

        if ($columns !== []) {
            Schema::table('crm_staff', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }

    private function departmentId(?string $name): ?int
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }

        $existing = DB::table('hr_departments')->whereRaw('LOWER(name) = ?', [strtolower($name)])->value('id');
        if ($existing) {
            return (int) $existing;
        }

        $base = Str::of($name)->ascii()->slug('_')->lower()->limit(40, '')->toString() ?: 'department';
        $code = $this->uniqueCode('hr_departments', 'code', strtoupper($base));

        return DB::table('hr_departments')->insertGetId([
            'code' => $code,
            'name' => $name,
            'function_key' => $this->guessFunction($name),
            'color' => 'gray',
            'description' => null,
            'sort_order' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function positionId(?string $title): ?int
    {
        $title = trim((string) $title);
        if ($title === '') {
            return null;
        }

        $existing = DB::table('hr_positions')->whereRaw('LOWER(title) = ?', [strtolower($title)])->value('id');
        if ($existing) {
            return (int) $existing;
        }

        $base = Str::of($title)->ascii()->slug('_')->lower()->limit(70, '')->toString() ?: 'job_title';

        return DB::table('hr_positions')->insertGetId([
            'code' => $this->uniqueCode('hr_positions', 'code', strtoupper($base)),
            'title' => $title,
            'group_key' => $this->guessPositionGroup($title),
            'authority_level' => $this->guessAuthority($title),
            'function_key' => $this->guessFunction($title),
            'description' => null,
            'sort_order' => 0,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function uniqueEmployeeCode(int $legacyId, ?string $legacyCode = null): string
    {
        $legacyCode = trim((string) $legacyCode);
        $base = $legacyCode !== ''
            ? Str::limit($legacyCode, 45, '')
            : 'EMP-'.str_pad((string) $legacyId, 4, '0', STR_PAD_LEFT);

        return $this->uniqueCode('hr_employees', 'employee_code', $base);
    }

    private function uniqueCode(string $table, string $column, string $base): string
    {
        $code = $base;
        $suffix = 2;
        while (DB::table($table)->where($column, $code)->exists()) {
            $code = Str::limit($base, 42, '').'-'.$suffix++;
        }

        return $code;
    }

    private function normalizeEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return $email === '' ? null : $email;
    }

    private function normalizePhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return null;
        }
        $international = str_starts_with($phone, '+');
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        return $digits === '' ? null : ($international ? '+' : '').$digits;
    }

    private function employmentStatus(?string $status): string
    {
        return in_array($status, ['active', 'inactive', 'resigned'], true) ? $status : 'active';
    }

    private function guessFunction(string $value): ?string
    {
        $value = Str::of($value)->ascii()->lower()->toString();

        return match (true) {
            str_contains($value, 'marketing') => 'marketing',
            str_contains($value, 'sales'), str_contains($value, 'kinh doanh') => 'sales',
            str_contains($value, 'customer'), str_contains($value, 'cskh'), str_contains($value, 'cham soc') => 'customer_service',
            str_contains($value, 'finance'), str_contains($value, 'accounting'), str_contains($value, 'tai chinh'), str_contains($value, 'ke toan') => 'finance',
            str_contains($value, 'technical'), str_contains($value, 'developer'), str_contains($value, 'engineer'), str_contains($value, 'ky thuat') => 'technical',
            default => null,
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
