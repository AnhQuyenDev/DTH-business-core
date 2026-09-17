<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HumanResourceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $adminId = DB::table('users')->where('email', 'admin@dth.local')->value('id');

        $departments = collect([
            ['code' => 'SALES', 'name' => 'Sales', 'function_key' => 'sales', 'color' => 'success', 'sort_order' => 10],
            ['code' => 'CS', 'name' => 'Customer Success', 'function_key' => 'customer_service', 'color' => 'info', 'sort_order' => 20],
            ['code' => 'OPS', 'name' => 'Operations', 'function_key' => 'other', 'color' => 'gray', 'sort_order' => 30],
        ])->mapWithKeys(function (array $row) use ($now): array {
            DB::table('hr_departments')->updateOrInsert(
                ['code' => $row['code']],
                $row + ['is_active' => true, 'updated_at' => $now, 'created_at' => $now],
            );

            return [$row['code'] => DB::table('hr_departments')->where('code', $row['code'])->value('id')];
        });

        $positions = collect([
            ['code' => 'ACCOUNT-EXEC', 'title' => 'Account Executive', 'group_key' => 'professional', 'authority_level' => 'member', 'function_key' => 'sales', 'sort_order' => 10],
            ['code' => 'CS-SPECIALIST', 'title' => 'Customer Success Specialist', 'group_key' => 'professional', 'authority_level' => 'member', 'function_key' => 'customer_service', 'sort_order' => 20],
            ['code' => 'BUSINESS-CONSULTANT', 'title' => 'Business Consultant', 'group_key' => 'professional', 'authority_level' => 'member', 'function_key' => 'sales', 'sort_order' => 30],
            ['code' => 'OPS-MANAGER', 'title' => 'Operations Manager', 'group_key' => 'management', 'authority_level' => 'manager', 'function_key' => 'other', 'sort_order' => 40],
            ['code' => 'SDR', 'title' => 'Sales Development Representative', 'group_key' => 'professional', 'authority_level' => 'member', 'function_key' => 'sales', 'sort_order' => 50],
        ])->mapWithKeys(function (array $row) use ($now): array {
            DB::table('hr_positions')->updateOrInsert(
                ['code' => $row['code']],
                $row + ['is_active' => true, 'updated_at' => $now, 'created_at' => $now],
            );

            return [$row['code'] => DB::table('hr_positions')->where('code', $row['code'])->value('id')];
        });

        $employees = [
            ['employee_code' => 'EMP-DEMO-01', 'full_name' => 'Nguyen Minh Anh', 'email' => 'anh.nguyen@dth.local', 'phone' => '0901000001', 'department' => 'SALES', 'position' => 'ACCOUNT-EXEC', 'employment_status' => 'active', 'user_id' => $adminId, 'function_key' => 'sales'],
            ['employee_code' => 'EMP-DEMO-02', 'full_name' => 'Tran Bao Ngoc', 'email' => 'ngoc.tran@dth.local', 'phone' => '0901000002', 'department' => 'CS', 'position' => 'CS-SPECIALIST', 'employment_status' => 'active', 'user_id' => null, 'function_key' => 'customer_service'],
            ['employee_code' => 'EMP-DEMO-03', 'full_name' => 'Le Quang Huy', 'email' => 'huy.le@dth.local', 'phone' => '0901000003', 'department' => 'SALES', 'position' => 'BUSINESS-CONSULTANT', 'employment_status' => 'active', 'user_id' => null, 'function_key' => 'sales'],
            ['employee_code' => 'EMP-DEMO-04', 'full_name' => 'Pham Thu Ha', 'email' => 'ha.pham@dth.local', 'phone' => '0901000004', 'department' => 'OPS', 'position' => 'OPS-MANAGER', 'employment_status' => 'inactive', 'user_id' => null, 'function_key' => 'other'],
            ['employee_code' => 'EMP-DEMO-05', 'full_name' => 'Vo Duc Minh', 'email' => 'minh.vo@dth.local', 'phone' => '0901000005', 'department' => 'SALES', 'position' => 'SDR', 'employment_status' => 'active', 'user_id' => null, 'function_key' => 'sales'],
        ];

        foreach ($employees as $row) {
            DB::table('hr_employees')->updateOrInsert(
                ['employee_code' => $row['employee_code']],
                [
                    'user_id' => $row['user_id'],
                    'full_name' => $row['full_name'],
                    'email' => strtolower($row['email']),
                    'phone' => $row['phone'],
                    'department_id' => $departments[$row['department']],
                    'position_id' => $positions[$row['position']],
                    'employment_status' => $row['employment_status'],
                    'started_at' => $now->copy()->subYear()->toDateString(),
                    'ended_at' => null,
                    'metadata' => json_encode(['demo' => true]),
                    'deleted_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );

            $employeeId = DB::table('hr_employees')->where('employee_code', $row['employee_code'])->value('id');

            DB::table('hr_employee_business_functions')->updateOrInsert(
                ['employee_id' => $employeeId, 'function_key' => $row['function_key']],
                [
                    'authority_level' => $row['position'] === 'OPS-MANAGER' ? 'manager' : 'member',
                    'is_primary' => true,
                    'is_active' => true,
                    'metadata' => json_encode(['demo' => true]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );

            DB::table('hr_employee_availabilities')->insert([
                'employee_id' => $employeeId,
                'status' => $row['employment_status'] === 'active' ? 'working' : 'leave',
                'starts_at' => $now->copy()->startOfDay(),
                'ends_at' => $now->copy()->addDays(6)->endOfDay(),
                'can_receive_new_work' => $row['employment_status'] === 'active',
                'can_support_existing_work' => $row['employment_status'] === 'active',
                'reason' => $row['employment_status'] === 'active' ? 'demo_schedule' : 'demo_unavailable',
                'note' => 'Demo availability generated by HumanResourceDemoSeeder',
                'approved_by_user_id' => $adminId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
