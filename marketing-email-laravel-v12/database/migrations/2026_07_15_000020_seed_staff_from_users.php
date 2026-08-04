<?php

use App\Enums\Crm\StaffEmploymentStatus;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $staffRoles = ['admin', 'marketing_manager', 'marketing_staff', 'customer_service_manager', 'customer_service_staff'];

        $users = User::whereIn('role', $staffRoles)
            ->whereDoesntHave('staff')
            ->get();

        foreach ($users as $user) {
            $department = match ($user->role) {
                'admin'                     => 'admin',
                'marketing_manager'         => 'marketing',
                'marketing_staff'           => 'marketing',
                'customer_service_manager'  => 'customer_service',
                'customer_service_staff'    => 'customer_service',
                default                     => 'marketing',
            };

            DB::table('staff')->insert([
                'user_id'               => $user->id,
                'employee_code'         => 'EMP-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
                'full_name'             => $user->name,
                'department'            => $department,
                'position'              => match ($user->role) {
                    'admin'                     => 'Quản trị viên',
                    'marketing_manager'         => 'Trưởng phòng Marketing',
                    'marketing_staff'           => 'Nhân viên Marketing',
                    'customer_service_manager'  => 'Trưởng phòng CSKH',
                    'customer_service_staff'    => 'Nhân viên CSKH',
                    default                     => 'Nhân viên',
                },
                'phone'                 => null,
                'employment_status'     => StaffEmploymentStatus::Active->value,
                'can_receive_customers' => in_array($user->role, ['admin', 'customer_service_manager', 'customer_service_staff']),
                'customer_capacity'     => match ($user->role) {
                    'customer_service_staff' => 50,
                    default                  => null,
                },
                'distribution_weight'   => 1.00,
                'started_at'            => $user->created_at?->toDateString() ?? now()->toDateString(),
                'ended_at'              => null,
                'metadata'              => json_encode(['migrated_from_user' => true, 'original_role' => $user->role]),
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('staff')->where('metadata->migrated_from_user', true)->delete();
    }
};
